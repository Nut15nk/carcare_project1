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
                ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-3">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-900">
                                        รถจักรยานยนต์ #<?= htmlspecialchars($booking['motorcycleId'] ?? 'N/A'); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600">รหัสการจอง: <?= $booking['reservationId'] ?? 'N/A'; ?></p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $statusColor; ?>">
                                    <i data-lucide="<?= $statusIcon; ?>" class="h-4 w-4 mr-1"></i><?= $statusText; ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">📅 วันที่รับ</p>
                                    <p class="font-semibold"><?= $startDate ? $startDate->format('d/m/Y') : '-'; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">📅 วันที่คืน</p>
                                    <p class="font-semibold"><?= $endDate ? $endDate->format('d/m/Y') : '-'; ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">⏱️ จำนวนวัน</p>
                                    <p class="font-semibold"><?= $totalDays; ?> วัน</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">💰 ราคารวม</p>
                                    <p class="font-semibold text-blue-600">฿<?= number_format($booking['finalPrice'] ?? $booking['totalPrice'] ?? 0); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($booking['pickupLocation'])): ?>
                            <div class="mt-3">
                                <p class="text-sm text-gray-600">📍 สถานที่รับรถ</p>
                                <p class="font-semibold text-sm"><?= htmlspecialchars($booking['pickupLocation']); ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($booking['returnLocation'])): ?>
                            <div class="mt-3">
                                <p class="text-sm text-gray-600">📍 สถานที่คืนรถ</p>
                                <p class="font-semibold text-sm"><?= htmlspecialchars($booking['returnLocation']); ?></p>
                            </div>
                            <?php endif; ?>

                            <?php if ($payment): ?>
                            <div class="mt-3 pt-3 border-t border-gray-100 text-sm text-gray-600 flex flex-col sm:flex-row sm:items-center gap-2">
                                <div class="flex items-center gap-1">
                                    <i data-lucide="calendar" class="h-4 w-4"></i>
                                    <span>ชำระเงินเมื่อ: <?= date('d/m/Y H:i', strtotime($payment['paidAt'] ?? $payment['createdAt'])); ?></span>
                                </div>
                                <span class="hidden sm:inline">•</span>
                                <div class="flex items-center gap-1">
                                    <i data-lucide="credit-card" class="h-4 w-4"></i>
                                    <span>วิธีการ: <?= $payment['paymentMethod'] ?? 'N/A'; ?></span>
                                </div>
                                <span class="hidden sm:inline">•</span>
                                <div class="flex items-center gap-1">
                                    <i data-lucide="dollar-sign" class="h-4 w-4"></i>
                                    <span>จำนวน: ฿<?= number_format($payment['amount'] ?? 0); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-2 mt-4 md:mt-0">
                            <?php if (!$payment): ?>
                            <a href="index.php?page=payment&reservation=<?= $booking['reservationId']; ?>"
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                                <i data-lucide="credit-card" class="h-4 w-4"></i> ชำระเงิน
                            </a>
                            <?php else: ?>
                            <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                                <i data-lucide="check-circle" class="h-4 w-4"></i> ชำระแล้ว
                            </span>
                            <?php endif; ?>

                            <a href="index.php?page=booking-confirmation&reservation=<?= $booking['reservationId']; ?>"
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                                <i data-lucide="eye" class="h-4 w-4"></i> ดูรายละเอียด
                            </a>
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
