<?php
// pages/admin/sections/AdminDashboard.php

session_start();

require_once __DIR__ . '/../../../service/Admin/AdminService.php';
use Service\Admin\AdminService;

// DEBUG
error_log("=== DASHBOARD DEBUG ===");
error_log("Session User: " . (isset($_SESSION['user']) ? 'EXISTS' : 'NOT EXISTS'));
error_log("User Role: " . ($_SESSION['user']['role'] ?? 'NO ROLE'));

try {
    $statsData = AdminService::getDashboardStats();
    $recentBookings = AdminService::getRecentReservations(5);
} catch (Throwable $e) {
    error_log('[AdminDashboard] ' . $e->getMessage());
    $statsData = [];
    $recentBookings = [];
}

// Stats card data (เหมือนเดิม ไม่แตะ UI)
$stats = [
    ['label'=>'การจองทั้งหมด','value'=>$statsData['totalBookings'] ?? 0,'icon'=>'calendar','color'=>'bg-blue-500'],
    ['label'=>'รอการยืนยัน','value'=>$statsData['pendingBookings'] ?? 0,'icon'=>'clock','color'=>'bg-yellow-500'],
    ['label'=>'กำลังเช่า','value'=>$statsData['activeBookings'] ?? 0,'icon'=>'trending-up','color'=>'bg-green-500'],
    ['label'=>'รายได้รวม','value'=>'฿'.number_format($statsData['totalRevenue'] ?? 0),'icon'=>'credit-card','color'=>'bg-purple-500'],
    ['label'=>'รถว่าง','value'=>$statsData['availableMotorcycles'] ?? 0,'icon'=>'bike','color'=>'bg-indigo-500'],
];
?>

<!-- (5) เริ่ม HTML ของ "ภาพรวม" -->
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">ภาพรวมระบบ</h1>
        <p class="text-gray-600">สรุปข้อมูลการดำเนินงานของร้าน</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <?php foreach ($stats as $stat): ?>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="<?php echo $stat['color']; ?> p-3 rounded-lg">
                        <i data-lucide="<?php echo $stat['icon']; ?>" class="h-6 w-6 text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600"><?php echo $stat['label']; ?></p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stat['value']; ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Recent Bookings -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">การจองล่าสุด (5 รายการ)</h2>
        </div>
        <div class="p-6">
            <?php if (empty($recentBookings)): ?>
                <p class="text-gray-500 text-center">ยังไม่มีการจอง</p>
            <?php else: ?>
                <?php foreach ($recentBookings as $booking): ?>
                    <?php
                    // กำหนดสีสถานะ (logic เดิม)
                    $statusText = $booking['status'] ?? 'pending';
                    $statusColor = 'bg-gray-100 text-gray-800';

                    if ($statusText === 'pending') {
                        $statusText = 'รอยืนยัน';
                        $statusColor = 'bg-yellow-100 text-yellow-800';
                    } elseif ($statusText === 'confirmed') {
                        $statusText = 'ยืนยันแล้ว';
                        $statusColor = 'bg-blue-100 text-blue-800';
                    } elseif ($statusText === 'active') {
                        $statusText = 'กำลังเช่า';
                        $statusColor = 'bg-green-100 text-green-800';
                    } elseif ($statusText === 'completed') {
                        $statusText = 'เสร็จสิ้น';
                        $statusColor = 'bg-gray-100 text-gray-800';
                    } elseif ($statusText === 'cancelled') {
                        $statusText = 'ยกเลิก';
                        $statusColor = 'bg-red-100 text-red-800';
                    }
                    ?>

                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                        <div>
                            <!-- 🔧 FIX: ใช้ brand / model จาก SQL -->
                            <p class="font-medium text-gray-900">
                                <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>
                            </p>

                            <!-- 🔧 FIX: ใช้ start_date / end_date -->
                            <p class="text-sm text-gray-600">
                                <?php
                                echo date('d/m/Y', strtotime($booking['start_date'])) .
                                     ' - ' .
                                     date('d/m/Y', strtotime($booking['end_date']));
                                ?>
                            </p>
                        </div>

                        <div class="text-right">
                            <!-- 🔧 FIX: ใช้ total_price -->
                            <p class="font-medium text-gray-900">
                                ฿<?php echo number_format($booking['total_price'], 0); ?>
                            </p>

                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $statusColor; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- DEBUG -->
<script>
console.log('Dashboard Debug');
console.log('Stats Data:', <?php echo json_encode($statsData); ?>);
console.log('Recent Bookings:', <?php echo json_encode($recentBookings); ?>);
</script>
