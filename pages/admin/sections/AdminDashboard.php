<?php
// pages/admin/sections/AdminDashboard.php
session_start(); // ต้องมีบรรทัดนี้สำคัญ!

// DEBUG: ตรวจสอบ Session
error_log("=== DASHBOARD DEBUG ===");
error_log("Session User: " . (isset($_SESSION['user']) ? 'EXISTS' : 'NOT EXISTS'));
error_log("User Role: " . ($_SESSION['user']['role'] ?? 'NO ROLE'));
error_log("Token: " . (isset($_SESSION['user']['token']) ? 'EXISTS' : 'NOT EXISTS'));

// โหลด AdminService
$adminServicePaths = [
    __DIR__ . '/../../../api/admin.php',
    __DIR__ . '/../../api/admin.php', 
    'api/admin.php'
];

$adminServiceLoaded = false;
foreach ($adminServicePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $adminServiceLoaded = true;
        break;
    }
}

if (!$adminServiceLoaded) {
    die("ไม่พบไฟล์ AdminService");
}

// ใช้ API จริงแทน Mock Data
try {
    // ดึงข้อมูลจาก API
    $statsData = AdminService::getDashboardStats();
    $allReservations = AdminService::getAllReservations();
    
    // DEBUG: ดูข้อมูลที่ได้จาก API
    error_log("Stats Data: " . json_encode($statsData));
    error_log("Reservations Count: " . count($allReservations));
    
    // สร้าง stats จากข้อมูลจริง
    $stats = [
        ['label' => 'การจองทั้งหมด', 'value' => $statsData['totalBookings'] ?? 0, 'icon' => 'calendar', 'color' => 'bg-blue-500'],
        ['label' => 'รอการยืนยัน', 'value' => $statsData['pendingBookings'] ?? 0, 'icon' => 'clock', 'color' => 'bg-yellow-500'],
        ['label' => 'กำลังเช่า', 'value' => $statsData['activeBookings'] ?? 0, 'icon' => 'trending-up', 'color' => 'bg-green-500'],
        ['label' => 'รายได้รวม', 'value' => '฿' . number_format($statsData['totalRevenue'] ?? 0, 0), 'icon' => 'credit-card', 'color' => 'bg-purple-500'],
        ['label' => 'รถว่าง', 'value' => $statsData['availableMotorcycles'] ?? 0, 'icon' => 'bike', 'color' => 'bg-indigo-500'],
    ];
    
    // ใช้การจองล่าสุดจาก API (5 รายการแรก)
    $recentBookings = array_slice($allReservations, 0, 5);
    
} catch (Exception $e) {
    // ถ้า API error ใช้ mock data fallback (ไม่กระทบการแสดงผล)
    error_log("Dashboard API Error: " . $e->getMessage());
    
    // Fallback mock data
    $recentBookings = [];
    
    $stats = [
        ['label' => 'การจองทั้งหมด', 'value' => 0, 'icon' => 'calendar', 'color' => 'bg-blue-500'],
        ['label' => 'รอการยืนยัน', 'value' => 0, 'icon' => 'clock', 'color' => 'bg-yellow-500'],
        ['label' => 'กำลังเช่า', 'value' => 0, 'icon' => 'trending-up', 'color' => 'bg-green-500'],
        ['label' => 'รายได้รวม', 'value' => '฿0', 'icon' => 'credit-card', 'color' => 'bg-purple-500'],
        ['label' => 'รถว่าง', 'value' => 0, 'icon' => 'bike', 'color' => 'bg-indigo-500'],
    ];
}
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
                    // กำหนดสีสถานะ
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
                            <p class="font-medium text-gray-900">
                                <?php 
                                if (isset($booking['motorcycle']['brand']) && isset($booking['motorcycle']['model'])) {
                                    echo htmlspecialchars($booking['motorcycle']['brand'] . ' ' . $booking['motorcycle']['model']);
                                } else if (isset($booking['motorcycleId'])) {
                                    echo htmlspecialchars('รถรหัส: ' . $booking['motorcycleId']);
                                } else {
                                    echo 'ไม่พบข้อมูลรถ';
                                }
                                ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <?php 
                                $startDate = $booking['startDate'] ?? date('Y-m-d');
                                $endDate = $booking['endDate'] ?? date('Y-m-d', strtotime('+1 day'));
                                echo date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)); 
                                ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-900">฿<?php echo number_format($booking['totalPrice'] ?? 0, 0); ?></p>
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

<!-- DEBUG: แสดงข้อมูล session สำหรับตรวจสอบ -->
<script>
console.log('Dashboard Debug:');
console.log('Stats Data:', <?php echo json_encode($statsData); ?>);
console.log('Recent Bookings:', <?php echo json_encode($recentBookings); ?>);
</script>