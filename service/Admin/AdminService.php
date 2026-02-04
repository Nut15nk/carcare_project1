<?php
namespace Service\Admin;

require_once __DIR__ . '/../../config/config.php';

class AdminService
{
    private static function db()
    {
        return \Database::connect();
    }

    /* ===================== DASHBOARD ===================== */

    /* ===================== DASHBOARD ===================== */

    public static function getDashboardStats(): array
    {
        $db = self::db();

        // 1. คำนวณรายได้รวมจาก payments ที่ชำระเงินแล้ว
        // ตรวจสอบว่า payments มีข้อมูลหรือไม่
        $totalRevenueQuery = $db->query("
        SELECT
            COALESCE(SUM(p.amount), 0) as total_revenue,
            COUNT(*) as total_payments,
            MIN(amount) as min_amount,
            MAX(amount) as max_amount
        FROM payments p
        WHERE p.payment_status IN ('paid', 'verified')
        AND p.amount > 0
    ");

        $revenueResult = $totalRevenueQuery->fetch(\PDO::FETCH_ASSOC);
        $totalRevenue  = (float) ($revenueResult['total_revenue'] ?? 0);

        // Debug log
        error_log("Dashboard Revenue - จาก payments: " . $totalRevenue . " (จำนวน: " . ($revenueResult['total_payments'] ?? 0) . ")");

        // 2. ถ้ารายได้น้อยเกินไป ให้คำนวณจาก reservations (backup)
        if ($totalRevenue < 1000) {
            $backupQuery = $db->query("
            SELECT COALESCE(SUM(r.final_price), 0) as backup_revenue
            FROM reservations r
            WHERE r.status IN ('completed', 'confirmed', 'active')
            AND EXISTS (
                SELECT 1 FROM payments p
                WHERE p.reservation_id = r.reservation_id
                AND p.payment_status IN ('paid', 'verified')
            )
        ");

            $backupResult  = $backupQuery->fetch(\PDO::FETCH_ASSOC);
            $backupRevenue = (float) ($backupResult['backup_revenue'] ?? 0);

            error_log("Dashboard Revenue - จาก reservations (backup): " . $backupRevenue);

            // ใช้ค่าที่มากกว่า
            $totalRevenue = max($totalRevenue, $backupRevenue);
        }

        // 3. ข้อมูลอื่นๆ
        return [
            'totalBookings'        => (int) $db
                ->query("SELECT COUNT(*) FROM reservations")
                ->fetchColumn(),

            'pendingBookings'      => (int) $db
                ->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")
                ->fetchColumn(),

            'activeBookings'       => (int) $db
                ->query("
                SELECT COUNT(*)
                FROM reservations
                WHERE status IN ('confirmed','active')
            ")
                ->fetchColumn(),

            'totalRevenue'         => $totalRevenue,

            'availableMotorcycles' => (int) $db
                ->query("
                SELECT COUNT(*)
                FROM motorcycles
                WHERE is_available = 1
            ")
                ->fetchColumn(),
        ];
    }

    public static function getRecentReservations(int $limit = 5): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            r.reservation_id,
            r.status,
            r.final_price AS total_price,
            DATE(r.start_datetime) AS start_date,
            DATE(r.end_datetime)   AS end_date,
            m.brand,
            m.model,
            c.first_name,
            c.last_name,
            c.email
        FROM reservations r
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        JOIN customers c ON r.customer_id = c.customer_id
        ORDER BY r.created_at DESC
        LIMIT ?
    ");

        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ===================== RESERVATIONS ===================== */

    public static function updateReservationStatus(string $reservationId, string $status): bool
    {
        $db = self::db();

        $stmt = $db->prepare("
        UPDATE reservations
        SET status = :status
        WHERE reservation_id = :reservation_id
    ");

        return $stmt->execute([
            ':status'         => $status,
            ':reservation_id' => $reservationId,
        ]);
    }

    /* ===================== RESERVATIONS + PAYMENTS ===================== */

    public static function getAllReservationsWithPayment(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            r.reservation_id                              AS reservationId,
            DATE(r.start_datetime)                        AS startDate,
            DATE(r.end_datetime)                          AS endDate,
            r.final_price                                 AS totalAmount,
            r.status                                      AS status,

            CONCAT(c.first_name, ' ', c.last_name)        AS customerName,
            c.email                                       AS customerEmail,
            CONCAT(m.brand, ' ', m.model)                 AS motorcycle,
            m.license_plate                               AS license_plate,

            p.payment_status                              AS payment_status,
            p.payment_method                              AS payment_method,
            p.payment_date                                AS payment_date,
            r.created_at

        FROM reservations r
        JOIN customers c ON r.customer_id = c.customer_id
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id

        ORDER BY r.created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function confirmPaymentAndUpdateReservation(string $reservationId): bool
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            // 1) อัปเดต payment
            $stmtPayment = $db->prepare("
            UPDATE payments
            SET payment_status = 'paid',
                payment_date = NOW()
            WHERE reservation_id = :reservation_id
        ");
            $stmtPayment->execute([
                ':reservation_id' => $reservationId,
            ]);

            // 2) อัปเดต reservation → confirmed / active
            $stmtReservation = $db->prepare("
            UPDATE reservations
            SET status = 'confirmed'
            WHERE reservation_id = :reservation_id
        ");
            $stmtReservation->execute([
                ':reservation_id' => $reservationId,
            ]);

            $db->commit();
            return true;

        } catch (\Throwable $e) {
            $db->rollBack();
            error_log('[confirmPaymentAndUpdateReservation] ' . $e->getMessage());
            return false;
        }
    }

    public static function confirmBooking(string $reservationId): bool
    {
        $db = self::db();

        // ตรวจสอบ payment ก่อน
        $stmt = $db->prepare("
        SELECT p.payment_status
        FROM reservations r
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id
        WHERE r.reservation_id = ?
        LIMIT 1
    ");
        $stmt->execute([$reservationId]);
        $paymentStatus = $stmt->fetchColumn();

        if ($paymentStatus !== 'paid') {
            throw new \Exception('ยังไม่ได้ชำระเงินจริง');
        }

        $stmt = $db->prepare("
        UPDATE reservations
        SET status = 'confirmed'
        WHERE reservation_id = ?
          AND status = 'pending'
    ");

        return $stmt->execute([$reservationId]);
    }

    public static function confirmPayment(string $reservationId): bool
    {
        $db = self::db();

        $stmt = $db->prepare("
        UPDATE payments
        SET payment_status = 'paid',
            payment_date = NOW()
        WHERE reservation_id = ?
    ");

        return $stmt->execute([$reservationId]);
    }

    public static function autoCancelExpiredUnpaidBookings(): int
    {
        $db = self::db();

        $stmt = $db->prepare("
        UPDATE reservations r
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id
        SET r.status = 'cancelled'
        WHERE r.status = 'pending'
          AND r.start_datetime <= NOW()
          AND (p.payment_status IS NULL OR p.payment_status != 'paid')
    ");

        $stmt->execute();
        return $stmt->rowCount();
    }

    /* ===================== AUTH ===================== */

    private static function requireOwner(): void
    {
        if (! isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'owner') {
            throw new \Exception('Access denied');
        }
    }

    /* ===================== GET ALL USERS ===================== */

    public static function getAllStaffWithOwners(): array
    {
        self::requireOwner();
        $db = self::db();

        $stmt = $db->query("
            SELECT
                owner_id        AS id,
                email,
                first_name      AS firstName,
                last_name       AS lastName,
                phone,
                'owner'         AS position,
                1               AS isActive,
                created_at
            FROM owners

            UNION ALL

            SELECT
                employee_id     AS id,
                email,
                first_name      AS firstName,
                last_name       AS lastName,
                phone,
                'staff'         AS position,
                is_active       AS isActive,
                created_at
            FROM employees

            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ===================== CREATE ===================== */

    public static function createUser(array $data): void
    {
        self::requireOwner();
        $db = self::db();

        if ($data['position'] === 'owner') {
            $stmt = $db->prepare("
                INSERT INTO owners (
                    owner_id, email, password_hash,
                    first_name, last_name, phone
                ) VALUES (
                    UUID(), :email, :password,
                    :first_name, :last_name, :phone
                )
            ");

            $stmt->execute([
                ':email'      => $data['email'],
                ':password'   => password_hash($data['password'], PASSWORD_BCRYPT),
                ':first_name' => $data['firstName'],
                ':last_name'  => $data['lastName'],
                ':phone'      => $data['phone'],
            ]);
            return;
        }

        // staff
        $stmt = $db->prepare("
            INSERT INTO employees (
                employee_id, owner_id, email, password_hash,
                first_name, last_name, phone, position, is_active
            ) VALUES (
                UUID(), :owner_id, :email, :password,
                :first_name, :last_name, :phone, 'staff', 1
            )
        ");

        $stmt->execute([
            ':owner_id'   => $_SESSION['user']['id'],
            ':email'      => $data['email'],
            ':password'   => password_hash($data['password'], PASSWORD_BCRYPT),
            ':first_name' => $data['firstName'],
            ':last_name'  => $data['lastName'],
            ':phone'      => $data['phone'],
        ]);
    }

    /* ===================== UPDATE ===================== */

    public static function updateUser(
        string $id,
        string $oldPosition,
        string $newPosition,
        array $data
    ): void {
        self::requireOwner();
        $db = self::db();

        $db->beginTransaction();

        try {
            // ================= SAME ROLE =================
            if ($oldPosition === $newPosition) {

                $table = $oldPosition === 'owner' ? 'owners' : 'employees';
                $idCol = $oldPosition === 'owner' ? 'owner_id' : 'employee_id';

                $fields = "
                first_name = :first_name,
                last_name  = :last_name,
                phone      = :phone
            ";

                $params = [
                    ':first_name' => $data['firstName'],
                    ':last_name'  => $data['lastName'],
                    ':phone'      => $data['phone'],
                    ':id'         => $id,
                ];

                if (! empty($data['password'])) {
                    $fields              .= ", password_hash = :password";
                    $params[':password']  = password_hash($data['password'], PASSWORD_BCRYPT);
                }

                $stmt = $db->prepare("
                UPDATE {$table}
                SET {$fields}
                WHERE {$idCol} = :id
            ");
                $stmt->execute($params);

                $db->commit();
                return;
            }

            // ================= OWNER ➜ STAFF =================
            if ($oldPosition === 'owner' && $newPosition === 'staff') {

                // 1. ดึง owner เดิม
                $stmt = $db->prepare("SELECT * FROM owners WHERE owner_id = ?");
                $stmt->execute([$id]);
                $owner = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (! $owner) {
                    throw new \Exception('ไม่พบ owner');
                }

                // 2. insert employee
                $stmt = $db->prepare("
                INSERT INTO employees (
                    employee_id, owner_id, email, password_hash,
                    first_name, last_name, phone, position, is_active
                ) VALUES (
                    UUID(), :owner_id, :email, :password,
                    :first_name, :last_name, :phone, 'staff', 1
                )
            ");

                $stmt->execute([
                    ':owner_id'   => $_SESSION['user']['id'], // เจ้าของร้านปัจจุบัน
                    ':email'      => $owner['email'],
                    ':password'   => ! empty($data['password'])
                        ? password_hash($data['password'], PASSWORD_BCRYPT)
                        : $owner['password_hash'],
                    ':first_name' => $data['firstName'],
                    ':last_name'  => $data['lastName'],
                    ':phone'      => $data['phone'],
                ]);

                // 3. delete owner
                $db->prepare("DELETE FROM owners WHERE owner_id = ?")->execute([$id]);

                $db->commit();
                return;
            }

            // ================= STAFF ➜ OWNER =================
            if ($oldPosition === 'staff' && $newPosition === 'owner') {

                $stmt = $db->prepare("SELECT * FROM employees WHERE employee_id = ?");
                $stmt->execute([$id]);
                $emp = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (! $emp) {
                    throw new \Exception('ไม่พบพนักงาน');
                }

                $stmt = $db->prepare("
                INSERT INTO owners (
                    owner_id, email, password_hash,
                    first_name, last_name, phone
                ) VALUES (
                    UUID(), :email, :password,
                    :first_name, :last_name, :phone
                )
            ");

                $stmt->execute([
                    ':email'      => $emp['email'],
                    ':password'   => ! empty($data['password'])
                        ? password_hash($data['password'], PASSWORD_BCRYPT)
                        : $emp['password_hash'],
                    ':first_name' => $data['firstName'],
                    ':last_name'  => $data['lastName'],
                    ':phone'      => $data['phone'],
                ]);

                $db->prepare("DELETE FROM employees WHERE employee_id = ?")->execute([$id]);

                $db->commit();
                return;
            }

            throw new \Exception('การเปลี่ยนตำแหน่งไม่ถูกต้อง');

        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /* ===================== DELETE ===================== */

    public static function deleteUser(string $id, string $position): void
    {
        self::requireOwner();

        if ($id === ($_SESSION['user']['id'] ?? '')) {
            throw new \Exception('ไม่สามารถลบตัวเองได้');
        }

        $db    = self::db();
        $table = $position === 'owner' ? 'owners' : 'employees';
        $idCol = $position === 'owner' ? 'owner_id' : 'employee_id';

        $stmt = $db->prepare("DELETE FROM {$table} WHERE {$idCol} = ?");
        $stmt->execute([$id]);
    }

    /* ===================== MOTORCYCLES ===================== */

    public static function getAllMotorcycles(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            motorcycle_id      AS motorcycleId,
            brand,
            model,
            year,
            license_plate      AS licensePlate,
            color,
            engine_cc as engineCc,
            price_per_day      AS pricePerDay,
            description,
            is_available       AS isAvailable,
            maintenance_status AS maintenanceStatus,
            image_url          AS imageUrl,
            created_at
        FROM motorcycles
        ORDER BY created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function createMotorcycle(array $data): void
    {
        $db = self::db();

        $stmt = $db->prepare("
            INSERT INTO motorcycles (
                motorcycle_id,
                brand,
                model,
                year,
                license_plate,
                color,
                engine_cc,
                price_per_day,
                description,
                is_available,
                maintenance_status,
                image_url

            ) VALUES (
                :id,
                :brand,
                :model,
                :year,
                :license_plate,
                :color,
                :engine_cc,
                :price_per_day,
                :description,
                :is_available,
                :maintenance_status,
                :image_url
            )
        ");

        $stmt->execute([
            ':id'                 => $data['motorcycle_id'],
            ':brand'              => $data['brand'],
            ':model'              => $data['model'],
            ':year'               => $data['year'],
            ':license_plate'      => $data['license_plate'],
            ':color'              => $data['color'],
            ':engine_cc'          => $data['engine_cc'] ?? 0,
            ':price_per_day'      => $data['price_per_day'],
            ':description'        => $data['description'] ?? null,
            ':is_available'       => ! empty($data['is_available']) ? 1 : 0,
            ':maintenance_status' => strtoupper($data['maintenance_status'] ?? 'READY'),
            ':image_url'          => $data['image_url'] ?? null,
        ]);
    }

    public static function updateMotorcycle(string $id, array $data): void
    {
        $db = self::db();

        $fields = "
        brand = :brand,
        model = :model,
        year = :year,
        license_plate = :license_plate,
        color = :color,
        engine_cc = :engine_cc,
        price_per_day = :price_per_day,
        description = :description,
        is_available = :is_available,
        maintenance_status = :maintenance_status
    ";

        $params = [
            ':id'                 => $id,
            ':brand'              => $data['brand'],
            ':model'              => $data['model'],
            ':year'               => $data['year'],
            ':license_plate'      => $data['license_plate'],
            ':color'              => $data['color'],
            ':engine_cc'          => $data['engine_cc'] ?? 0,
            ':price_per_day'      => $data['price_per_day'],
            ':description'        => $data['description'] ?? null,
            ':is_available'       => 1,
            ':maintenance_status' => strtoupper($data['maintenance_status'] ?? 'READY'),
        ];

        if (isset($data['image_url'])) {
            $fields               .= ", image_url = :image_url";
            $params[':image_url']  = $data['image_url'];
        }

        $stmt = $db->prepare("
        UPDATE motorcycles
        SET {$fields}
        WHERE motorcycle_id = :id
    ");

        $stmt->execute($params);
    }

    public static function deleteMotorcycle(string $id): void
    {
        $db = self::db();

        $stmt = $db->prepare("
            DELETE FROM motorcycles
            WHERE motorcycle_id = ?
        ");

        $stmt->execute([$id]);
    }

    /* ===================== CUSTOMERS ===================== */

    public static function getAllCustomers(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            c.customer_id   AS customerId,
            c.first_name    AS firstName,
            c.last_name     AS lastName,
            c.email,
            c.phone,
            c.line_id       AS line_id,
            c.is_active     AS isActive,
            c.created_at    AS createdAt,
            COUNT(r.reservation_id) AS totalBookings,
            COALESCE(SUM(r.final_price), 0) AS totalSpent
        FROM customers c
        LEFT JOIN reservations r ON r.customer_id = c.customer_id
        GROUP BY c.customer_id, c.first_name, c.last_name, c.email, c.phone, c.line_id, c.is_active, c.created_at
        ORDER BY c.created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ===================== CUSTOMER RESERVATIONS ===================== */

    public static function getCustomerReservations(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            r.reservation_id        AS reservationId,
            DATE(r.start_datetime)  AS startDate,
            DATE(r.end_datetime)    AS endDate,
            r.status,
            r.final_price,
            r.pickup_location,
            r.return_location,
            r.pickup_details,
            r.return_details,
            r.total_days,

            m.brand,
            m.model,
            m.license_plate,
            m.price_per_day,

            p.payment_status,
            p.payment_method,
            p.payment_date

        FROM reservations r
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id

        WHERE r.customer_id = ?
        ORDER BY r.created_at DESC
    ");

        $stmt->execute([$customerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ===================== DISCOUNTS ===================== */

    public static function getAllDiscounts(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            discount_id      AS discountId,
            discount_code    AS discountCode,
            discount_type    AS discountType,
            discount_value   AS discountValue,
            min_rental_days  AS minDays,
            max_discount_amount AS maxDiscount,
            start_date       AS startDate,
            end_date         AS endDate,
            usage_limit      AS usageLimit,
            used_count       AS usedCount,
            is_active        AS isActive,
            created_at
        FROM discounts
        ORDER BY created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function createDiscount(array $data): bool
    {
        self::requireOwner();
        $db = self::db();

        $stmt = $db->prepare("
        INSERT INTO discounts (
            discount_id,
            discount_code,
            discount_type,
            discount_value,
            min_rental_days,
            max_discount_amount,
            start_date,
            end_date,
            usage_limit,
            created_by,
            is_active
        ) VALUES (
            UUID(),
            :code,
            :type,
            :value,
            :min_days,
            :max_discount,
            :start_date,
            :end_date,
            :usage_limit,
            :created_by,
            1
        )
    ");

        return $stmt->execute([
            ':code'         => $data['discountCode'],
            ':type'         => $data['discountType'],
            ':value'        => $data['discountValue'],
            ':min_days'     => $data['minDays'],
            ':max_discount' => $data['maxDiscount'],
            ':start_date'   => $data['startDate'],
            ':end_date'     => $data['endDate'],
            ':usage_limit'  => $data['usageLimit'],
            ':created_by'   => $_SESSION['user']['id'],
        ]);
    }

    public static function updateDiscount(string $discountId, array $data): bool
    {
        self::requireOwner();
        $db = self::db();

        $stmt = $db->prepare("
        UPDATE discounts
        SET
            discount_type = :type,
            discount_value = :value,
            min_rental_days = :min_days,
            max_discount_amount = :max_discount,
            start_date = :start_date,
            end_date = :end_date,
            usage_limit = :usage_limit
        WHERE discount_id = :id
    ");

        return $stmt->execute([
            ':id'           => $discountId,
            ':type'         => $data['discountType'],
            ':value'        => $data['discountValue'],
            ':min_days'     => $data['minDays'],
            ':max_discount' => $data['maxDiscount'],
            ':start_date'   => $data['startDate'],
            ':end_date'     => $data['endDate'],
            ':usage_limit'  => $data['usageLimit'],
        ]);
    }

    public static function deleteDiscount(string $discountId): bool
    {
        self::requireOwner();
        $db = self::db();

        $stmt = $db->prepare("
        DELETE FROM discounts
        WHERE discount_id = ?
    ");

        return $stmt->execute([$discountId]);
    }

/* ===================== REPORTS ===================== */
    public static function getRevenueReport(string $type = 'monthly'): array
    {
        $db = self::db();

        if ($type !== 'monthly') {
            return [];
        }

        $stmt = $db->query("
        SELECT
            DATE_FORMAT(p.payment_date, '%Y-%m') AS month,
            SUM(p.amount) AS revenue
        FROM reservations r
        JOIN payments p ON p.reservation_id = r.reservation_id
        WHERE p.payment_status = 'paid'
        GROUP BY DATE_FORMAT(p.payment_date, '%Y-%m')
        ORDER BY month ASC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getRecentActivities(int $limit = 10): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT type, description, createdAt
        FROM (
            SELECT
                'booking' AS type,
                CONCAT('การจอง ', r.reservation_id, ' โดย ', c.first_name, ' ', c.last_name) AS description,
                r.created_at AS createdAt
            FROM reservations r
            JOIN customers c ON r.customer_id = c.customer_id

            UNION ALL

            SELECT
                'payment' AS type,
                CONCAT('ชำระเงิน ', p.reservation_id, ' จำนวน ', p.amount, ' บาท') AS description,
                p.payment_date AS createdAt
            FROM payments p
            WHERE p.payment_status = 'paid'
        ) x
        ORDER BY createdAt DESC
        LIMIT ?
    ");

        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getAllReservations(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            r.reservation_id,
            r.motorcycle_id,
            m.brand,
            m.model,
            r.final_price,
            r.status,
            c.first_name,
            c.last_name,
            c.email
        FROM reservations r
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        JOIN customers c ON r.customer_id = c.customer_id
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getCustomerFullProfile(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            c.customer_id   AS customerId,
            c.first_name    AS firstName,
            c.last_name     AS lastName,
            c.email,
            c.phone,
            c.created_at    AS createdAt,
            c.is_active     AS isActive,

            COUNT(r.reservation_id)        AS totalBookings,
            COALESCE(SUM(r.final_price),0) AS totalSpent,
            MAX(r.created_at)              AS lastBookingAt,
            COALESCE(AVG(r.total_days), 0) AS avgDays

        FROM customers c
        LEFT JOIN reservations r
            ON r.customer_id = c.customer_id

        WHERE c.customer_id = ?
        GROUP BY c.customer_id
        LIMIT 1
    ");

        $stmt->execute([$customerId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'customerId'    => $customerId,
                'firstName'     => '',
                'lastName'      => '',
                'email'         => '',
                'phone'         => '',
                'createdAt'     => '',
                'isActive'      => 1,
                'totalBookings' => 0,
                'totalSpent'    => 0,
                'lastBookingAt' => null,
                'avgDays'       => 0,
            ];
        }

        return $result;
    }

    public static function getCustomerReservationsDetailed(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            r.reservation_id   AS reservationId,
            DATE(r.start_datetime) AS startDate,
            DATE(r.end_datetime)   AS endDate,
            r.status,
            r.final_price,
            r.total_days,
            r.discount_amount,

            r.pickup_location,
            r.return_location,
            r.pickup_details,
            r.return_details,

            m.brand,
            m.model,
            m.license_plate,
            m.price_per_day,
            m.color,
            m.year,

            p.payment_status,
            p.payment_method,
            p.payment_date,
            p.amount

        FROM reservations r
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id
        WHERE r.customer_id = ?
        ORDER BY r.created_at DESC
    ");

        $stmt->execute([$customerId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getCustomerSummary(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            COUNT(r.reservation_id)                         AS totalBookings,
            COALESCE(SUM(r.final_price), 0)                 AS totalSpent,
            MAX(r.created_at)                               AS lastBookingAt,
            COALESCE(AVG(r.total_days), 0)                  AS avgDays
        FROM reservations r
        LEFT JOIN payments p
            ON p.reservation_id = r.reservation_id
           AND p.payment_status = 'paid'
        WHERE r.customer_id = ?
    ");

        $stmt->execute([$customerId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'totalBookings' => 0,
                'totalSpent'    => 0,
                'lastBookingAt' => null,
                'avgDays'       => 0,
            ];
        }

        return $result;
    }

    public static function getAllReservationsDetailed(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            r.reservation_id        AS reservationId,
            r.customer_id,
            r.motorcycle_id,
            r.status,
            r.final_price,
            r.total_price,
            r.discount_amount,
            r.pickup_location,
            r.pickup_details,
            r.return_location,
            r.return_details,
            r.total_days,
            r.created_at,
            DATE(r.start_datetime)  AS startDate,
            DATE(r.end_datetime)    AS endDate,

            CONCAT(c.first_name, ' ', c.last_name) AS customerName,
            c.email   AS customerEmail,
            c.phone   AS customerPhone,

            m.brand,
            m.model,
            m.year,
            m.color,
            m.license_plate,
            m.price_per_day,

            p.payment_status,
            p.payment_method,
            p.payment_date,
            p.amount

        FROM reservations r
        JOIN customers c   ON r.customer_id = c.customer_id
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id
        ORDER BY r.created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getBookingDetail(string $reservationId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            r.reservation_id AS reservationId,
            r.customer_id,
            r.motorcycle_id,
            r.status,
            r.final_price,
            r.total_price,
            r.discount_amount,
            r.deposit_amount,
            r.total_days,
            r.pickup_location,
            r.pickup_details,
            r.return_location,
            r.return_details,
            DATE(r.start_datetime) AS startDate,
            DATE(r.end_datetime)   AS endDate,
            r.created_at,

            c.first_name AS firstName,
            c.last_name  AS lastName,
            c.phone      AS phone,
            c.email      AS email,

            m.brand,
            m.model,
            m.color,
            m.year,
            m.license_plate,
            m.price_per_day,
            m.engine_cc,

            p.payment_status,
            p.payment_method,
            p.payment_date,
            p.amount,
            p.transaction_id,
            p.slip_image_url,
            p.notes

        FROM reservations r
        JOIN customers c   ON r.customer_id = c.customer_id
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        LEFT JOIN payments p ON p.reservation_id = r.reservation_id
        WHERE r.reservation_id = ?
        LIMIT 1
    ");

        $stmt->execute([$reservationId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'reservationId'   => $reservationId,
                'firstName'       => '',
                'lastName'        => '',
                'email'           => '',
                'phone'           => '',
                'startDate'       => '',
                'endDate'         => '',
                'final_price'     => 0,
                'total_price'     => 0,
                'discount_amount' => 0,
                'deposit_amount'  => 0,
                'total_days'      => 0,
                'brand'           => '',
                'model'           => '',
                'color'           => '',
                'year'            => '',
                'license_plate'   => '',
                'price_per_day'   => 0,
                'payment_status'  => 'pending',
                'payment_method'  => null,
                'payment_date'    => null,
                'amount'          => 0,
                'slip_image_url'  => null,
                'notes'           => null,
                'pickup_location' => '',
                'return_location' => '',
                'pickup_details'  => '',
                'return_details'  => '',
            ];
        }

        return $result;
    }

    public static function getAllCustomersWithStats(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            c.customer_id   AS customerId,
            c.first_name    AS firstName,
            c.last_name     AS lastName,
            c.email,
            c.phone,
            c.is_active     AS isActive,
            c.created_at    AS createdAt,
            COUNT(r.reservation_id)        AS totalBookings,
            COALESCE(SUM(r.final_price), 0) AS totalSpent,
            MAX(r.created_at)              AS lastBookingAt,
            COALESCE(AVG(r.total_days), 0) AS avgDays
        FROM customers c
        LEFT JOIN reservations r ON r.customer_id = c.customer_id
        GROUP BY c.customer_id, c.first_name, c.last_name, c.email, c.phone, c.is_active, c.created_at
        ORDER BY c.created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getPaymentDetail(string $reservationId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            p.*,
            r.reservation_id,
            r.customer_id,
            r.final_price,
            r.deposit_amount,
            c.first_name,
            c.last_name,
            c.email,
            m.brand,
            m.model
        FROM payments p
        JOIN reservations r ON p.reservation_id = r.reservation_id
        JOIN customers c ON r.customer_id = c.customer_id
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        WHERE p.reservation_id = ?
        LIMIT 1
    ");
        $stmt->execute([$reservationId]);

        // แก้จาก PDO::FETCH_ASSOC เป็น \PDO::FETCH_ASSOC
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function verifyPayment(string $reservationId): bool
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            // 1. อัปเดต payment status เป็น 'verified'
            $stmt = $db->prepare("
            UPDATE payments
            SET payment_status = 'verified',
                payment_date = NOW()
            WHERE reservation_id = ?
        ");
            $stmt->execute([$reservationId]);

            // 2. อัปเดต reservation status เป็น 'confirmed'
            $stmt = $db->prepare("
            UPDATE reservations
            SET status = 'confirmed'
            WHERE reservation_id = ?
        ");
            $stmt->execute([$reservationId]);

            $db->commit();
            return true;

        } catch (\Exception $e) { // แก้จาก Exception เป็น \Exception
            $db->rollBack();
            error_log("Verify payment error: " . $e->getMessage());
            return false;
        }
    }

    public static function rejectPayment(string $reservationId, string $reason = ''): bool
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            // 1. อัปเดต payment status เป็น 'rejected'
            $stmt = $db->prepare("
            UPDATE payments
            SET payment_status = 'rejected',
                notes = ?
            WHERE reservation_id = ?
        ");
            $stmt->execute([$reason, $reservationId]);

            // 2. อัปเดต reservation status เป็น 'cancelled'
            $stmt = $db->prepare("
            UPDATE reservations
            SET status = 'cancelled'
            WHERE reservation_id = ?
        ");
            $stmt->execute([$reservationId]);

            $db->commit();
            return true;

        } catch (\Exception $e) { // แก้จาก Exception เป็น \Exception
            $db->rollBack();
            error_log("Reject payment error: " . $e->getMessage());
            return false;
        }
    }

    // เพิ่มใน AdminService class หลัง rejectPayment()
    public static function completeBooking(string $reservationId): bool
    {
        $db = self::db();

        try {
            $db->beginTransaction();

            // 1. อัปเดต reservation status เป็น 'completed'
            $stmt = $db->prepare("
            UPDATE reservations
            SET status = 'completed'
            WHERE reservation_id = ?
            AND status IN ('confirmed', 'active')
        ");
            $stmt->execute([$reservationId]);

            // 2. อัปเดต motorcycle กลับสู่สถานะพร้อมใช้งาน
            $stmt = $db->prepare("
            UPDATE motorcycles m
            JOIN reservations r ON m.motorcycle_id = r.motorcycle_id
            SET m.is_available = 1
            WHERE r.reservation_id = ?
        ");
            $stmt->execute([$reservationId]);

            $db->commit();
            return true;

        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Complete booking error: " . $e->getMessage());
            return false;
        }
    }

    /* ===================== ENHANCED REVENUE REPORTS ===================== */
    public static function getEnhancedRevenueReport(string $period = 'monthly'): array
    {
        $db = self::db();

        $format       = '';
        $groupBy      = '';
        $selectFormat = '';

        switch ($period) {
            case 'daily':
                $format       = '%Y-%m-%d';
                $selectFormat = 'DATE_FORMAT(p.payment_date, \'%Y-%m-%d\')';
                $groupBy      = "DATE(p.payment_date)";
                break;
            case 'weekly':
                $format       = '%Y-%U';
                $selectFormat = 'CONCAT(YEAR(p.payment_date), \'-\', LPAD(WEEK(p.payment_date), 2, \'0\'))';
                $groupBy      = "YEARWEEK(p.payment_date)";
                break;
            case 'monthly':
                $format       = '%Y-%m';
                $selectFormat = 'DATE_FORMAT(p.payment_date, \'%Y-%m\')';
                $groupBy      = "DATE_FORMAT(p.payment_date, '%Y-%m')";
                break;
            case 'yearly':
                $format       = '%Y';
                $selectFormat = 'YEAR(p.payment_date)';
                $groupBy      = "YEAR(p.payment_date)";
                break;
            default:
                $format       = '%Y-%m';
                $selectFormat = 'DATE_FORMAT(p.payment_date, \'%Y-%m\')';
                $groupBy      = "DATE_FORMAT(p.payment_date, '%Y-%m')";
        }

        $sql = "
        SELECT
            {$selectFormat} as period,
            {$selectFormat} as label,
            COALESCE(SUM(p.amount), 0) as revenue,
            COUNT(DISTINCT p.reservation_id) as bookingCount,
            COALESCE(AVG(p.amount), 0) as avgBookingValue
        FROM payments p
        JOIN reservations r ON p.reservation_id = r.reservation_id
        WHERE p.payment_status IN ('paid', 'verified')
        AND p.payment_date IS NOT NULL
        AND p.amount > 0
        GROUP BY {$groupBy}
        ORDER BY period ASC
    ";

        $stmt = $db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

/* ===================== DEBUG & VALIDATION ===================== */
    public static function validateRevenueData(): array
    {
        $db = self::db();

        $results = [];

        // 1. ตรวจสอบ payments ทั้งหมด
        $paymentsQuery = $db->query("
        SELECT
            p.payment_id,
            p.reservation_id,
            p.amount,
            p.payment_status,
            p.payment_date,
            r.final_price,
            r.status as reservation_status,
            m.brand,
            m.model
        FROM payments p
        LEFT JOIN reservations r ON p.reservation_id = r.reservation_id
        LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        WHERE p.payment_status IN ('paid', 'verified')
        ORDER BY p.amount DESC
    ");

        $paymentsData = $paymentsQuery->fetchAll(\PDO::FETCH_ASSOC);

        // 2. ตรวจสอบ reservations ที่มีการชำระเงิน
        $reservationsQuery = $db->query("
        SELECT
            r.reservation_id,
            r.final_price,
            r.status,
            r.created_at,
            p.payment_status,
            p.amount as payment_amount,
            p.payment_date,
            m.brand,
            m.model
        FROM reservations r
        LEFT JOIN payments p ON r.reservation_id = p.reservation_id
        LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        WHERE r.status IN ('completed', 'confirmed', 'active')
        AND p.payment_status IN ('paid', 'verified')
        ORDER BY r.final_price DESC
    ");

        $reservationsData = $reservationsQuery->fetchAll(\PDO::FETCH_ASSOC);

        // 3. คำนวณยอดรวม
        $sumPayments     = array_sum(array_column($paymentsData, 'amount'));
        $sumReservations = array_sum(array_column($reservationsData, 'final_price'));

        // 4. ตรวจสอบรถยอดนิยม
        $topBikesQuery = $db->query("
        SELECT
            m.motorcycle_id,
            m.brand,
            m.model,
            COUNT(r.reservation_id) as booking_count,
            COALESCE(SUM(p.amount), 0) as total_revenue
        FROM motorcycles m
        LEFT JOIN reservations r ON m.motorcycle_id = r.motorcycle_id
        LEFT JOIN payments p ON r.reservation_id = p.reservation_id AND p.payment_status IN ('paid', 'verified')
        GROUP BY m.motorcycle_id, m.brand, m.model
        ORDER BY booking_count DESC
        LIMIT 10
    ");

        $topBikes = $topBikesQuery->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'payments_total'     => $sumPayments,
            'payments_count'     => count($paymentsData),
            'payments_data'      => $paymentsData,

            'reservations_total' => $sumReservations,
            'reservations_count' => count($reservationsData),
            'reservations_data'  => $reservationsData,

            'top_bikes'          => $topBikes,

            'database_name'      => $db->query("SELECT DATABASE()")->fetchColumn(),
        ];
    }

/* ===================== GET REAL-TIME REVENUE ===================== */
    public static function getRealTimeRevenue(): array
    {
        $db = self::db();

        // วิธีที่ปลอดภัยที่สุด: คำนวณจาก payments ที่เชื่อมโยงกับ reservations
        $query = $db->query("
        SELECT
            -- 1. รายได้จาก payments (หลัก)
            (SELECT COALESCE(SUM(p.amount), 0)
             FROM payments p
             WHERE p.payment_status IN ('paid', 'verified')
             AND p.amount > 0) as revenue_from_payments,

            -- 2. รายได้จาก reservations ที่มีการชำระเงิน (ยืนยัน)
            (SELECT COALESCE(SUM(r.final_price), 0)
             FROM reservations r
             WHERE r.status IN ('completed', 'confirmed', 'active')
             AND EXISTS (
                 SELECT 1 FROM payments p
                 WHERE p.reservation_id = r.reservation_id
                 AND p.payment_status IN ('paid', 'verified')
             )) as revenue_from_reservations,

            -- 3. จำนวน payments ที่ชำระแล้ว
            (SELECT COUNT(*)
             FROM payments p
             WHERE p.payment_status IN ('paid', 'verified')) as total_paid_payments,

            -- 4. จำนวน reservations ที่ชำระเงินแล้ว
            (SELECT COUNT(DISTINCT r.reservation_id)
             FROM reservations r
             WHERE EXISTS (
                 SELECT 1 FROM payments p
                 WHERE p.reservation_id = r.reservation_id
                 AND p.payment_status IN ('paid', 'verified')
             )) as total_paid_reservations
    ");

        return $query->fetch(\PDO::FETCH_ASSOC);
    }

    /* ===================== ENHANCED TOP MOTORCYCLES ===================== */
    public static function getTopMotorcycles(int $limit = 5): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            m.motorcycle_id AS motorcycleId,
            m.brand,
            m.model,
            m.year,
            m.license_plate AS licensePlate,
            m.color,
            m.engine_cc AS engineCc,
            m.price_per_day AS pricePerDay,
            m.image_url AS imageUrl,
            m.is_available AS isAvailable,

            -- จำนวนการจองที่เสร็จสมบูรณ์
            COALESCE((
                SELECT COUNT(*)
                FROM reservations r
                WHERE r.motorcycle_id = m.motorcycle_id
                AND r.status IN ('completed', 'confirmed', 'active')
            ), 0) AS bookingCount,

            -- รายได้จาก payments ที่ชำระเงินแล้ว
            COALESCE((
                SELECT SUM(p.amount)
                FROM payments p
                JOIN reservations r ON p.reservation_id = r.reservation_id
                WHERE r.motorcycle_id = m.motorcycle_id
                AND p.payment_status IN ('paid', 'verified')
                AND p.amount > 0
            ), 0) AS totalRevenue,

            -- ค่าเฉลี่ยวันเช่า
            COALESCE((
                SELECT AVG(r.total_days)
                FROM reservations r
                WHERE r.motorcycle_id = m.motorcycle_id
                AND r.status IN ('completed', 'confirmed', 'active')
            ), 0) AS avgDays

        FROM motorcycles m
        ORDER BY bookingCount DESC, totalRevenue DESC
        LIMIT ?
    ");

        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Debug log
        foreach ($result as $index => $motorcycle) {
            error_log("Top Motorcycle #" . ($index + 1) . ": " .
                $motorcycle['brand'] . " " . $motorcycle['model'] .
                " - Bookings: " . $motorcycle['bookingCount'] .
                " - Revenue: " . $motorcycle['totalRevenue']);
        }

        return $result;
    }

/* ===================== GET ALL MOTORCYCLES WITH IMAGES ===================== */
    public static function getAllMotorcyclesWithImages(): array
    {
        $db = self::db();

        $stmt = $db->query("
        SELECT
            motorcycle_id AS motorcycleId,
            brand,
            model,
            year,
            license_plate AS licensePlate,
            color,
            engine_cc AS engineCc,
            price_per_day AS pricePerDay,
            image_url AS imageUrl,
            is_available AS isAvailable,
            created_at AS createdAt
        FROM motorcycles
        ORDER BY created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ใน AdminService.php ให้เพิ่ม method นี้:
    public static function getTopMotorcyclesWithPeriod(int $limit = 5, string $period = 'all_time'): array
    {
        $db = self::db();

        // กำหนดช่วงเวลาตาม period สำหรับ payments
        $dateCondition = '';
        switch ($period) {
            case 'daily':
                $dateCondition = "AND DATE(p.payment_date) = CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND YEARWEEK(p.payment_date) = YEARWEEK(CURDATE())";
                break;
            case 'monthly':
                $dateCondition = "AND YEAR(p.payment_date) = YEAR(CURDATE())
                          AND MONTH(p.payment_date) = MONTH(CURDATE())";
                break;
            case 'yearly':
                $dateCondition = "AND YEAR(p.payment_date) = YEAR(CURDATE())";
                break;
            case 'all_time':
            default:
                $dateCondition = "";
                break;
        }

        $sql = "
        SELECT
            m.motorcycle_id AS motorcycleId,
            m.brand,
            m.model,
            m.year,
            m.license_plate AS licensePlate,
            m.color,
            m.engine_cc AS engineCc,
            m.price_per_day AS pricePerDay,
            m.image_url AS imageUrl,
            m.is_available AS isAvailable,

            -- จำนวนการจองที่ชำระเงินแล้วในช่วงเวลาที่เลือก
            COALESCE((
                SELECT COUNT(DISTINCT r.reservation_id)
                FROM reservations r
                JOIN payments p ON r.reservation_id = p.reservation_id
                WHERE r.motorcycle_id = m.motorcycle_id
                AND p.payment_status IN ('paid', 'verified')
                {$dateCondition}
            ), 0) AS bookingCount,

            -- รายได้จาก payments ที่ชำระเงินแล้วในช่วงเวลาที่เลือก
            COALESCE((
                SELECT SUM(p.amount)
                FROM payments p
                JOIN reservations r ON p.reservation_id = r.reservation_id
                WHERE r.motorcycle_id = m.motorcycle_id
                AND p.payment_status IN ('paid', 'verified')
                AND p.amount > 0
                {$dateCondition}
            ), 0) AS totalRevenue,

            -- ค่าเฉลี่ยวันเช่า
            COALESCE((
                SELECT AVG(r.total_days)
                FROM reservations r
                JOIN payments p ON r.reservation_id = p.reservation_id
                WHERE r.motorcycle_id = m.motorcycle_id
                AND p.payment_status IN ('paid', 'verified')
                {$dateCondition}
            ), 0) AS avgDays

        FROM motorcycles m
        ORDER BY bookingCount DESC, totalRevenue DESC
        LIMIT ?
    ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getTopMotorcyclesEnhanced(int $limit = 5, string $period = 'all_time'): array
    {
        $db = self::db();

        // กำหนดเงื่อนไขวันที่ตาม period
        $dateCondition = '';
        switch ($period) {
            case 'daily':
                $dateCondition = "AND DATE(p.payment_date) = CURDATE()";
                break;
            case 'weekly':
                $dateCondition = "AND YEARWEEK(p.payment_date) = YEARWEEK(CURDATE())";
                break;
            case 'monthly':
                $dateCondition = "AND YEAR(p.payment_date) = YEAR(CURDATE())
                      AND MONTH(p.payment_date) = MONTH(CURDATE())";
                break;
            case 'yearly':
                $dateCondition = "AND YEAR(p.payment_date) = YEAR(CURDATE())";
                break;
            case 'all_time':
            default:
                $dateCondition = "";
                break;
        }

        $sql = "
        SELECT
            m.motorcycle_id AS motorcycleId,
            m.brand,
            m.model,
            m.year,
            m.license_plate AS licensePlate,
            m.color,
            m.engine_cc AS engineCc,
            m.price_per_day AS pricePerDay,
            m.image_url AS imageUrl,
            m.is_available AS isAvailable,

            -- จำนวนการจองทั้งหมดในช่วงเวลาที่เลือก
            COALESCE((
                SELECT COUNT(*)
                FROM reservations r
                WHERE r.motorcycle_id = m.motorcycle_id
                AND r.status IN ('completed', 'confirmed', 'active')
                " . ($dateCondition ? "AND EXISTS (
                    SELECT 1 FROM payments p
                    WHERE p.reservation_id = r.reservation_id
                    AND p.payment_status IN ('paid', 'verified')
                    {$dateCondition}
                )" : "") . "
            ), 0) AS bookingCount,

            -- รายได้จาก reservations (รวมทั้งหมด)
            COALESCE((
                SELECT SUM(r.final_price)
                FROM reservations r
                WHERE r.motorcycle_id = m.motorcycle_id
                AND r.status IN ('completed', 'confirmed', 'active')
                " . ($dateCondition ? "AND EXISTS (
                    SELECT 1 FROM payments p
                    WHERE p.reservation_id = r.reservation_id
                    AND p.payment_status IN ('paid', 'verified')
                    {$dateCondition}
                )" : "") . "
            ), 0) AS totalRevenue

        FROM motorcycles m
        WHERE m.is_available = 1
        ORDER BY bookingCount DESC, totalRevenue DESC
        LIMIT ?
    ";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function debugRevenueData(): array
    {
        $db = self::db();

        // 1. ตรวจสอบข้อมูลทั้งหมดใน payments
        $payments = $db->query("
        SELECT
            p.*,
            r.status as reservation_status,
            r.final_price,
            m.brand,
            m.model
        FROM payments p
        LEFT JOIN reservations r ON p.reservation_id = r.reservation_id
        LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        ORDER BY p.amount DESC
    ")->fetchAll(\PDO::FETCH_ASSOC);

        // 2. ตรวจสอบข้อมูลทั้งหมดใน reservations
        $reservations = $db->query("
        SELECT
            r.*,
            p.payment_status,
            p.amount as payment_amount,
            m.brand,
            m.model
        FROM reservations r
        LEFT JOIN payments p ON r.reservation_id = p.reservation_id
        LEFT JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
        WHERE r.status IN ('completed', 'confirmed', 'active')
        ORDER BY r.final_price DESC
    ")->fetchAll(\PDO::FETCH_ASSOC);

        // 3. คำนวณยอดรวม
        $totalPayments     = array_sum(array_column($payments, 'amount'));
        $totalReservations = array_sum(array_column($reservations, 'final_price'));

        return [
            'total_payments'     => $totalPayments,
            'total_reservations' => $totalReservations,
            'payments_count'     => count($payments),
            'reservations_count' => count($reservations),
            'payments_data'      => $payments,
            'reservations_data'  => $reservations,
        ];
    }

    // ใน AdminService class ให้เพิ่ม method นี้ก่อนหน้า getCustomerProfile

    public static function getCustomerReservationStats(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            COUNT(CASE WHEN r.status IN ('completed', 'confirmed', 'active') THEN 1 END) AS successful_bookings,
            COUNT(CASE WHEN r.status IN ('cancelled', 'rejected') THEN 1 END) AS failed_bookings,
            COUNT(CASE WHEN r.status = 'pending' THEN 1 END) AS pending_bookings
        FROM reservations r
        WHERE r.customer_id = ?
    ");

        $stmt->execute([$customerId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'successful_bookings' => 0,
                'failed_bookings'     => 0,
                'pending_bookings'    => 0,
            ];
        }

        return $result;
    }

// แก้ไข method getCustomerProfile ให้รวมข้อมูลเพิ่มเติม
    public static function getCustomerProfile(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            c.customer_id   AS customerId,
            c.first_name    AS firstName,
            c.last_name     AS lastName,
            c.email,
            c.phone,
            c.line_id       AS lineId,
            c.is_active     AS isActive,
            c.created_at    AS createdAt,
            COUNT(r.reservation_id) AS totalBookings,
            COALESCE(SUM(r.final_price), 0) AS totalSpent,
            MAX(r.created_at) AS lastBookingAt
        FROM customers c
        LEFT JOIN reservations r ON r.customer_id = c.customer_id
        WHERE c.customer_id = ?
        GROUP BY c.customer_id, c.first_name, c.last_name, c.email, c.phone, c.line_id, c.is_active, c.created_at
        LIMIT 1
    ");

        $stmt->execute([$customerId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (! $result) {
            return [
                'customerId'    => $customerId,
                'firstName'     => '',
                'lastName'      => '',
                'email'         => '',
                'phone'         => '',
                'lineId'        => '',
                'isActive'      => 1,
                'createdAt'     => '',
                'totalBookings' => 0,
                'totalSpent'    => 0,
                'lastBookingAt' => null,
            ];
        }

        // ดึงสถิติการจองเพิ่มเติม
        $stats = self::getCustomerReservationStats($customerId);

        return array_merge($result, $stats);
    }

}
