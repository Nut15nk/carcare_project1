<?php
// pages/admin/sections/EmployeeDashboard.php
// หน้าแดชบอร์ดสำหรับพนักงาน (จำลอง)

// จำลองข้อมูลงานที่ถูกมอบหมายให้พนักงาน
$assignedTasks = [
    ['id'=>101, 'title'=>'ตรวจสอบรถที่จอดไว้', 'due'=>date('Y-m-d', strtotime('+0 days')), 'status'=>'pending'],
    ['id'=>102, 'title'=>'เตรียมรถสำหรับลูกค้า A', 'due'=>date('Y-m-d', strtotime('+1 days')), 'status'=>'in-progress'],
    ['id'=>103, 'title'=>'ตรวจเช็คสภาพเครื่องยนต์', 'due'=>date('Y-m-d', strtotime('+2 days')), 'status'=>'pending'],
];

$todayShifts = [
    ['shift'=>'เช้า', 'start'=>'08:00', 'end'=>'12:00'],
    ['shift'=>'บ่าย', 'start'=>'13:00', 'end'=>'17:00'],
];

$assignedBookings = [
    ['id'=>2, 'customer'=>'สมชาย', 'motorcycle'=>'Honda Click 160', 'start'=>date('Y-m-d', strtotime('+0 days')), 'status'=>'active'],
    ['id'=>3, 'customer'=>'สมหญิง', 'motorcycle'=>'PCX 160', 'start'=>date('Y-m-d', strtotime('+1 days')), 'status'=>'pending'],
];
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">แดชบอร์ดพนักงาน</h1>
        <p class="text-gray-600">ยินดีต้อนรับ <?php echo htmlspecialchars($_SESSION['user_email'] ?? 'พนักงาน'); ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-3">กะวันนี้</h3>
            <?php foreach($todayShifts as $s): ?>
                <div class="mb-2">
                    <div class="text-sm font-medium"><?php echo $s['shift']; ?></div>
                    <div class="text-xs text-gray-500"><?php echo $s['start']; ?> - <?php echo $s['end']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-3">งานที่มอบหมาย</h3>
            <?php if (empty($assignedTasks)): ?>
                <p class="text-gray-500">ไม่มีงาน</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach($assignedTasks as $t): ?>
                        <li class="p-3 border rounded flex justify-between items-start">
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($t['title']); ?></div>
                                <div class="text-xs text-gray-500">กำหนด: <?php echo date('d/m/Y', strtotime($t['due'])); ?></div>
                            </div>
                            <div class="text-sm">
                                <?php if ($t['status'] === 'pending'): ?>
                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">รอดำเนินการ</span>
                                <?php elseif ($t['status'] === 'in-progress'): ?>
                                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">กำลังทำ</span>
                                <?php else: ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">เสร็จแล้ว</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold mb-3">การจองที่ถูกมอบหมาย</h3>
            <?php if (empty($assignedBookings)): ?>
                <p class="text-gray-500">ไม่มีการจอง</p>
            <?php else: ?>
                <ul class="space-y-2">
                    <?php foreach($assignedBookings as $b): ?>
                        <li class="p-3 border rounded flex justify-between items-start">
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($b['customer']); ?> — <?php echo htmlspecialchars($b['motorcycle']); ?></div>
                                <div class="text-xs text-gray-500">วันที่เริ่ม: <?php echo date('d/m/Y', strtotime($b['start'])); ?></div>
                            </div>
                            <div class="text-sm">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">รอการยืนยัน</span>
                                <?php elseif ($b['status'] === 'active'): ?>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">กำลังเช่า</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
