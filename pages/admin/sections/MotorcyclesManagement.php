<?php
// pages/admin/sections/MotorcyclesManagement.php
// หน้าจอจัดการรถเช่า (Admin) - mock data stored in session for demo

if (!isset($_SESSION['mock_motorcycles'])) {
    $_SESSION['mock_motorcycles'] = [
        ['id'=>1,'brand'=>'Honda','model'=>'Wave 110i','cc'=>110,'status'=>'available'],
        ['id'=>2,'brand'=>'Honda','model'=>'Click 160','cc'=>160,'status'=>'available'],
        ['id'=>3,'brand'=>'Kawasaki','model'=>'Ninja 400','cc'=>400,'status'=>'maintenance'],
    ];
}

$motorcycles = &$_SESSION['mock_motorcycles'];

// Handle actions: set_status, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['motorcycle_id'])) {
    $action = $_POST['action'];
    $mid = (int)$_POST['motorcycle_id'];
    foreach ($motorcycles as $i => &$m) {
        if ($m['id'] === $mid) {
            if ($action === 'set_available') {
                $m['status'] = 'available';
                $_SESSION['flash_message'] = ['type'=>'success','message'=>'ตั้งสถานะเป็น available ให้รถ #' . $mid];
            } elseif ($action === 'set_maintenance') {
                $m['status'] = 'maintenance';
                $_SESSION['flash_message'] = ['type'=>'success','message'=>'ตั้งสถานะเป็น maintenance ให้รถ #' . $mid];
            } elseif ($action === 'delete') {
                array_splice($motorcycles, $i, 1);
                $_SESSION['flash_message'] = ['type'=>'success','message'=>'ลบรถ #' . $mid];
            }
            break;
        }
    }
    unset($m);
    header('Location: index.php?page=admin&section=motorcycles');
    exit;
}
?>

<div>
    <h2 class="text-xl font-semibold mb-4">จัดการรถเช่า</h2>
    <p class="text-sm text-gray-600 mb-4">เพิ่ม/แก้ไข/ลบ รายการรถเช่า (mock)</p>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">ยี่ห้อ</th>
                    <th class="px-4 py-3">รุ่น</th>
                    <th class="px-4 py-3">CC</th>
                    <th class="px-4 py-3">สถานะ</th>
                    <th class="px-4 py-3">การทำงาน</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($motorcycles as $m): ?>
                    <tr class="border-t">
                        <td class="px-4 py-3"><?php echo $m['id']; ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($m['brand']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($m['model']); ?></td>
                        <td class="px-4 py-3"><?php echo $m['cc']; ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($m['status']); ?></td>
                        <td class="px-4 py-3">
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="motorcycle_id" value="<?php echo $m['id']; ?>">
                                <?php if ($m['status'] !== 'available'): ?>
                                    <button name="action" value="set_available" class="px-3 py-1 bg-green-600 text-white rounded text-sm">ตั้งเป็น available</button>
                                <?php endif; ?>
                                <?php if ($m['status'] !== 'maintenance'): ?>
                                    <button name="action" value="set_maintenance" class="px-3 py-1 bg-yellow-500 text-white rounded text-sm">ตั้งเป็น maintenance</button>
                                <?php endif; ?>
                                <button name="action" value="delete" class="px-3 py-1 bg-red-500 text-white rounded text-sm" onclick="return confirm('ลบรถ #<?php echo $m['id']; ?>?')">ลบ</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
