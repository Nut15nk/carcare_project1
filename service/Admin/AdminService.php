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

    public static function getDashboardStats(): array
    {
        $db = self::db();

        return [
            'totalBookings'        => (int) $db
                ->query("SELECT COUNT(*) FROM reservations")
                ->fetchColumn(),

            'pendingBookings'      => (int) $db
                ->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")
                ->fetchColumn(),

            'activeBookings'       => (int) $db
                ->query("SELECT COUNT(*) FROM reservations WHERE status IN ('confirmed','active')")
                ->fetchColumn(),

            'totalRevenue'         => (float) $db
                ->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status = 'paid'")
                ->fetchColumn(),

            'availableMotorcycles' => (int) $db
                ->query("SELECT COUNT(*) FROM motorcycles WHERE is_available = 1")
                ->fetchColumn(),
        ];
    }

    public static function getRecentReservations(int $limit = 5): array
    {
        $db   = self::db();
        $stmt = $db->prepare("
            SELECT r.*, m.brand, m.model
            FROM reservations r
            JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
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
            r.reservation_id                    AS reservationId,
            r.start_date                        AS startDate,
            r.end_date                          AS endDate,
            r.final_price                       AS totalAmount,
            r.status                            AS status,

            CONCAT(c.first_name, ' ', c.last_name) AS customerName,
            CONCAT(m.brand, ' ', m.model)       AS motorcycle,

            p.payment_status                    AS payment_status,
            p.payment_method                    AS payment_method,
            p.payment_date                      AS payment_date

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
          AND r.created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
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
                    $fields .= ", password_hash = :password";
                    $params[':password'] = password_hash($data['password'], PASSWORD_BCRYPT);
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
                price_per_day      AS pricePerDay,
                description,
                is_available       AS isAvailable,
                maintenance_status AS maintenanceStatus,
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
                price_per_day,
                description,
                is_available,
                maintenance_status
            ) VALUES (
                :id,
                :brand,
                :model,
                :year,
                :license_plate,
                :color,
                :price_per_day,
                :description,
                :is_available,
                :maintenance_status
            )
        ");

        $stmt->execute([
            ':id'                 => $data['motorcycle_id'],
            ':brand'              => $data['brand'],
            ':model'              => $data['model'],
            ':year'               => $data['year'],
            ':license_plate'      => $data['license_plate'],
            ':color'              => $data['color'],
            ':price_per_day'      => $data['price_per_day'],
            ':description'        => $data['description'] ?? null,
            ':is_available'       => ! empty($data['is_available']) ? 1 : 0,
            ':maintenance_status' => $data['maintenance_status'] ?? 'READY',
        ]);
    }

    public static function updateMotorcycle(string $id, array $data): void
    {
        $db = self::db();

        $stmt = $db->prepare("
            UPDATE motorcycles
            SET
                brand = :brand,
                model = :model,
                year = :year,
                license_plate = :license_plate,
                color = :color,
                price_per_day = :price_per_day,
                description = :description,
                is_available = :is_available,
                maintenance_status = :maintenance_status
            WHERE motorcycle_id = :id
        ");

        $stmt->execute([
            ':id'                 => $id,
            ':brand'              => $data['brand'],
            ':model'              => $data['model'],
            ':year'               => $data['year'],
            ':license_plate'      => $data['license_plate'],
            ':color'              => $data['color'],
            ':price_per_day'      => $data['price_per_day'],
            ':description'        => $data['description'] ?? null,
            ':is_available'       => ! empty($data['is_available']) ? 1 : 0,
            ':maintenance_status' => $data['maintenance_status'] ?? 'READY',
        ]);
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
            customer_id   AS customerId,
            first_name    AS firstName,
            last_name     AS lastName,
            email,
            phone,
            is_verified   AS isActive,
            created_at    AS createdAt
        FROM customers
        ORDER BY created_at DESC
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /* ===================== CUSTOMER PROFILE ===================== */

    public static function getCustomerProfile(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            customer_id      AS customerId,
            first_name       AS firstName,
            last_name        AS lastName,
            email,
            phone,
            address,
            license_number   AS licenseNumber,
            id_card_number   AS idCardNumber,
            date_of_birth    AS dateOfBirth,
            is_verified      AS isVerified,
            created_at       AS createdAt
        FROM customers
        WHERE customer_id = ?
        LIMIT 1
    ");

        $stmt->execute([$customerId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

/* ===================== CUSTOMER RESERVATIONS ===================== */

    public static function getCustomerReservations(string $customerId): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            r.reservation_id    AS reservationId,
            r.start_date,
            r.end_date,
            r.status,
            r.final_price,
            m.brand,
            m.model,
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
            usage_limit = :usage_limit,
            updated_at = NOW()
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

        if ($type === 'monthly') {
            $stmt = $db->query("
            SELECT
                DATE_FORMAT(payment_date, '%Y-%m') AS month,
                SUM(amount) AS revenue
            FROM payments
            WHERE payment_status = 'paid'
            GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
            ORDER BY month ASC
        ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return [];
    }

    public static function getRecentActivities(int $limit = 10): array
    {
        $db = self::db();

        $stmt = $db->prepare("
        SELECT
            'booking' AS type,
            CONCAT('การจอง ', r.reservation_id) AS description,
            r.created_at AS createdAt
        FROM reservations r

        UNION ALL

        SELECT
            'payment' AS type,
            CONCAT('ชำระเงิน ', p.reservation_id) AS description,
            p.payment_date AS createdAt
        FROM payments p
        WHERE p.payment_status = 'paid'

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
            m.model
        FROM reservations r
        JOIN motorcycles m ON r.motorcycle_id = m.motorcycle_id
    ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

}
