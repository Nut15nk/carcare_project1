<?php
// pages/MotorcyclesPages.php

// 1️⃣ Include config และ Service ใหม่
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../service/MotorcycleService.php';

// 2️⃣ รับค่าตัวกรองจาก URL
$searchTerm     = $_GET['search'] ?? '';
$selectedBrand  = $_GET['brand'] ?? '';
$selectedType   = $_GET['type'] ?? '';
$priceRangeMin  = (int)($_GET['min_price'] ?? 0);
$priceRangeMax  = (int)($_GET['max_price'] ?? 1000);
$startDate      = $_GET['start_date'] ?? '';
$endDate        = $_GET['end_date'] ?? '';

// 3️⃣ ดึงข้อมูลจาก Service ใหม่
try {
    $filters = [
        'brand' => $selectedBrand,
        'model' => $searchTerm,
        'minPrice' => $priceRangeMin,
        'maxPrice' => $priceRangeMax,
        'type' => $selectedType
    ];

    $motorcycles_data = MotorcycleService::searchMotorcycles($filters, $startDate, $endDate);

    if (!is_array($motorcycles_data)) $motorcycles_data = [];

} catch (Exception $e) {
    $motorcycles_data = [];
    $error_message = "ไม่สามารถโหลดข้อมูลรถได้: " . $e->getMessage();
}

// 4️⃣ กรองข้อมูลเพิ่มเติมหน้าเว็บ (ถ้าต้องการ)
$filteredMotorcycles = array_filter($motorcycles_data, function($bike) use ($searchTerm, $selectedBrand, $selectedType, $priceRangeMin, $priceRangeMax) {
    if ($searchTerm) {
        $term = strtolower($searchTerm);
        $brandModel = strtolower(($bike['brand'] ?? '') . ' ' . ($bike['model'] ?? ''));
        if (!str_contains($brandModel, $term)) return false;
    }
    if ($selectedBrand && ($bike['brand'] ?? '') !== $selectedBrand) return false;
    if ($selectedType) {
        $cc = $bike['engineCc'] ?? 0;
        $typeMatch = false;
        if ($selectedType === 'small' && $cc <= 150) $typeMatch = true;
        if ($selectedType === 'medium' && $cc > 150 && $cc <= 300) $typeMatch = true;
        if ($selectedType === 'large' && $cc > 300) $typeMatch = true;
        if (!$typeMatch) return false;
    }
    $price = $bike['pricePerDay'] ?? 0;
    if ($price < $priceRangeMin || $price > $priceRangeMax) return false;
    return true;
});

// 5️⃣ เตรียม Dropdowns
$brands = !empty($motorcycles_data) ? array_unique(array_column($motorcycles_data, 'brand')) : [];
$types = [
    'small'  => 'เล็ก (≤ 150cc)',
    'medium' => 'กลาง (151-300cc)',
    'large'  => 'ใหญ่ (> 300cc)'
];

// 6️⃣ โปรโมชั่นตัวอย่าง
function calculateDiscount($days, $pricePerDay) {
    $normalPrice = $days * $pricePerDay;
    $discount = ($days >= 3) ? floor($days / 3) * 50 : 0;
    return [
        'normalPrice' => $normalPrice,
        'finalPrice'  => $normalPrice - $discount,
        'discount'    => $discount
    ];
}

$promoDays = 3;
$promoPricePerDay = 650;
$promoData = calculateDiscount($promoDays, $promoPricePerDay);

// 7️⃣ Base64 placeholder image
$placeholderImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
?>


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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <!-- Search -->
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5"></i>
                        <input
                            type="text"
                            name="search"
                            placeholder="ค้นหายี่ห้อหรือรุ่นรถ..."
                            value="<?php echo htmlspecialchars($searchTerm); ?>"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                    </div>
                    
                    <!-- Start Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">วันที่รับรถ</label>
                        <input
                            type="date"
                            name="start_date"
                            value="<?php echo htmlspecialchars($startDate); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            min="<?php echo date('Y-m-d'); ?>"
                        />
                    </div>
                    
                    <!-- End Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">วันที่คืนรถ</label>
                        <input
                            type="date"
                            name="end_date"
                            value="<?php echo htmlspecialchars($endDate); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            min="<?php echo date('Y-m-d'); ?>"
                        />
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="search" class="h-4 w-4"></i>
                            ค้นหา
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Filters - Always Visible -->
                <div class="border-t pt-4 mt-4">
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
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                                <input
                                    type="number"
                                    name="max_price"
                                    min="0"
                                    max="1000"
                                    placeholder="สูงสุด"
                                    value="<?php echo $priceRangeMax; ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                        
                        <!-- Reset Button -->
                        <div class="flex items-end">
                            <a
                                href="index.php?page=motorcycles"
                                class="w-full text-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors flex items-center justify-center gap-2"
                            >
                                <i data-lucide="refresh-cw" class="h-4 w-4"></i>
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

        <!-- Debug Info -->
        <?php if (empty($motorcycles_data)): ?>
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg mb-6">
                <p>⚠️ ไม่พบข้อมูลรถจากระบบ กรุณาตรวจสอบการเชื่อมต่อ API</p>
                <p class="text-sm mt-1">ลอง: <a href="test_motorcycles.php" class="underline">ทดสอบ API Motorcycles</a></p>
            </div>
        <?php endif; ?>

        <!-- Motorcycles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <?php foreach ($filteredMotorcycles as $motorcycle): ?>
                <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
                    <!-- Image -->
                    <div class="relative h-48 bg-gray-200">
                        <?php
                        $imageUrl = $motorcycle['imageUrl'] ?? '';
                        $hasValidImage = !empty($imageUrl) && filter_var($imageUrl, FILTER_VALIDATE_URL);
                        ?>
                        <img
                            src="<?php echo $hasValidImage ? htmlspecialchars($imageUrl) : $placeholderImage; ?>"
                            alt="<?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                            class="w-full h-full object-cover"
                            onerror="this.onerror=null; this.src='<?php echo $placeholderImage; ?>';"
                            loading="lazy"
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
                                    ฿<?php echo number_format($motorcycle['pricePerDay'] ?? 0, 2); ?>
                                </span>
                                <span class="text-gray-600 text-sm">/วัน</span>
                            </div>
                            
                            <?php 
                            $isAvailable = $motorcycle['isAvailable'] ?? false;
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
        <?php if (empty($filteredMotorcycles) && !empty($motorcycles_data)): ?>
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

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const today = new Date().toISOString().split('T')[0];
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.min = today;
        });
        
        // ตรวจสอบวันที่เริ่มต้นและสิ้นสุด
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                if (this.value) {
                    endDateInput.min = this.value;
                }
            });
            
            endDateInput.addEventListener('change', function() {
                if (this.value && startDateInput.value && this.value < startDateInput.value) {
                    this.value = startDateInput.value;
                }
            });
        }
    });
</script>
