<?php
// pages/MyBookings.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// โหลด Services
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../service/BookingService.php';
require_once __DIR__ . '/../service/PaymentService.php';
require_once __DIR__ . '/../service/MotorcycleService.php';

$customerId = $_SESSION['user']['userId'] ?? $_SESSION['user_id'] ?? '';
$bookings = [];
$paymentMap = [];

if ($customerId) {
    // ดึงการจองของลูกค้า
    $bookings = BookingService::getCustomerBookings($customerId);

    // ดึง payment สำหรับแต่ละ booking
    foreach ($bookings as $booking) {
        $reservationId = $booking['reservationId'] ?? null;
        if ($reservationId) {
            $payment = PaymentService::getPaymentByReservation($reservationId);
            if ($payment) {
                $paymentMap[$reservationId] = $payment;
            }
        }
    }
}

// เรียงการจองล่าสุดขึ้นก่อน
usort($bookings, function ($a, $b) {
    return strtotime($b['createdAt'] ?? '') - strtotime($a['createdAt'] ?? '');
});

$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']);
}
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">การจองของฉัน</h1>
        <p class="text-lg text-gray-600 mb-6">จัดการการจองรถจักรยานยนต์ทั้งหมดของคุณ</p>

        <?php if (empty($bookings)): ?>
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
            <div class="space-y-4">
                <?php foreach ($bookings as $booking):
    $payment = $paymentMap[$booking['reservationId']] ?? null;

    // วันที่
    $startDate = !empty($booking['startDate']) ? new DateTime($booking['startDate']) : null;
    $endDate   = !empty($booking['endDate']) ? new DateTime($booking['endDate']) : null;
    $totalDays = ($startDate && $endDate) ? $endDate->diff($startDate)->days : 0;

    // สถานะ
    $statusText  = $payment ? 'ชำระเงินแล้ว' : 'รอชำระเงิน';
    $statusColor = $payment ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
    $statusIcon  = $payment ? 'check-circle' : 'clock';
    
    // ชื่อรถ (ยี่ห้อ + รุ่น)
    $carName = !empty($booking['brand']) && !empty($booking['model']) 
               ? $booking['brand'] . ' ' . $booking['model'] 
               : 'รถจักรยานยนต์ #' . ($booking['motorcycleId'] ?? '-');
?>
<div class="bg-white rounded-lg shadow-lg p-6 mb-4 hover:shadow-xl transition-shadow duration-200">
    <div class="flex flex-col md:flex-row gap-6">
        
        <?php if (!empty($booking['imageUrl'])): ?>
        <div class="w-full md:w-48 h-32 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
            <img src="<?= htmlspecialchars($booking['imageUrl']); ?>" 
                 alt="<?= htmlspecialchars($carName); ?>" 
                 class="w-full h-full object-cover">
        </div>
        <?php endif; ?>

        <div class="flex-1">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h3 class="font-bold text-xl text-blue-900">
                        <?= htmlspecialchars($carName); ?>
                    </h3>
                    <p class="text-sm text-gray-500">รหัสการจอง: #<?= htmlspecialchars($booking['reservationId'] ?? 'N/A'); ?></p>
                </div>
                
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium whitespace-nowrap shrink-0 <?= $statusColor; ?>">
                    <i data-lucide="<?= $statusIcon; ?>" class="h-4 w-4 mr-1"></i><?= $statusText; ?>
                </span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm bg-gray-50 p-3 rounded-md mb-4">
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold">วันรับรถ</p>
                    <p class="font-medium text-gray-900"><?= $startDate ? $startDate->format('d/m/Y') : '-'; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold">วันคืนรถ</p>
                    <p class="font-medium text-gray-900"><?= $endDate ? $endDate->format('d/m/Y') : '-'; ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold">ระยะเวลา</p>
                    <p class="font-medium text-gray-900"><?= $totalDays; ?> วัน</p>
                </div>

                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold">สถานที่รับรถ</p>
                    <p class="font-medium text-gray-900" title="<?= htmlspecialchars($booking['pickupLocation'] ?? '-'); ?>">
                        <?= htmlspecialchars($booking['pickupLocation'] ?? '-'); ?>
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold">สถานที่คืนรถ</p>
                    <p class="font-medium text-gray-900" title="<?= htmlspecialchars($booking['returnLocation'] ?? '-'); ?>">
                        <?= htmlspecialchars($booking['returnLocation'] ?? '-'); ?>
                    </p>
                </div>
                
                <div>
                    <p class="text-gray-500 text-xs uppercase font-semibold">ราคารวม</p>
                    <p class="font-bold text-blue-600 text-lg">฿<?= number_format($booking['finalPrice'] ?? $booking['totalPrice'] ?? 0); ?></p>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-2 border-t border-gray-100">
                <?php if (!$payment && $booking['status'] !== 'cancelled'): ?>
                <a href="index.php?page=payment&reservation=<?= $booking['reservationId']; ?>"
                   class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                    <i data-lucide="credit-card" class="h-4 w-4"></i> ชำระเงินทันที
                </a>
                <?php endif; ?>

                <a href="index.php?page=booking-confirmation&reservation=<?= $booking['reservationId']; ?>"
                   class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                    <i data-lucide="file-text" class="h-4 w-4"></i> ดูรายละเอียด
                </a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>