<?php
// pages/admin/sections/ReportsPage.php
// หน้าแสดงรายงาน (Admin only) - จำลองข้อมูล
$reportSummary = [
    'totalBookings' => 125,
    'totalRevenue' => 256000,
    'topMotorcycle' => 'PCX 160',
    'activeCustomers' => 98,
];
?>

<div>
    <h2 class="text-xl font-semibold mb-4">รายงานสรุป</h2>
    <p class="text-sm text-gray-600 mb-4">รายงานสรุปการดำเนินงาน (จำลอง)</p>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">การจองทั้งหมด</p>
            <p class="text-2xl font-bold"><?php echo $reportSummary['totalBookings']; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">รายได้รวม</p>
            <p class="text-2xl font-bold">฿<?php echo number_format($reportSummary['totalRevenue']); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">ลูกค้าที่ใช้งาน</p>
            <p class="text-2xl font-bold"><?php echo $reportSummary['activeCustomers']; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">รถยอดนิยม</p>
            <p class="text-2xl font-bold"><?php echo htmlspecialchars($reportSummary['topMotorcycle']); ?></p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <h3 class="font-semibold mb-2">รายละเอียดเพิ่มเติม (จำลอง)</h3>
        <p class="text-sm text-gray-600">กราฟและเนื้อหาจริงจะถูกสร้างจากฐานข้อมูลในระบบจริง</p>
    </div>
</div>
