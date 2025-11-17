<?php
// pages/admin/sections/PaymentsPage.php
// จัดการชำระเงิน - ใช้ข้อมูลจริงจาก API

require_once 'api/admin.php';

// ดึงข้อมูลการจองทั้งหมดเพื่อแสดงข้อมูลการชำระเงิน
$bookings = AdminService::getAllReservations();

// กรองเฉพาะการจองที่มีข้อมูลการชำระเงิน
$payments = [];
foreach ($bookings as $booking) {
    if (isset($booking['paymentStatus']) || isset($booking['payment'])) {
        $payments[] = $booking;
    }
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $action = $_POST['action'];
    $bookingId = $_POST['booking_id'];
    
    if ($action === 'confirm_payment') {
        $success = AdminService::updatePaymentStatus($bookingId, 'paid');
        if ($success) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'ยืนยันการชำระเงินสำเร็จ'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'ไม่สามารถยืนยันการชำระเงินได้'];
        }
    }
    
    header('Location: index.php?page=admin&section=payments');
    exit;
}

// ฟังก์ชันช่วยในการแสดงสถานะการชำระเงิน
function getPaymentStatusBadge($status) {
    $statusMap = [
        'pending' => ['text' => 'รอชำระเงิน', 'class' => 'bg-yellow-100 text-yellow-800'],
        'paid' => ['text' => 'ชำระเงินแล้ว', 'class' => 'bg-green-100 text-green-800'],
        'failed' => ['text' => 'ชำระเงินไม่สำเร็จ', 'class' => 'bg-red-100 text-red-800'],
        'refunded' => ['text' => 'คืนเงินแล้ว', 'class' => 'bg-blue-100 text-blue-800']
    ];
    
    $statusInfo = $statusMap[$status] ?? ['text' => $status, 'class' => 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $statusInfo['class'] . '">' . $statusInfo['text'] . '</span>';
}
?>

<div>
    <h2 class="text-xl font-semibold mb-4">จัดการชำระเงิน</h2>
    <p class="text-sm text-gray-600 mb-4">ตรวจสอบและยืนยันสถานะการชำระเงิน</p>

    <?php if (empty($payments)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-yellow-800">ไม่พบข้อมูลการชำระเงิน</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">รหัสการจอง</th>
                        <th class="px-4 py-3">ลูกค้า</th>
                        <th class="px-4 py-3">ยอดชำระ</th>
                        <th class="px-4 py-3">วันที่ชำระ</th>
                        <th class="px-4 py-3">ช่องทาง</th>
                        <th class="px-4 py-3">สถานะ</th>
                        <th class="px-4 py-3">การทำงาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($payments as $payment): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-sm">
                                <?php echo htmlspecialchars($payment['reservationId'] ?? $payment['id']); ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php 
                                $customerName = '';
                                if (isset($payment['customerName'])) {
                                    $customerName = $payment['customerName'];
                                } else if (isset($payment['customer']['firstName'])) {
                                    $customerName = $payment['customer']['firstName'] . ' ' . ($payment['customer']['lastName'] ?? '');
                                }
                                echo htmlspecialchars($customerName ?: 'ไม่ระบุชื่อ');
                                ?>
                            </td>
                            <td class="px-4 py-3 font-semibold">
                                ฿<?php echo number_format($payment['totalAmount'] ?? $payment['amount'] ?? 0, 2); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php 
                                $paymentDate = $payment['paymentDate'] ?? $payment['createdAt'] ?? '';
                                echo $paymentDate ? date('d/m/Y H:i', strtotime($paymentDate)) : 'รอชำระเงิน';
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php 
                                $paymentMethod = $payment['paymentMethod'] ?? $payment['payment_method'] ?? 'ไม่ระบุ';
                                echo htmlspecialchars($paymentMethod);
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php 
                                $paymentStatus = $payment['paymentStatus'] ?? $payment['payment_status'] ?? 'pending';
                                echo getPaymentStatusBadge($paymentStatus);
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($paymentStatus === 'pending'): ?>
                                    <form method="post" style="display:inline-block;">
                                        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($payment['reservationId'] ?? $payment['id']); ?>">
                                        <button name="action" value="confirm_payment" 
                                                class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition"
                                                onclick="return confirm('ยืนยันการชำระเงิน #<?php echo $payment['reservationId'] ?? $payment['id']; ?>?')">
                                            ยืนยันชำระเงิน
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-sm text-gray-500">ดำเนินการแล้ว</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>