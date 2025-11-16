<?php
// pages/MotorcyclesPage.php
// ไม่ต้อง session_start() เพราะ index.php เรียกไปแล้ว

// โหลดไฟล์ API
require_once '../api/config.php';
require_once '../api/motorcycles.php';
require_once '../api/auth.php';

// --- (1) รับค่าตัวกรอง (แทน useState) ---
$searchTerm = $_GET['search'] ?? '';
$selectedBrand = $_GET['brand'] ?? '';
$selectedType = $_GET['type'] ?? '';
$priceRangeMin = (int)($_GET['min_price'] ?? 0);
$priceRangeMax = (int)($_GET['max_price'] ?? 1000);
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// --- (2) ดึงข้อมูลจาก API ---
try {
    if ($startDate && $endDate) {
        // ถ้ามีการเลือกวันที่ ให้ดึงเฉพาะรถที่ว่าง
        $motorcycles_data = MotorcycleService::getAvailableMotorcycles($startDate, $endDate);
    } else {
        // ถ้าไม่เลือกวันที่ ให้ดึงรถทั้งหมด
        $motorcycles_data = MotorcycleService::getAllMotorcycles();
    }
} catch (Exception $e) {
    $motorcycles_data = [];
    $error_message = "ไม่สามารถโหลดข้อมูลรถได้: " . $e->getMessage();
}

// --- (3) ตรรกะการกรอง (แทน useEffect) ---
$filteredMotorcycles = $motorcycles_data;

// 1. กรองตามคำค้นหา
if ($searchTerm) {
    $filteredMotorcycles = array_filter($filteredMotorcycles, function($bike) use ($searchTerm) {
        $term = strtolower($searchTerm);
        return str_contains(strtolower($bike['brand'] ?? ''), $term) || 
               str_contains(strtolower($bike['model'] ?? ''), $term);
    });
}

// 2. กรองตามยี่ห้อ
if ($selectedBrand) {
    $filteredMotorcycles = array_filter($filteredMotorcycles, function($bike) use ($selectedBrand) {
        return ($bike['brand'] ?? '') === $selectedBrand;
    });
}

// 3. กรองตามประเภท (ใช้ engineCc เป็นตัวแทน)
if ($selectedType) {
    $filteredMotorcycles = array_filter($filteredMotorcycles, function($bike) use ($selectedType) {
        $engineCc = $bike['engineCc'] ?? 0;
        if ($selectedType === 'small' && $engineCc <= 150) return true;
        if ($selectedType === 'medium' && $engineCc > 150 && $engineCc <= 300) return true;
        if ($selectedType === 'large' && $engineCc > 300) return true;
        return false;
    });
}

// 4. กรองตามราคา
$filteredMotorcycles = array_filter($filteredMotorcycles, function($bike) use ($priceRangeMin, $priceRangeMax) {
    $price = $bike['pricePerDay'] ?? 0;
    return $price >= $priceRangeMin && $price <= $priceRangeMax;
});

// --- (4) เตรียมข้อมูลสำหรับ Dropdowns ---
$brands = array_unique(array_column($motorcycles_data, 'brand'));
$types = [
    'small' => 'เล็ก (≤ 150cc)',
    'medium' => 'กลาง (151-300cc)', 
    'large' => 'ใหญ่ (> 300cc)'
];

// --- (5) คำนวณโปรโมชั่น ---
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

$promoDays = 3;
$promoPricePerDay = 950 / 3;
$promoData = calculateDiscount($promoDays, $promoPricePerDay);

?>

<!-- (6) เริ่มส่วน HTML -->
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
        <form method="GET" action="index.php">
            <input type="hidden" name="page" value="motorcycles">

            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex flex-col lg:flex-row gap-4 mb-4">
                    <!-- Search -->
                    <div class="flex-1 relative">
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
                                min="<?php echo date('Y-m-d'); ?>"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">วันที่คืนรถ</label>
                            <input
                                type="date"
                                name="end_date"
                                value="<?php echo htmlspecialchars($endDate); ?>"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                min="<?php echo date('Y-m-d'); ?>"
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
                    <!-- ปุ่ม Submit -->
                     <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                        ค้นหา
                    </button>
                </div>
                
                <!-- Advanced Filters -->
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
                                        <?php if ($selectedBrand == $brand) echo 'selected'; ?>
                                    >
                                        <?php echo $brand; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Type Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ขนาดเครื่อง</label>
                            <select
                                name="type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">ทุกขนาด</option>
                                <?php foreach ($types as $key => $label): ?>
                                    <option 
                                        value="<?php echo $key; ?>"
                                        <?php if ($selectedType == $key) echo 'selected'; ?>
                                    >
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Price Range -->
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
                        <!-- Reset Button -->
                        <div class="flex items-end">
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
        </form>

        <!-- Results Summary -->
        <div class="mb-6">
            <p class="text-gray-600">
                พบรถจักรยานยนต์ <?php echo count($filteredMotorcycles); ?> คัน
                
                <?php if ($startDate && $endDate): ?>
                    <span class="ml-2 text-blue-600">
                        สำหรับวันที่ <?php echo date('d/m/Y', strtotime($startDate)); ?> - <?php echo date('d/m/Y', strtotime($endDate)); ?>
                    </span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Error Message -->
        <?php if (isset($error_message)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Motorcycles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <?php foreach ($filteredMotorcycles as $motorcycle): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                    <!-- Image -->
                    <div class="relative h-48 bg-gray-200">
                        <img
                            src="<?php echo htmlspecialchars($motorcycle['imageUrl'] ?? '../img/default-bike.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                            class="w-full h-full object-cover"
                            onerror="this.src='../img/default-bike.jpg'"
                        />
                        <div class="absolute top-4 right-4">
                            <?php
                            $isAvailable = $motorcycle['isAvailable'] ?? false;
                            $statusText = $isAvailable ? 'พร้อมใช้งาน' : 'ไม่ว่าง';
                            $statusClass = $isAvailable ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
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
                                <span><?php echo $motorcycle['engineCc'] ?? 'N/A'; ?>cc</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <i data-lucide="calendar" class="h-4 w-4"></i>
                                <span><?php echo $motorcycle['year'] ?? 'N/A'; ?></span>
                            </div>
                        </div>
                        <!-- Description -->
                        <div class="mb-4">
                            <p class="text-gray-600 text-sm">
                                <?php echo htmlspecialchars($motorcycle['description'] ?? 'รถจักรยานยนต์คุณภาพดี'); ?>
                            </p>
                        </div>
                        <!-- Price and Action -->
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-2xl font-bold text-blue-600">
                                    ฿<?php echo number_format($motorcycle['pricePerDay'], 2); ?>
                                </span>
                                <span class="text-gray-600 text-sm">/วัน</span>
                            </div>
                            
                            <?php 
                            $isAvailable = $motorcycle['isAvailable'] ?? false;
                            // ถ้าเลือกวันที่, ให้สามารถจองได้ถ้ารถว่าง
                            // ถ้าไม่เลือกวันที่, ให้เช็คสถานะจริง
                            $canBook = $isAvailable;
                            ?>

                            <?php if ($canBook): ?>
                                <a
                                    href="index.php?page=booking&id=<?php echo $motorcycle['motorcycleId']; ?>"
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

        <!-- Special Offers -->
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

<!-- JavaScript สำหรับซ่อน/แสดงตัวกรอง -->
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

        // ตั้งค่าวันที่ขั้นต่ำเป็นวันนี้
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.min = today;
        });
    });
</script>