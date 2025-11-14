<?php
// pages/admin/sections/PaymentsPage.php
// (ไฟล์นี้จะถูก include โดย AdminRouter.php)

$payments = [];
$error = '';

try {
    // (1) ดึงข้อมูลจาก API (เหมือน fetch() ใน React)
    // (ใน PHP เราใช้ file_get_contents แทน)
    $apiUrl = "http://localhost/project-api/get_payments.php";
    
    // ตั้งค่า timeout เผื่อ API ช้า
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $json_data = @file_get_contents($apiUrl, false, $context);
    
    if ($json_data === false) {
        throw new Exception("ไม่สามารถเชื่อมต่อ API ได้ ($apiUrl)");
    }

    $payments = json_decode($json_data, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("ข้อมูลที่ได้รับจาก API ไม่ใช่ JSON");
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

?>

<!-- (2) เริ่ม HTML ของ "การชำระเงิน" -->
<div class="p-0"> <!-- p-6 ถูกควบคุมโดย AdminRouter แล้ว -->
    <h2 class="text-2xl font-bold mb-4 text-gray-900">จัดการการชำระเงิน</h2>

    <?php if (!empty($error)): ?>
        <!-- (3) แสดง Error ถ้า API ล้มเหลว -->
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <strong>เกิดข้อผิดพลาด:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php elseif (empty($payments)): ?>
        <!-- (4) แสดงผลถ้าไม่มีข้อมูล -->
        <div class="bg-white p-6 rounded-lg shadow text-center text-gray-500">
            ไม่พบข้อมูลการชำระเงิน
        </div>
    <?php else: ?>
        <!-- (5) แสดงตาราง (เหมือนใน React) -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="w-full border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border-b px-4 py-2 text-left">Booking ID</th>
                        <th class="border-b px-4 py-2 text-left">Customer ID</th>
                        <th class="border-b px-4 py-2 text-left">Amount</th>
                        <th class="border-b px-4 py-2 text-left">Date</th>
                        <th class="border-b px-4 py-2 text-left">Method</th>
                        <th class="border-b px-4 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border-b px-4 py-2"><?php echo htmlspecialchars($p['booking_id']); ?></td>
                            <td class="border-b px-4 py-2"><?php echo htmlspecialchars($p['customer_id']); ?></td>
                            <td class="border-b px-4 py-2"><?php echo htmlspecialchars($p['amount']); ?></td>
                            <td class="border-b px-4 py-2"><?php echo htmlspecialchars($p['payment_date']); ?></td>
                            <td class="border-b px-4 py-2"><?php echo htmlspecialchars($p['method']); ?></td>
                            <td class="border-b px-4 py-2"><?php echo htmlspecialchars($p['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>