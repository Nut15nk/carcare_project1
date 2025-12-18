<?php
    // pages/ProfilePages.php

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['user'])) {
        header("Location: index.php?page=login");
        exit;
    }

    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../service/BookingService.php';
    require_once __DIR__ . '/../service/PaymentService.php';

    // ======================
    // USER INFO
    // ======================
    $user = $_SESSION['user'];

    $userId    = $user['userId'] ?? '';
    $firstName = $user['firstName'] ?? '';
    $lastName  = $user['lastName'] ?? '';
    $email     = $user['email'] ?? '';
    $phone     = $user['phone'] ?? '';
    $role      = $user['role'] ?? 'CUSTOMER';

    $fullName = trim($firstName . ' ' . $lastName);
    if ($fullName === '') {
        $fullName = $email;
    }

    // ======================
    // BOOKINGS + PAYMENTS
    // ======================
    $bookings   = [];
    $paymentMap = [];

    if ($userId) {
        $bookings = BookingService::getCustomerBookings($userId);

        foreach ($bookings as $booking) {
            $rid = $booking['reservationId'] ?? null;
            if ($rid) {
                $payment = PaymentService::getPaymentByReservation($rid);
                if ($payment) {
                    $paymentMap[$rid] = $payment;
                }
            }
        }
    }

    // sort newest first
    usort($bookings, function ($a, $b) {
        return strtotime($b['createdAt'] ?? '') - strtotime($a['createdAt'] ?? '');
    });
?>

<div class="min-h-screen bg-gray-50 py-10">
    <div class="max-w-4xl mx-auto px-4">

        <div class="bg-white rounded-xl shadow p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center">
                    <i data-lucide="user" class="w-7 h-7 text-blue-600"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($fullName); ?></h1>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($email); ?></p>
                </div>
            </div>

            <div class="border-t pt-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">

                <div class="flex items-start gap-3">
                    <i data-lucide="mail" class="w-5 h-5 text-gray-400 mt-1"></i>
                    <div>
                        <p class="text-gray-500">อีเมล</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>

                <?php if ($phone): ?>
                <div class="flex items-start gap-3">
                    <i data-lucide="phone" class="w-5 h-5 text-gray-400 mt-1"></i>
                    <div>
                        <p class="text-gray-500">โทรศัพท์</p>
                        <p class="font-semibold"><?php echo htmlspecialchars($phone); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex items-start gap-3">
                    <i data-lucide="shield" class="w-5 h-5 text-gray-400 mt-1"></i>
                    <div>
                        <p class="text-gray-500">สิทธิ์ผู้ใช้</p>
                        <p class="font-semibold">
                            <?php
                                echo match ($role) {
                                    'ADMIN'    => 'ผู้ดูแลระบบ',
                                    'EMPLOYEE' => 'พนักงาน',
                                    default    => 'ลูกค้า',
                                };
                            ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <i data-lucide="calendar" class="w-5 h-5 text-gray-400 mt-1"></i>
                    <div>
                        <p class="text-gray-500">จำนวนการจอง</p>
                        <p class="font-semibold"><?php echo count($bookings); ?> ครั้ง</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>


<script>
    lucide.createIcons();
</script>
