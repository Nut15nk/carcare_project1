<?php
require_once __DIR__ . '/../../../service/Admin/AdminService.php';
use Service\Admin\AdminService;

/* ================= LOAD DATA ================= */

$stats            = AdminService::getDashboardStats();
$revenueReport    = AdminService::getRevenueReport('monthly');
$recentActivities = AdminService::getRecentActivities(10);
$allReservations  = AdminService::getAllReservations();
$allCustomers     = AdminService::getAllCustomers();

/* ================= CALCULATE ================= */

$totalBookings = $stats['totalBookings'] ?? 0;
$totalRevenue  = $stats['totalRevenue'] ?? 0;

$activeCustomers = count(array_filter($allCustomers, fn($c) => ($c['isActive'] ?? 1) == 1));

$motorcycleCount = [];
foreach ($allReservations as $r) {
    $key = $r['brand'] . ' ' . $r['model'];
    $motorcycleCount[$key] = ($motorcycleCount[$key] ?? 0) + 1;
}

arsort($motorcycleCount);
$topMotorcycle = key($motorcycleCount) ?: 'ยังไม่มีข้อมูล';
?>

<div class="space-y-6">

<h1 class="text-2xl font-bold">รายงานและสถิติ</h1>

<!-- SUMMARY -->
<div class="grid grid-cols-4 gap-4">
    <div class="bg-white p-4 shadow rounded">การจองทั้งหมด<br><b><?= $totalBookings ?></b></div>
    <div class="bg-white p-4 shadow rounded">รายได้รวม<br><b>฿<?= number_format($totalRevenue) ?></b></div>
    <div class="bg-white p-4 shadow rounded">ลูกค้าใช้งาน<br><b><?= $activeCustomers ?></b></div>
    <div class="bg-white p-4 shadow rounded">รถยอดนิยม<br><b><?= htmlspecialchars($topMotorcycle) ?></b></div>
</div>

<!-- REVENUE -->
<div class="bg-white p-6 shadow rounded">
<h2 class="font-semibold mb-2">รายได้รายเดือน</h2>
<div class="flex gap-2 items-end h-40">
<?php foreach ($revenueReport as $r): ?>
    <div class="flex-1 text-center">
        <div class="bg-blue-600" style="height:<?= min($r['revenue']/1000,100) ?>%"></div>
        <small><?= $r['month'] ?></small><br>
        <small>฿<?= number_format($r['revenue']) ?></small>
    </div>
<?php endforeach; ?>
</div>
</div>

<!-- ACTIVITIES -->
<div class="bg-white p-6 shadow rounded">
<h2 class="font-semibold mb-2">กิจกรรมล่าสุด</h2>

<?php if (!$recentActivities): ?>
    <p class="text-gray-500">ไม่มีกิจกรรม</p>
<?php else: ?>
    <ul class="space-y-2">
    <?php foreach ($recentActivities as $a): ?>
        <li class="border-b pb-2">
            <b><?= htmlspecialchars($a['description']) ?></b><br>
            <small><?= date('d/m/Y H:i', strtotime($a['createdAt'])) ?></small>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
</div>

</div>
