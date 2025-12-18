<?php
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
$bookings    = [];
$paymentMap  = [];
$paidCount   = 0;
$unpaidCount = 0;

if ($userId) {
    // ดึงการจองของผู้ใช้
    $bookings = BookingService::getCustomerBookings($userId);

    // map reservation_id เป็น reservationId เพื่อให้ตรงกับ key
    foreach ($bookings as &$b) {
        $b['reservationId'] = $b['reservation_id'];
    }
    unset($b);

    // ดึง payment ของผู้ใช้
    $paymentMap = PaymentService::getPaymentsForUser($userId);

    // คำนวณ paid/unpaid
    foreach ($bookings as $booking) {
        $rid = $booking['reservationId'] ?? null;
        if ($rid && isset($paymentMap[$rid]) && strtoupper($paymentMap[$rid]['payment_status']) === 'PAID') {
            $paidCount++;
        }
    }

    $totalBookings = count($bookings);
    $unpaidCount = $totalBookings - $paidCount;

    // sort ใหม่ล่าสุดก่อน
    usort($bookings, function ($a, $b) {
        return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
    });
} else {
    $totalBookings = 0;
}
?>

<div class="min-h-screen bg-gray-50 py-10">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <i data-lucide="user" class="w-8 h-8 text-blue-600"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($fullName); ?></h1>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($email); ?></p>
                        <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-md border border-gray-200">
                             <?php
                                 echo match ($role) {
                                     'ADMIN'    => 'ผู้ดูแลระบบ',
                                     'EMPLOYEE' => 'พนักงาน',
                                     default    => 'ลูกค้า',
                                 };
                             ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 bg-gray-50/50 p-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-white rounded shadow-sm text-gray-400">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">อีเมล</p>
                        <p class="font-medium text-gray-900 break-all"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                </div>

                <?php if ($phone): ?>
                <div class="flex items-start gap-3">
                    <div class="p-2 bg-white rounded shadow-sm text-gray-400">
                        <i data-lucide="phone" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">โทรศัพท์</p>
                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($phone); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="border-t border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">ภาพรวมบัญชี</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white border rounded-xl p-4 flex items-center gap-4 shadow-sm hover:shadow-md transition">
                        <div class="bg-blue-50 p-3 rounded-lg shrink-0">
                            <i data-lucide="calendar" class="w-6 h-6 text-blue-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-500">การจองทั้งหมด</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $totalBookings; ?></p>
                        </div>
                    </div>

                    <div class="bg-white border rounded-xl p-4 flex items-center gap-4 shadow-sm hover:shadow-md transition">
                        <div class="bg-green-50 p-3 rounded-lg shrink-0">
                            <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-500">ชำระเงินแล้ว</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $paidCount; ?></p>
                        </div>
                    </div>

                    <div class="bg-white border rounded-xl p-4 flex items-center gap-4 shadow-sm hover:shadow-md transition">
                        <div class="bg-yellow-50 p-3 rounded-lg shrink-0">
                            <i data-lucide="clock" class="w-6 h-6 text-yellow-600"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-500">รอชำระเงิน</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $unpaidCount; ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
