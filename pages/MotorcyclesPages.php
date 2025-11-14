<?php
// pages/MotorcyclesPage.php
// ไม่ต้อง session_start() เพราะ index.php เรียกไปแล้ว

// --- (1) ฐานข้อมูลจำลอง (Mock Database) ---
// (ในอนาคต ควรสรุปข้อมูลนี้จาก Database หรือ $_SESSION)
$motorcycles_data = [
    [
        'id' => '1',
        'brand' => 'Honda',
        'model' => 'Wave 110i',
        'cc' => 110,
        'type' => 'Automatic',
        'pricePerDay' => 250,
        'image' => 'https://imgcdn.zigwheels.co.th/large/gallery/exterior/90/3251/honda-wave110i-2016-marketing-image-510506.jpg',
        'status' => 'available',
        'features' => ['ประหยัดน้ำมัน', 'ขับขี่ง่าย', 'เหมาะกับเมือง'],
        'bookings' => [] // จำลองการจอง (ในอนาคตควรดึงจาก $_SESSION['bookings'])
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
        'image' => 'https://www.thaihonda.co.th/honda/uploads/cache/926/photos/shares/0125/Bike-Gallery-W926xH518_PX_Styling_01.jpg',
        'features' => ['หรูหรา', 'สะดวกสบาย', 'เทคโนโลยีทันสมัย'],
        'status' => 'available',
        'bookings' => [
            // ['start' => '2025-11-18', 'end' => '2025-11-20'] // ตัวอย่างการจอง
        ]
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

// --- (2) ฟังก์ชัน Helpers (แปลงจาก React) ---

/**
 * คำนวณส่วนลด (ลด 50 บาท ทุกๆ 3 วัน)
 */
function calculateDiscount($days, $pricePerDay) {
    $normalPrice = $days * $pricePerDay;
    $discount = 0;
    if ($days >= 3) {
        $discount = floor($days / 3) * 50;
    }
    return [
        'normalPrice' => $normalPrice,
        'finalPrice' => $normalPrice - $discount,
        'discount' => $discount
    ];
}

/**
 * ฟังก์ชันกรองรถที่ว่างตามวันที่
 * (จำลอง getAvailableMotorcycles)
 */
function getAvailableMotorcycles($motorcycles, $startDate, $endDate, $allBookings) {
    if (empty($startDate) || empty($endDate)) return $motorcycles;
    try {
        $requestStart = new DateTime($startDate);
        $requestEnd = new DateTime($endDate);
    } catch (Exception $e) {
        return $motorcycles; // คืนค่าทั้งหมดถ้าวันที่ผิด
    }

    // (A) สร้างรายการ ID รถมอเตอร์ไซค์ที่ "ไม่ว่าง" ในช่วงวันที่เลือก
    $bookedMotorcycleIds = [];
    foreach ($allBookings as $booking) {
        // ข้ามการจองที่ยกเลิก
        if ($booking['status'] === 'cancelled') continue;

        try {
            $bookingStart = new DateTime($booking['startDate']);
            $bookingEnd = new DateTime($booking['endDate']);

            // ตรวจสอบการทับซ้อน (Overlap)
            // ถ้า (วันเริ่มจอง < วันคืนรถที่จองแล้ว) และ (วันคืนรถ > วันเริ่มรถที่จองแล้ว)
            if ($requestStart < $bookingEnd && $requestEnd > $bookingStart) {
                $bookedMotorcycleIds[] = $booking['motorcycleId'];
            }
        } catch (Exception $e) { /* ข้ามการจองที่ผิดพลาด */ }
    }
    
    // (B) กรองรถ
    return array_filter($motorcycles, function($bike) use ($bookedMotorcycleIds) {
        // 1. รถต้อง 'available' (ไม่โดนซ่อม)
        // 2. ID รถต้องไม่อยู่ในรายการ "ไม่ว่าง"
        return $bike['status'] === 'available' && !in_array($bike['id'], $bookedMotorcycleIds);
    });
}

// --- (3) รับค่าตัวกรอง (แทน useState) ---
$searchTerm = $_GET['search'] ?? '';
$selectedBrand = $_GET['brand'] ?? '';
$selectedType = $_GET['type'] ?? '';
$priceRangeMin = (int)($_GET['min_price'] ?? 0);
$priceRangeMax = (int)($_GET['max_price'] ?? 1000);
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// ดึงการจองทั้งหมดจาก Session (เพื่อใช้กรองวันที่)
$allBookings = $_SESSION['bookings'] ?? [];

// --- (4) ตรรกะการกรอง (แทน useEffect) ---
$filteredMotorcycles = $motorcycles_data;

// 1. กรองตามวันที่ (ถ้าเลือก)
$filteredMotorcycles = getAvailableMotorcycles($filteredMotorcycles, $startDate, $endDate, $allBookings);

// 2. กรองตามคำค้นหา
if ($searchTerm) {
    $filteredMotorcycles = array_filter($filteredMotorcycles, function($bike) use ($searchTerm) {
        $term = strtolower($searchTerm);
        return str_contains(strtolower($bike['brand']), $term) || 
               str_contains(strtolower($bike['model']), $term);
    });
}

// 3. กรองตามยี่ห้อ
if ($selectedBrand) {
    $filteredMotorcycles = array_filter($filteredMotorcycles, function($bike) use ($selectedBrand) {
        return $bike['brand'] === $selectedBrand;
    });
}

// 4. กรองตามประเภท
if ($selectedType) {
    $filteredMotorcycles = array_filter($filteredMotorcycles, function($bike) use ($selectedType) {
        return $bike['type'] === $selectedType;
    });
}

// 5. กรองตามราคา
$filteredMotorcycles = array_filter($filteredMotorcycles, function($bike) use ($priceRangeMin, $priceRangeMax) {
    return $bike['pricePerDay'] >= $priceRangeMin && $bike['pricePerDay'] <= $priceRangeMax;
});

// --- (5) เตรียมข้อมูลสำหรับ Dropdowns ---
$brands = array_unique(array_column($motorcycles_data, 'brand'));
$types = array_unique(array_column($motorcycles_data, 'type'));

// --- (6) คำนวณโปรโมชั่น (เหมือน React) ---
$promoDays = 3;
$promoPricePerDay = 950 / 3;
$promoData = calculateDiscount($promoDays, $promoPricePerDay);

?>

<!-- (7) เริ่มส่วน HTML (เทียบเท่า return() ของ React) -->
<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-4">เลือกรถจักรยานยนต์ของคุณ</h1>
                <p class="text-xl text-blue-100">รถหลากหลายรุ่น ตั้งแต่ 110cc ถึง 700cc</p>
            </div>
        </div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Search and Filter Section -->
        <!-- นี่คือฟอร์มที่จะส่งค่า GET กลับไปที่ index.php (Router) -->
        <form method="GET" action="index.php">
            <!-- (สำคัญ) ต้องส่ง page=motorcycles กลับไปด้วยเสมอ -->
            <input type="hidden" name="page" value="motorcycles">

            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex flex-col lg:flex-row gap-4 mb-4">
                    <!-- Search -->
                    <div class="flex-1 relative">
                        <!-- แปลง <Search> เป็น <i data-lucide="..."> -->
                        <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5"></i>
                        <input
                            type="text"
                            name="search"
                            placeholder="ค้นหายี่ห้อหรือรุ่นรถ..."
                            value="<?php echo htmlspecialchars($searchTerm); ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                    <!-- Date Range -->
                    <div class="flex gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">วันที่รับรถ</label>
                            <input
                                type="date"
                                name="start_date"
                                value="<?php echo htmlspecialchars($startDate); ?>"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">วันที่คืนรถ</label>
                            <input
                                type="date"
                                name="end_date"
                                value="<?php echo htmlspecialchars($endDate); ?>"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                    <!-- Filter Toggle (ใช้ JS) -->
                    <button
                        type="button"
                        id="filter-toggle-button"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                    >
                        <i data-lucide="filter" class="h-5 w-5"></i>
                        ตัวกรอง
                    </button>
                    <!-- ปุ่ม Submit ของฟอร์ม (ถูกซ่อนไว้ แต่จำเป็น) -->
                     <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        ค้นหา
                    </button>
                </div>
                
                <!-- Advanced Filters (ซ่อน/แสดง ด้วย JS) -->
                <!-- React ใช้ {showFilters && ...} PHP ใช้ JS ด้านล่าง -->
                <div id="advanced-filters" class="border-t pt-4 mt-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Brand Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ยี่ห้อ</label>
                            <select
                                name="brand"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">ทุกยี่ห้อ</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option 
                                        value="<?php echo $brand; ?>"
                                        <?php if ($selectedBrand == $brand) echo 'selected'; // ทำให้จำค่าที่เลือกได้ ?>
                                    >
                                        <?php echo $brand; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Type Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ประเภท</label>
                            <select
                                name="type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">ทุกประเภท</option>
                                <?php foreach ($types as $type): ?>
                                    <option 
                                        value="<?php echo $type; ?>"
                                        <?php if ($selectedType == $type) echo 'selected'; ?>
                                    >
                                        <?php echo $type; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Price Range (เปลี่ยนจาก Range Slider เป็น Number Input) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ราคาต่อวัน
                            </label>
                            <div class="flex gap-2">
                                <input
                                    type="number"
                                    name="min_price"
                                    min="0"
                                    max="1000"
                                    placeholder="ต่ำสุด"
                                    value="<?php echo $priceRangeMin; ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                                <input
                                    type="number"
                                    name="max_price"
                                    min="0"
                                    max="1000"
                                    placeholder="สูงสุด"
                                    value="<?php echo $priceRangeMax; ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                />
                            </div>
                        </div>
                        <!-- Reset Button (เปลี่ยนเป็น Link) -->
                        <div class="flex items-end">
                            <!-- ลิงก์นี้จะล้างค่า GET ทั้งหมด -->
                            <a
                                href="index.php?page=motorcycles"
                                class="w-full text-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors"
                            >
                                รีเซ็ต
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form> <!-- จบฟอร์ม -->

        <!-- Results Summary -->
        <div class="mb-6">
            <p class="text-gray-600">
                พบรถจักรยานยนต์ <?php echo count($filteredMotorcycles); ?> คัน
                
                <?php // แปลง {startDate && endDate && ...} ?>
                <?php if ($startDate && $endDate): ?>
                    <span class="ml-2 text-blue-600">
                        <!-- 
                            (!!!) นี่คือจุดที่แก้ไขครับ (!!!)
                            เปลี่ยนจาก Y-m-d เป็น d/m/Y 
                        -->
                        สำหรับวันที่ <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Motorcycles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <?php // แปลง .map() เป็น foreach ?>
            <?php foreach ($filteredMotorcycles as $motorcycle): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                    <!-- Image -->
                    <div class="relative h-48 bg-gray-200">
                        <img
                            src="<?php echo htmlspecialchars($motorcycle['image']); ?>"
                            alt="<?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                            class="w-full h-full object-cover"
                        />
                        <div class="absolute top-4 right-4">
                            <?php
                            // ตรรกะแสดงสถานะ
                            // (เรากรองรถที่ไม่ว่างออกไปแล้ว ถ้เลือกวันที่)
                            // (แต่ถ้าไม่เลือกวันที่, เราจะแสดงสถานะจริง)
                            $status = $motorcycle['status'];
                            $statusText = 'ซ่อมบำรุง';
                            $statusClass = 'bg-yellow-100 text-yellow-800';
                            
                            if ($status === 'available') {
                                $statusText = 'พร้อมใช้งาน';
                                $statusClass = 'bg-green-100 text-green-800';
                            } elseif ($status === 'booked') {
                                $statusText = 'ถูกจอง';
                                $statusClass = 'bg-red-100 text-red-800';
                            }
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>
                    <!-- Content -->
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xl font-bold text-gray-900">
                                <?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>
                            </h3>
                            <div class="flex items-center">
                                <i data-lucide="star" class="h-4 w-4 text-yellow-400 fill-current"></i>
                                <span class="text-sm text-gray-600 ml-1">4.8</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                            <div class="flex items-center gap-1">
                                <i data-lucide="fuel" class="h-4 w-4"></i>
                                <span><?php echo $motorcycle['cc']; ?>cc</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <i data-lucide="users" class="h-4 w-4"></i>
                                <span><?php echo htmlspecialchars($motorcycle['type']); ?></span>
                            </div>
                        </div>
                        <!-- Features -->
                        <div class="mb-4">
                            <div class="flex flex-wrap gap-1">
                                <?php // แสดง 3 feature แรก ?>
                                <?php foreach (array_slice($motorcycle['features'], 0, 3) as $feature): ?>
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded-full">
                                        <?php echo htmlspecialchars($feature); ?>
                                    </span>
                                <?php endforeach; ?>
                                
                                <?php if (count($motorcycle['features']) > 3): ?>
                                    <span class="px-2 py-1 bg-gray-50 text-gray-600 text-xs rounded-full">
                                        +<?php echo count($motorcycle['features']) - 3; ?> อื่นๆ
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Price and Action -->
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-2xl font-bold text-blue-600">
                                    ฿<?php echo $motorcycle['pricePerDay']; ?>
                                </span>
                                <span class="text-gray-600 text-sm">/วัน</span>
                            </div>
                            
                            <?php 
                            // ถ้าเลือกวันที่, รถคันนี้ "ว่าง" แน่นอน
                            // ถ้าไม่เลือกวันที่, ให้เช็คสถานะจริง
                            $isAvailable = $startDate && $endDate ? true : $motorcycle['status'] === 'available';
                            ?>

                            <?php if ($isAvailable): ?>
                                <!-- แปลง <Link> เป็น <a> -->
                                <a
                                    href="index.php?page=booking&id=<?php echo $motorcycle['id']; ?>"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
                                >
                                    จองเลย
                                </a>
                            <?php else: ?>
                                <button
                                    disabled
                                    class="bg-gray-300 text-gray-500 px-6 py-2 rounded-lg font-medium cursor-not-allowed"
                                >
                                    ไม่ว่าง
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- No Results -->
        <?php if (empty($filteredMotorcycles)): ?>
            <div class="text-center py-12">
                <div class="text-gray-400 mb-4">
                    <i data-lucide="calendar" class="h-16 w-16 mx-auto"></i>
                </div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">ไม่พบรถที่ตรงกับเงื่อนไข</h3>
                <p class="text-gray-600 mb-4">ลองปรับเปลี่ยนเงื่อนไขการค้นหาหรือเลือกวันที่อื่น</p>
                <a
                    href="index.php?page=motorcycles"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
                >
                    รีเซ็ตตัวกรอง
                </a>
            </div>
        <?php endif; ?>

        <!-- Special Offers (คัดลอกตรรกะจาก home.php) -->
        <div class="mt-12 bg-gradient-to-r from-yellow-400 to-orange-500 rounded-lg p-8 text-center">
            <h2 class="text-2xl font-bold text-white mb-4">โปรโมชั่นพิเศษ!</h2>
            <div class="bg-white rounded-lg p-6 max-w-md mx-auto">
                <div class="text-3xl font-bold text-orange-600 mb-2">เช่า <?php echo $promoDays; ?> วัน</div>
                <div class="text-xl text-gray-800 mb-4">
                    <span class="line-through text-gray-500"><?php echo number_format($promoData['normalPrice'], 0); ?> บาท</span>
                    <span class="ml-2 text-green-600 font-bold"><?php echo number_format($promoData['finalPrice'], 0); ?> บาท</span>
                </div>
                <p class="text-gray-600 mb-4">
                    <?php if ($promoData['discount'] > 0): ?>
                        ประหยัด <?php echo $promoData['discount']; ?> บาท (ลด 50 บาท ทุกๆ 3 วัน)
                    <?php else: ?>
                        เช่าน้อยกว่า 3 วันยังไม่มีส่วนลด
                    <?php endif; ?>
                </p>
                <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                    <i data-lucide="map-pin" class="h-4 w-4"></i>
                    <span>บริการส่งรถถึงโรงแรม • คืนรถที่สนามบินหาดใหญ่</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- (8) JavaScript สำหรับซ่อน/แสดงตัวกรอง -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const filterButton = document.getElementById('filter-toggle-button');
        const filterContent = document.getElementById('advanced-filters');

        if (filterButton && filterContent) {
            filterButton.addEventListener('click', function() {
                filterContent.classList.toggle('hidden');
            });

            // ตรวจสอบว่ามีค่า filter อยู่ใน URL หรือไม่ ถ้ามี ให้เปิดตัวกรอง
            <?php if ($selectedBrand || $selectedType || $priceRangeMin > 0 || $priceRangeMax < 1000): ?>
                filterContent.classList.remove('hidden');
            <?php endif; ?>
        }
    });
</script>