<?php
// pages/admin/sections/BookingManagement.php
// จัดการการจอง + การชำระเงิน (Admin)

session_start();

require_once __DIR__ . '/../../../service/Admin/AdminService.php';
use Service\Admin\AdminService;

/**
 * 🔥 AUTO CANCEL
 * ถ้าวันนี้ >= start_date และยังไม่ชำระ → cancel
 */
AdminService::autoCancelExpiredUnpaidBookings();

// ดึงข้อมูลการจองทั้งหมด (รวม payment)
$bookings = AdminService::getAllReservationsWithPayment();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $action    = $_POST['action'];
    $bookingId = $_POST['booking_id'];

    try {
        switch ($action) {

            case 'confirm_booking':
                // ✅ ใช้ตัวนี้เท่านั้น (เช็ค payment จริง)
                AdminService::confirmBooking($bookingId);
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'อนุมัติการจอง #' . $bookingId
                ];
                break;

            case 'cancel_booking':
                AdminService::updateReservationStatus($bookingId, 'cancelled');
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'ยกเลิกการจอง #' . $bookingId
                ];
                break;
        }
    } catch (Throwable $e) {
        $_SESSION['flash_message'] = [
            'type' => 'error',
            'message' => $e->getMessage()
        ];
    }

    header('Location: index.php?page=admin&section=bookings');
    exit;
}

// badge helper (UI เดิม)
function badge($text, $class)
{
    return "<span class='px-2 py-1 text-xs font-semibold rounded-full {$class}'>{$text}</span>";
}
?>

<div>
    <h2 class="text-xl font-semibold mb-4">จัดการการจอง</h2>
    <p class="text-sm text-gray-600 mb-4">จัดการการจองและการชำระเงินในหน้าเดียว</p>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
            <?php echo $_SESSION['flash_message']['message']; ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3">รหัส</th>
                    <th class="px-4 py-3">ลูกค้า</th>
                    <th class="px-4 py-3">รถ</th>
                    <th class="px-4 py-3">วันที่</th>
                    <th class="px-4 py-3">ยอด</th>
                    <th class="px-4 py-3">การจอง</th>
                    <th class="px-4 py-3">การชำระเงิน</th>
                    <th class="px-4 py-3">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-sm"><?php echo $b['reservationId']; ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($b['customerName']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($b['motorcycle']); ?></td>
                        <td class="px-4 py-3 text-sm">
                            <?php
                            echo date('d/m/Y', strtotime($b['startDate'])) .
                                 ' - ' .
                                 date('d/m/Y', strtotime($b['endDate']));
                            ?>
                        </td>
                        <td class="px-4 py-3 font-semibold">
                            ฿<?php echo number_format($b['totalAmount'], 2); ?>
                        </td>

                        <!-- Reservation status -->
                        <td class="px-4 py-3">
                            <?php
                            echo match ($b['status']) {
                                'pending'   => badge('รอดำเนินการ', 'bg-yellow-100 text-yellow-800'),
                                'confirmed' => badge('ยืนยันแล้ว', 'bg-blue-100 text-blue-800'),
                                'completed' => badge('เสร็จสิ้น', 'bg-gray-100 text-gray-800'),
                                'cancelled' => badge('ยกเลิก', 'bg-red-100 text-red-800'),
                                default     => badge($b['status'], 'bg-gray-100')
                            };
                            ?>
                        </td>

                        <!-- Payment status -->
                        <td class="px-4 py-3">
                            <?php
                            echo $b['payment_status'] === 'paid'
                                ? badge('ชำระแล้ว', 'bg-green-100 text-green-800')
                                : badge('ยังไม่ชำระ', 'bg-yellow-100 text-yellow-800');
                            ?>
                        </td>

                        <!-- Actions -->
                        <td class="px-4 py-3">
                            <?php if (!in_array($b['status'], ['completed', 'cancelled'])): ?>
                                <form method="post" class="inline-flex space-x-1">
                                    <input type="hidden" name="booking_id"
                                           value="<?php echo $b['reservationId']; ?>">

                                    <?php if (
                                        $b['status'] === 'pending'
                                        && $b['payment_status'] === 'paid'
                                    ): ?>
                                        <button name="action" value="confirm_booking"
                                                class="px-2 py-1 bg-green-600 text-white rounded text-sm">
                                            อนุมัติ
                                        </button>
                                    <?php endif; ?>

                                    <button name="action" value="cancel_booking"
                                            class="px-2 py-1 bg-red-500 text-white rounded text-sm">
                                        ยกเลิก
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-sm text-gray-500">เสร็จสิ้น</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
