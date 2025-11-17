<?php
// pages/admin/sections/BookingManagement.php
// จัดการการจอง (Admin) - ใช้ข้อมูลจริงจาก API

require_once 'api/admin.php';

// ดึงข้อมูลการจองทั้งหมดจาก API
$bookings = AdminService::getAllReservations();

// Handle actions (confirm / cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $action = $_POST['action'];
    $bookingId = $_POST['booking_id'];
    
    if ($action === 'confirm') {
        $success = AdminService::updateReservationStatus($bookingId, 'confirmed');
        if ($success) {
            $_SESSION['flash_message'] = ['type'=>'success','message'=>'ยืนยันการจอง #' . $bookingId];
        } else {
            $_SESSION['flash_message'] = ['type'=>'error','message'=>'ไม่สามารถยืนยันการจองได้'];
        }
    } elseif ($action === 'cancel') {
        $success = AdminService::updateReservationStatus($bookingId, 'cancelled');
        if ($success) {
            $_SESSION['flash_message'] = ['type'=>'success','message'=>'ยกเลิกการจอง #' . $bookingId];
        } else {
            $_SESSION['flash_message'] = ['type'=>'error','message'=>'ไม่สามารถยกเลิกการจองได้'];
        }
    }
    
    // redirect to avoid form resubmission
    header('Location: index.php?page=admin&section=bookings');
    exit;
}

// ฟังก์ชันช่วยในการแสดงสถานะเป็นภาษาไทย
function getStatusBadge($status) {
    $statusMap = [
        'pending' => ['text' => 'รอดำเนินการ', 'class' => 'bg-yellow-100 text-yellow-800'],
        'confirmed' => ['text' => 'ยืนยันแล้ว', 'class' => 'bg-blue-100 text-blue-800'],
        'active' => ['text' => 'กำลังใช้งาน', 'class' => 'bg-green-100 text-green-800'],
        'completed' => ['text' => 'เสร็จสิ้น', 'class' => 'bg-gray-100 text-gray-800'],
        'cancelled' => ['text' => 'ยกเลิก', 'class' => 'bg-red-100 text-red-800']
    ];
    
    $statusInfo = $statusMap[$status] ?? ['text' => $status, 'class' => 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $statusInfo['class'] . '">' . $statusInfo['text'] . '</span>';
}
?>

<div>
    <h2 class="text-xl font-semibold mb-4">จัดการการจอง</h2>
    <p class="text-sm text-gray-600 mb-4">แสดงรายการการจองทั้งหมด แก้สถานะ หรือดูรายละเอียด</p>

    <?php if (empty($bookings)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <p class="text-yellow-800">ไม่พบข้อมูลการจอง</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">รหัสการจอง</th>
                        <th class="px-4 py-3">ลูกค้า</th>
                        <th class="px-4 py-3">รถ</th>
                        <th class="px-4 py-3">วันที่</th>
                        <th class="px-4 py-3">จำนวนวัน</th>
                        <th class="px-4 py-3">ยอดรวม</th>
                        <th class="px-4 py-3">สถานะ</th>
                        <th class="px-4 py-3">การทำงาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bookings as $booking): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-sm">
                                <?php echo htmlspecialchars($booking['reservationId'] ?? $booking['id']); ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php 
                                $customerName = '';
                                if (isset($booking['customerName'])) {
                                    $customerName = $booking['customerName'];
                                } else if (isset($booking['customer']['firstName'])) {
                                    $customerName = $booking['customer']['firstName'] . ' ' . ($booking['customer']['lastName'] ?? '');
                                }
                                echo htmlspecialchars($customerName ?: 'ไม่ระบุชื่อ');
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php 
                                $motorcycleInfo = '';
                                if (isset($booking['motorcycleBrand']) && isset($booking['motorcycleModel'])) {
                                    $motorcycleInfo = $booking['motorcycleBrand'] . ' ' . $booking['motorcycleModel'];
                                } else if (isset($booking['motorcycle']['brand']) && isset($booking['motorcycle']['model'])) {
                                    $motorcycleInfo = $booking['motorcycle']['brand'] . ' ' . $booking['motorcycle']['model'];
                                }
                                echo htmlspecialchars($motorcycleInfo ?: 'ไม่ระบุรถ');
                                ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php 
                                $startDate = $booking['startDate'] ?? $booking['start'];
                                $endDate = $booking['endDate'] ?? $booking['end'];
                                echo date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));
                                ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php 
                                $days = $booking['totalDays'] ?? 0;
                                echo $days . ' วัน';
                                ?>
                            </td>
                            <td class="px-4 py-3 font-semibold">
                                ฿<?php echo number_format($booking['totalAmount'] ?? $booking['amount'] ?? 0, 2); ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php echo getStatusBadge($booking['status']); ?>
                            </td>
                            <td class="px-4 py-3">
                                <form method="post" style="display:inline-block;">
                                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['reservationId'] ?? $booking['id']); ?>">
                                    <?php if (in_array($booking['status'], ['pending', 'confirmed'])): ?>
                                        <button name="action" value="confirm" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition" onclick="return confirm('ยืนยันการจอง #<?php echo $booking['reservationId'] ?? $booking['id']; ?>?')">
                                            ยืนยัน
                                        </button>
                                        <button name="action" value="cancel" class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition" onclick="return confirm('ยกเลิกการจอง #<?php echo $booking['reservationId'] ?? $booking['id']; ?>?')">
                                            ยกเลิก
                                        </button>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-500">เสร็จสิ้น</span>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>