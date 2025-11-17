<?php
// pages/admin/sections/CustomersManagement.php
// หน้าจอจัดการลูกค้า (Admin only) - ใช้ข้อมูลจริงจาก API

require_once 'api/admin.php';

// ดึงข้อมูลลูกค้าจาก API
$customers = AdminService::getAllCustomers();
?>

<div>
    <h2 class="text-xl font-semibold mb-4">จัดการลูกค้า</h2>
    <p class="text-sm text-gray-600 mb-4">ค้นหา แก้ไข หรือดูประวัติลูกค้า</p>

    <?php if (empty($customers)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-yellow-800">ไม่พบข้อมูลลูกค้า</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3">รหัสลูกค้า</th>
                        <th class="px-4 py-3">ชื่อ-นามสกุล</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">โทรศัพท์</th>
                        <th class="px-4 py-3">สถานะ</th>
                        <th class="px-4 py-3">วันที่สมัคร</th>
                        <th class="px-4 py-3">การทำงาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($customers as $customer): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-sm">
                                <?php echo htmlspecialchars($customer['customerId'] ?? $customer['id']); ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php 
                                $fullName = ($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? '');
                                echo htmlspecialchars(trim($fullName) ?: 'ไม่ระบุชื่อ');
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars($customer['email'] ?? 'ไม่ระบุอีเมล'); ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php echo htmlspecialchars($customer['phone'] ?? 'ไม่ระบุ'); ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo ($customer['isActive'] ?? true) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ($customer['isActive'] ?? true) ? 'ใช้งาน' : 'ปิดใช้งาน'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php 
                                $createdAt = $customer['createdAt'] ?? $customer['registrationDate'] ?? '';
                                echo $createdAt ? date('d/m/Y', strtotime($createdAt)) : 'ไม่ระบุ';
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <button class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition mr-2">
                                    ดู
                                </button>
                                <button class="px-3 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600 transition" onclick="return confirm('ลบลูกค้านี้หรือไม่?')">
                                    ลบ
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>