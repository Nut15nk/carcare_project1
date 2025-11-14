<?php
// (0) จำลองข้อมูลรถ (ดึงมาจาก BookingPages.php)
$motorcycles_data = [
    [
        'id' => '1',
        'brand' => 'Honda',
        'model' => 'Wave 110i',
        'cc' => 110,
        'type' => 'Automatic',
        'pricePerDay' => 250,
        'image' => 'https://imgcdn.zigwheels.co.th/large/gallery/exterior/90/3251/honda-wave110i-2016-marketing/image-510506.jpg',
        'status' => 'available',
        'features' => ['ประหยัดน้ำมัน', 'ขับขี่ง่าย', 'เหมาะกับเมือง'],
        'bookings' => []
    ],
    [
        'id' => '2',
        'brand' => 'Honda',
        'model' => 'Click 160',
        'cc' => 160,
        'type' => 'Automatic',
        'pricePerDay' => 300,
        'image' => 'https://n9.cl/5vw6d4',
        'features' => ['สปอร์ต', 'ออโตเมติก', 'ประหยัดน้ำมัน'],
        'status' => 'available',
        'bookings' => []
    ],
    [
        'id' => '3',
        'brand' => 'Honda',
        'model' => 'PCX 160',
        'cc' => 160,
        'type' => 'Automatic',
        'pricePerDay' => 400,
        'image' => 'https://www.thaihonda.co.th/honda/uploads/cache/926/photos/shares/0125/Bike/Gallery-W926xH518_PX_Styling_01.jpg',
        'features' => ['หรูหรา', 'สะดวกสบาย', 'เทคโนโลยีทันสมัย'],
        'status' => 'available',
        'bookings' => []
    ],
    [
        'id' => '4',
        'brand' => 'Yamaha',
        'model' => 'NMAX',
        'cc' => 155,
        'type' => 'Automatic',
        'pricePerDay' => 450,
        'image' => 'https://n9.cl/5vw6d4',
        'status' => 'available',
        'features' => ['สปอร์ต', 'ประสิทธิภาพสูง', 'ดีไซน์ทันสมัย'],
        'bookings' => []
    ],
    [
        'id' => '5',
        'brand' => 'Honda',
        'model' => 'Giorno',
        'cc' => 125,
        'type' => 'Manual',
        'pricePerDay' => 500,
        'image' => 'https://www.thaihonda.co.th/honda/uploads/cache/685/photos/shares/giorno/AW_GIORNO__Online_Color_Section_W685xH426px_2.png',
        'status' => 'available',
        'features' => ['สปอร์ตไบค์', 'ประสิทธิภาพสูง', 'สำหรับผู้เชี่ยวชาญ'],
        'bookings' => []
    ],
    [
        'id' => '6',
        'brand' => 'Kawasaki',
        'model' => 'Ninja 400',
        'cc' => 400,
        'type' => 'Manual',
        'pricePerDay' => 800,
        'image' => 'https://austinracingthailand.com/wp-content/uploads/2023/08/KA196.1.18-.jpeg',
        'status' => 'available',
        'features' => ['สปอร์ตไบค์', 'ประสิทธิภาพสูง', 'เครื่องยนต์ทรงพลัง'],
        'bookings' => []
    ]
];

// (1) จำลองข้อมูล Bookings
$allBookings = [
    [
        'id' => 1,
        'motorcycleId' => '1',
        'startDate' => date('Y-m-d', strtotime('-5 days')),
        'endDate' => date('Y-m-d', strtotime('-3 days')),
        'totalPrice' => 500,
        'status' => 'completed',
        'paymentStatus' => 'paid',
        'createdAt' => date('Y-m-d H:i:s', strtotime('-5 days'))
    ],
    [
        'id' => 2,
        'motorcycleId' => '2',
        'startDate' => date('Y-m-d', strtotime('-2 days')),
        'endDate' => date('Y-m-d', strtotime('+3 days')),
        'totalPrice' => 1200,
        'status' => 'active',
        'paymentStatus' => 'paid',
        'createdAt' => date('Y-m-d H:i:s', strtotime('-2 days'))
    ],
    [
        'id' => 3,
        'motorcycleId' => '3',
        'startDate' => date('Y-m-d', strtotime('+1 day')),
        'endDate' => date('Y-m-d', strtotime('+5 days')),
        'totalPrice' => 1600,
        'status' => 'pending',
        'paymentStatus' => 'pending',
        'createdAt' => date('Y-m-d H:i:s')
    ],
    [
        'id' => 4,
        'motorcycleId' => '4',
        'startDate' => date('Y-m-d', strtotime('+2 days')),
        'endDate' => date('Y-m-d', strtotime('+6 days')),
        'totalPrice' => 2250,
        'status' => 'pending',
        'paymentStatus' => 'pending',
        'createdAt' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ],
    [
        'id' => 5,
        'motorcycleId' => '5',
        'startDate' => date('Y-m-d', strtotime('-10 days')),
        'endDate' => date('Y-m-d', strtotime('-7 days')),
        'totalPrice' => 1500,
        'status' => 'completed',
        'paymentStatus' => 'paid',
        'createdAt' => date('Y-m-d H:i:s', strtotime('-10 days'))
    ],
];

// (2) คำนวณ Stats (เหมือนใน React)
$totalBookings = count($allBookings);
$pendingBookings = count(array_filter($allBookings, fn($b) => $b['status'] === 'pending'));
$activeBookings = count(array_filter($allBookings, fn($b) => $b['status'] === 'active'));
$totalRevenue = array_reduce(
    array_filter($allBookings, fn($b) => $b['paymentStatus'] === 'paid'),
    fn($sum, $b) => $sum + $b['totalPrice'],
    0
);
$availableMotorcycles = count(array_filter($motorcycles_data, fn($m) => $m['status'] === 'available'));

// (2) สร้างอาร์เรย์ Stats
$stats = [
    ['label' => 'การจองทั้งหมด', 'value' => $totalBookings, 'icon' => 'calendar', 'color' => 'bg-blue-500'],
    ['label' => 'รอการยืนยัน', 'value' => $pendingBookings, 'icon' => 'clock', 'color' => 'bg-yellow-500'], // ใช้ clock ธรรมดา
    ['label' => 'กำลังเช่า', 'value' => $activeBookings, 'icon' => 'trending-up', 'color' => 'bg-green-500'],
    ['label' => 'รายได้รวม', 'value' => '฿' . number_format($totalRevenue, 0), 'icon' => 'credit-card', 'color' => 'bg-purple-500'],
    ['label' => 'รถว่าง', 'value' => $availableMotorcycles, 'icon' => 'bike', 'color' => 'bg-indigo-500'],
];

// (4) เรียงลำดับการจองล่าสุด (สำหรับตาราง)
$recentBookings = $allBookings;
usort($recentBookings, fn($a, $b) => strtotime($b['createdAt']) - strtotime($a['createdAt']));
$recentBookings = array_slice($recentBookings, 0, 5); // เอา 5 รายการล่าสุด

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
                    // ค้นหารถ (เหมือน .find())
                    $motorcycle = null;
                    foreach ($motorcycles_data as $m) {
                        if ($m['id'] == $booking['motorcycleId']) {
                            $motorcycle = $m;
                            break;
                        }
                    }

                    // กำหนดสีสถานะ
                    $statusText = $booking['status'];
                    $statusColor = 'bg-gray-100 text-gray-800';
                    if ($booking['status'] === 'pending') {
                        $statusText = 'รอยืนยัน';
                        $statusColor = 'bg-yellow-100 text-yellow-800';
                    } elseif ($booking['status'] === 'confirmed') {
                        $statusText = 'ยืนยันแล้ว';
                        $statusColor = 'bg-blue-100 text-blue-800';
                    } elseif ($booking['status'] === 'active') {
                        $statusText = 'กำลังเช่า';
                        $statusColor = 'bg-green-100 text-green-800';
                    }
                    ?>
                    <div class="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
                        <div>
                            <p class="font-medium text-gray-900">
                                <?php echo $motorcycle ? ($motorcycle['brand'] . ' ' . $motorcycle['model']) : 'ไม่พบข้อมูลรถ'; ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <?php echo date('d/m/Y', strtotime($booking['startDate'])); ?> - <?php echo date('d/m/Y', strtotime($booking['endDate'])); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-900">฿<?php echo number_format($booking['totalPrice'], 0); ?></p>
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