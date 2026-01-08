<?php
    // pages/admin/sections/CustomersManagement.php

    require_once __DIR__ . '/../../../service/Admin/AdminService.php';
    use Service\Admin\AdminService;

    // ดึงข้อมูลลูกค้าจาก Service
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
                    <?php foreach ($customers as $customer): ?>
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
                                <span class="px-2 py-1 text-xs font-semibold rounded-full                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  <?php echo($customer['isActive'] ?? true) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo($customer['isActive'] ?? true) ? 'ใช้งาน' : 'ปิดใช้งาน'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php
                                    $createdAt = $customer['createdAt'] ?? $customer['registrationDate'] ?? '';
                                    echo $createdAt ? date('d/m/Y', strtotime($createdAt)) : 'ไม่ระบุ';
                                ?>
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700"
                                    onclick="openCustomerModal('<?php echo $customer['customerId']; ?>')">
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

<!-- CUSTOMER MODAL -->
<div id="customer-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center overflow-auto">
  <div class="bg-white max-w-3xl w-full mt-20 rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">ข้อมูลลูกค้า</h3>

    <div id="customer-detail" class="space-y-3 text-sm"></div>

    <h4 class="font-semibold mt-6 mb-2">ประวัติการจอง</h4>
    <div id="customer-reservations" class="text-sm"></div>

    <div class="text-right mt-6">
      <button onclick="closeCustomerModal()" class="px-4 py-2 bg-gray-300 rounded">
        ปิด
      </button>
    </div>
  </div>
</div> <!-- ⭐⭐⭐ สำคัญ: ปิด modal ชั้นนอก -->

<script>
function openCustomerModal(customerId) {
    fetch('api/admin/customer_detail.php?id=' + customerId)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            const p = data.profile;

            document.getElementById('customer-detail').innerHTML = `
                <div><b>ชื่อ:</b> ${p.firstName} ${p.lastName}</div>
                <div><b>Email:</b> ${p.email}</div>
                <div><b>โทร:</b> ${p.phone}</div>
                <div><b>ที่อยู่:</b> ${p.address ?? '-'}</div>
                <div><b>ใบขับขี่:</b> ${p.licenseNumber ?? '-'}</div>
                <div><b>ยืนยันตัวตน:</b> ${p.isVerified == 1 ? '✔ ยืนยันแล้ว' : '✖ ยังไม่ยืนยัน'}</div>
            `;

            const reservations = data.reservations;
            document.getElementById('customer-reservations').innerHTML =
                reservations.length
                    ? reservations.map(r => `
                        <div class="border-b py-2">
                            <div><b>รถ:</b> ${r.brand} ${r.model}</div>
                            <div><b>วันที่:</b> ${r.start_date} ถึง ${r.end_date}</div>
                            <div><b>สถานะ:</b> ${r.status}</div>
                            <div><b>การชำระเงิน:</b> ${r.payment_status ?? 'ยังไม่จ่าย'}</div>
                        </div>
                      `).join('')
                    : 'ไม่มีประวัติการจอง';

            document.getElementById('customer-modal').classList.remove('hidden');
        })
        .catch(err => {
            console.error(err);
            alert('โหลดข้อมูลไม่สำเร็จ');
        });
}

function closeCustomerModal() {
    document.getElementById('customer-modal').classList.add('hidden');
}
</script>