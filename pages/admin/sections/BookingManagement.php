<?php
// pages/admin/sections/BookingManagement.php
// จัดการการจอง (Admin) - mock data stored in session for demo

// Initialize mock data in session if not present
if (!isset($_SESSION['mock_bookings'])) {
    $_SESSION['mock_bookings'] = [
        ['id'=>1,'customer'=>'สมชาย','motorcycle'=>'Wave 110i','start'=>'2025-11-01','end'=>'2025-11-03','status'=>'completed'],
        ['id'=>2,'customer'=>'สมหญิง','motorcycle'=>'Click 160','start'=>'2025-11-12','end'=>'2025-11-15','status'=>'active'],
        ['id'=>3,'customer'=>'อริสา','motorcycle'=>'PCX 160','start'=>'2025-11-20','end'=>'2025-11-22','status'=>'pending'],
    ];
}

$bookings = &$_SESSION['mock_bookings'];

// Handle actions (confirm / cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
    $action = $_POST['action'];
    $bid = (int)$_POST['booking_id'];
    foreach ($bookings as &$b) {
        if ($b['id'] === $bid) {
            if ($action === 'confirm') {
                $b['status'] = 'confirmed';
                $_SESSION['flash_message'] = ['type'=>'success','message'=>'ยืนยันการจอง #' . $bid];
            } elseif ($action === 'cancel') {
                $b['status'] = 'cancelled';
                $_SESSION['flash_message'] = ['type'=>'success','message'=>'ยกเลิกการจอง #' . $bid];
            }
            break;
        }
    }
    unset($b);
    // redirect to avoid form resubmission
    header('Location: index.php?page=admin&section=bookings');
    exit;
}
?>

<div>
    <h2 class="text-xl font-semibold mb-4">จัดการการจอง</h2>
    <p class="text-sm text-gray-600 mb-4">แสดงรายการการจองทั้งหมด แก้สถานะ หรือดูรายละเอียด (mock)</p>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">ลูกค้า</th>
                    <th class="px-4 py-3">รถ</th>
                    <th class="px-4 py-3">วันที่</th>
                    <th class="px-4 py-3">สถานะ</th>
                    <th class="px-4 py-3">การทำงาน</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($bookings as $b): ?>
                    <tr class="border-t">
                        <td class="px-4 py-3"><?php echo $b['id']; ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($b['customer']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($b['motorcycle']); ?></td>
                        <td class="px-4 py-3"><?php echo date('d/m/Y', strtotime($b['start'])); ?> - <?php echo date('d/m/Y', strtotime($b['end'])); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($b['status']); ?></td>
                        <td class="px-4 py-3">
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                <?php if ($b['status'] !== 'confirmed' && $b['status'] !== 'cancelled'): ?>
                                    <button name="action" value="confirm" class="px-3 py-1 bg-green-600 text-white rounded text-sm" onclick="return confirm('ยืนยันการจอง #<?php echo $b['id']; ?>?')">ยืนยัน</button>
                                    <button name="action" value="cancel" class="px-3 py-1 bg-red-500 text-white rounded text-sm" onclick="return confirm('ยกเลิกการจอง #<?php echo $b['id']; ?>?')">ยกเลิก</button>
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
</div>
