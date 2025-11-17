<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// โหลดไฟล์ API - ใช้ระบบ path array
$configPaths = [
    __DIR__ . '/../api/config.php',
    __DIR__ . '/../../api/config.php',
    'api/config.php',
];

$configLoaded = false;
foreach ($configPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    die('ไม่พบไฟล์ config.php');
}

// โหลด bookings.php
$bookingPaths = [
    __DIR__ . '/../api/bookings.php',
    __DIR__ . '/../../api/bookings.php',
    'api/bookings.php',
];

$bookingLoaded = false;
foreach ($bookingPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $bookingLoaded = true;
        break;
    }
}

if (!$bookingLoaded) {
    die('ไม่พบไฟล์ bookings.php');
}

// โหลด payments.php
$paymentPaths = [
    __DIR__ . '/../api/payments.php',
    __DIR__ . '/../../api/payments.php',
    'api/payments.php',
];

$paymentLoaded = false;
foreach ($paymentPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $paymentLoaded = true;
        break;
    }
}

if (!$paymentLoaded) {
    die('ไม่พบไฟล์ payments.php');
}

$customerId = $_SESSION['user']['userId'] ?? $_SESSION['user_id'] ?? '';
$bookings = [];
$payments = [];

if ($customerId) {
    $bookings = BookingService::getCustomerBookings($customerId);
    
    // ✅ แก้ไข: ดึงข้อมูล payment สำหรับแต่ละ booking
    $paymentMap = [];
    foreach ($bookings as $booking) {
        $reservationId = $booking['reservationId'];
        $payment = PaymentService::getPaymentByReservation($reservationId);
        
        // ✅ เพิ่ม debugging
        error_log("Checking payment for reservation: " . $reservationId);
        error_log("Payment data: " . print_r($payment, true));
        
        if ($payment) {
            $paymentMap[$reservationId] = $payment;
        }
    }
}

// เรียงการจองใหม่ล่าสุดขึ้นก่อน
usort($bookings, function ($a, $b) {
    return strtotime($b['createdAt'] ?? '') - strtotime($a['createdAt'] ?? '');
});
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">การจองของฉัน</h1>
            <p class="text-lg text-gray-600">จัดการการจองรถจักรยานยนต์ทั้งหมดของคุณ</p>
        </div>

        <?php if (empty($bookings)): ?>
            <!-- Empty State -->
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="flex justify-center mb-4">
                    <i data-lucide="calendar" class="h-16 w-16 text-gray-400"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">ยังไม่มีการจอง</h3>
                <p class="text-gray-600 mb-6">เริ่มต้นการจองรถจักรยานยนต์ครั้งแรกของคุณ</p>
                <a href="index.php?page=motorcycles"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2">
                    <i data-lucide="bike" class="h-5 w-5"></i>
                    จองรถเลย
                </a>
            </div>
        <?php else: ?>
            <!-- Bookings List -->
            <div class="space-y-6">
                <?php foreach ($bookings as $booking):
                    $payment = $paymentMap[$booking['reservationId']] ?? null;
                    $startDate = new DateTime($booking['startDate']);
                    $endDate = new DateTime($booking['endDate']);
                    $totalDays = $endDate->diff($startDate)->days;

                    // ✅ กำหนดสถานะตาม payment status
                    if ($payment) {
                        $status = 'paid';
                        $statusText = 'ชำระเงินแล้ว';
                        $statusColor = 'bg-green-100 text-green-800';
                    } else {
                        $status = 'pending';
                        $statusText = 'รอชำระเงิน';
                        $statusColor = 'bg-yellow-100 text-yellow-800';
                    }
                ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                        <!-- Booking Info -->
                        <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-900">
                                        รถจักรยานยนต์ #<?php echo htmlspecialchars($booking['motorcycleId'] ?? 'N/A'); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600">รหัสการจอง: <?php echo $booking['reservationId']; ?></p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">วันที่รับ</p>
                                    <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['startDate'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">วันที่คืน</p>
                                    <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['endDate'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">จำนวนวัน</p>
                                    <p class="font-semibold"><?php echo $totalDays; ?> วัน</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">ราคารวม</p>
                                    <p class="font-semibold text-blue-600">฿<?php echo number_format($booking['finalPrice'] ?? $booking['totalPrice'] ?? 0); ?></p>
                                </div>
                            </div>

                            <!-- ✅ เพิ่มสถานที่รับรถ -->
                            <?php if ($booking['pickupLocation']): ?>
                            <div class="mt-3">
                                <p class="text-sm text-gray-600">สถานที่รับรถ</p>
                                <p class="font-semibold text-sm"><?php echo htmlspecialchars($booking['pickupLocation']); ?></p>
                            </div>
                            <?php endif; ?>

                            <!-- ✅ สถานที่คืนรถ (ของเดิม) -->
                            <?php if ($booking['returnLocation']): ?>
                            <div class="mt-3">
                                <p class="text-sm text-gray-600">สถานที่คืนรถ</p>
                                <p class="font-semibold text-sm"><?php echo htmlspecialchars($booking['returnLocation']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row gap-2">
                            <?php if (!$payment): ?>
                            <a href="index.php?page=payment&reservation=<?php echo $booking['reservationId']; ?>"
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="credit-card" class="h-4 w-4"></i>
                                ชำระเงิน
                            </a>
                            <?php else: ?>
                            <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                                <i data-lucide="check-circle" class="h-4 w-4"></i>
                                ชำระแล้ว
                            </span>
                            <?php endif; ?>

                            <a href="index.php?page=booking-confirmation&reservation=<?php echo $booking['reservationId']; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                                ดูรายละเอียด
                            </a>
                        </div>
                    </div>

                    <!-- Payment Info -->
                    <?php if ($payment): ?>
                    <div class="border-t mt-4 pt-4">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <i data-lucide="calendar" class="h-4 w-4"></i>
                            <span>ชำระเงินเมื่อ: <?php echo date('d/m/Y H:i', strtotime($payment['paidAt'] ?? $payment['createdAt'])); ?></span>
                            <span class="mx-2">•</span>
                            <i data-lucide="credit-card" class="h-4 w-4"></i>
                            <span>วิธีการ: <?php echo $payment['paymentMethod'] ?? 'N/A'; ?></span>
                            <span class="mx-2">•</span>
                            <i data-lucide="dollar-sign" class="h-4 w-4"></i>
                            <span>จำนวน: ฿<?php echo number_format($payment['amount'] ?? 0); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // เปิดใช้งาน Lucide icons
    lucide.createIcons();
</script>