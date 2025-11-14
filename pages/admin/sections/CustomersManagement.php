<?php
// pages/admin/sections/CustomersManagement.php
// หน้าจอจัดการลูกค้า (Admin only) - จำลองข้อมูล
$customers = [
    ['id'=>301,'name'=>'สมชาย','email'=>'somchai@example.com','phone'=>'0891111111'],
    ['id'=>302,'name'=>'สมหญิง','email'=>'somying@example.com','phone'=>'0892222222'],
    ['id'=>303,'name'=>'อริสา','email'=>'arisa@example.com','phone'=>'0893333333'],
];
?>

<div>
    <h2 class="text-xl font-semibold mb-4">จัดการลูกค้า</h2>
    <p class="text-sm text-gray-600 mb-4">ค้นหา แก้ไข หรือดูประวัติลูกค้า</p>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">ชื่อ</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">โทรศัพท์</th>
                    <th class="px-4 py-3">การทำงาน</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($customers as $c): ?>
                    <tr class="border-t">
                        <td class="px-4 py-3"><?php echo $c['id']; ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($c['name']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($c['email']); ?></td>
                        <td class="px-4 py-3"><?php echo htmlspecialchars($c['phone']); ?></td>
                        <td class="px-4 py-3">
                            <button class="px-3 py-1 bg-blue-600 text-white rounded text-sm">ดู</button>
                            <button class="px-3 py-1 bg-red-500 text-white rounded text-sm">ลบ</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
