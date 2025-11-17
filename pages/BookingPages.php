<?php
// pages/BookingPages.php
session_start();

// ตรวจสอบการล็อกอิน - แก้เป็นใช้ $_SESSION['user']
if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง'
    ];
    header("Location: login.php");
    exit;
}

$error = '';
$today = date('Y-m-d');

// โหลดไฟล์ API
$configPaths = [
    __DIR__ . '/../api/config.php',
    __DIR__ . '/../../api/config.php',
    'api/config.php'
];

$configLoaded = false;
foreach ($configPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $configLoaded = true;
        break;
    }
}

if (!$configLoaded) {
    die('ไม่พบไฟล์ config.php');
}

// โหลด motorcycles.php
$motorcyclePaths = [
    __DIR__ . '/../api/motorcycles.php',
    __DIR__ . '/../../api/motorcycles.php',
    'api/motorcycles.php'
];

$motorcycleLoaded = false;
foreach ($motorcyclePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $motorcycleLoaded = true;
        break;
    }
}

if (!$motorcycleLoaded) {
    die('ไม่พบไฟล์ motorcycles.php');
}

// โหลด bookings.php
$bookingPaths = [
    __DIR__ . '/../api/bookings.php',
    __DIR__ . '/../../api/bookings.php',
    'api/bookings.php'  
];

$bookingLoaded = false;
foreach ($bookingPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $bookingLoaded = true;
        break;
    }
}

if (!$bookingLoaded) {
    die('ไม่พบไฟล์ bookings.php');
}

// ดึงข้อมูลรถ
$motorcycle_id = $_GET['id'] ?? null;
$motorcycle = null;

if ($motorcycle_id) {
    try {
        $motorcycle = MotorcycleService::getMotorcycleById($motorcycle_id);
        if (!$motorcycle) {
            $error = 'ไม่พบข้อมูลรถจักรยานยนต์';
        }
    } catch (Exception $e) {
        $error = 'ไม่สามารถโหลดข้อมูลรถได้: ' . $e->getMessage();
    }
} else {
    $error = 'ไม่พบรหัสรถ';
}

// กำหนดค่าเริ่มต้นสำหรับส่วนลด
if (!isset($_SESSION['discounts'])) {
    $_SESSION['discounts'] = [
        [
            'code' => 'WELCOME50',
            'type' => 'fixed',
            'value' => 50,
            'min_days' => 1,
            'max_discount' => null,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'usage_limit' => 100,
            'used_count' => 0,
            'is_active' => true
        ],
        [
            'code' => 'WEEKEND10',
            'type' => 'percentage',
            'value' => 10,
            'min_days' => 2,
            'max_discount' => 200,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'usage_limit' => 50,
            'used_count' => 0,
            'is_active' => true
        ]
    ];
}
$all_discounts = $_SESSION['discounts'];

// ประมวลผลฟอร์มจอง
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $pickupLocation = $_POST['pickup_location'] ?? ''; // ✅ เพิ่ม
    $returnLocation = $_POST['return_location'] ?? 'ร้านเทมป์เทชัน';
    $discount_code_input = strtoupper(trim($_POST['discount_code'] ?? ''));

    if (empty($startDate) || empty($endDate) || empty($pickupLocation) || !$motorcycle) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $diff = $end->diff($start);
        $totalDays = $diff->days;

        if ($totalDays <= 0) {
            $error = 'วันที่คืนรถต้องอยู่หลังวันที่รับรถ';
        } else {
            try {
                // สร้างข้อมูลการจอง
                $bookingData = [
                    'motorcycleId' => $motorcycle['motorcycleId'],
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'pickupLocation' => $pickupLocation, // ✅ เพิ่ม
                    'returnLocation' => $returnLocation
                ];

                // เพิ่มโค้ดส่วนลดถ้ามี
                if (!empty($discount_code_input)) {
                    $found_code = null;
                    foreach ($all_discounts as $code) {
                        if ($code['code'] === $discount_code_input && $code['is_active']) {
                            $found_code = $code;
                            break;
                        }
                    }
                    
                    if ($found_code) {
                        $now = time();
                        $is_valid_date = (strtotime($found_code['start_date']) <= $now && $now <= strtotime($found_code['end_date']));
                        $is_under_limit = !$found_code['usage_limit'] || ($found_code['used_count'] < $found_code['usage_limit']);
                        $meets_min_days = ($totalDays >= $found_code['min_days']);

                        if ($is_valid_date && $is_under_limit && $meets_min_days) {
                            $bookingData['discountCode'] = $discount_code_input;
                            
                            foreach ($_SESSION['discounts'] as &$d) {
                                if ($d['code'] === $discount_code_input) {
                                    $d['used_count']++;
                                    break;
                                }
                            }
                        }
                    }
                }

                // สร้างการจองผ่าน API
                $booking = BookingService::createBooking($bookingData);
                
                if ($booking) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => 'จองสำเร็จ! เราจะติดต่อกลับภายใน 24 ชั่วโมง'
                    ];
                    header("Location: index.php?page=profile");
                    exit;
                } else {
                    $error = 'ไม่สามารถทำการจองได้ กรุณาลองใหม่';
                }
            } catch (Exception $e) {
                $error = 'เกิดข้อผิดพลาดในการจอง: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <?php if (!$motorcycle): ?>
            <div class="min-h-[60vh] flex items-center justify-center">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">ไม่พบข้อมูลรถ</h2>
                    <a href="index.php?page=motorcycles"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                        กลับไปเลือกรถ
                    </a>
                </div>
            </div>

        <?php else: ?>

            <!-- Back Button -->
            <a href="index.php?page=motorcycles" class="flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-6">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
                กลับไปเลือกรถ
            </a>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Motorcycle Details -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="relative h-64">
                        <img src="<?php echo htmlspecialchars($motorcycle['imageUrl'] ?? '../img/default-bike.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                            class="w-full h-full object-cover" 
                            onerror="this.src='../img/default-bike.jpg'"/>
                        <div class="absolute top-4 right-4">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                <?php echo ($motorcycle['isAvailable'] ?? false) ? 'พร้อมใช้งาน' : 'ไม่ว่าง'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <h1 class="text-2xl font-bold text-gray-900">
                                <?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>
                            </h1>
                            <div class="flex items-center">
                                <i data-lucide="star" class="h-5 w-5 text-yellow-400 fill-current"></i>
                                <span class="text-sm text-gray-600 ml-1">4.8</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-6 text-gray-600 mb-6">
                            <div class="flex items-center gap-2">
                                <i data-lucide="fuel" class="h-5 w-5"></i>
                                <span><?php echo $motorcycle['engineCc'] ?? 'N/A'; ?>cc</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="h-5 w-5"></i>
                                <span><?php echo $motorcycle['year'] ?? 'N/A'; ?></span>
                            </div>
                        </div>
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-900 mb-3">รายละเอียด</h3>
                            <div class="grid grid-cols-1 gap-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                    <span class="text-gray-700"><?php echo htmlspecialchars($motorcycle['description'] ?? 'รถจักรยานยนต์คุณภาพดี'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                    <span class="text-gray-700">ประเภท: <?php echo ($motorcycle['engineCc'] ?? 0) <= 150 ? 'ออโตเมติก' : 'manual'; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">ราคาต่อวัน</span>
                                <span id="price-per-day" data-price-per-day="<?php echo $motorcycle['pricePerDay']; ?>"
                                    class="text-2xl font-bold text-blue-600">
                                    ฿<?php echo $motorcycle['pricePerDay']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">จองรถจักรยานยนต์</h2>

                    <?php if (!empty($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="index.php?page=booking&id=<?php echo $motorcycle['motorcycleId']; ?>"
                        class="space-y-6">
                        
                        <!-- User Info -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <i data-lucide="user" class="h-5 w-5 text-gray-600"></i>
                                <span class="font-medium">ข้อมูลผู้จอง</span>
                            </div>
                            <p class="text-gray-700"><?php echo htmlspecialchars($_SESSION['user']['firstName'] ?? $_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'ผู้ใช้'); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['user']['email'] ?? $_SESSION['user_email'] ?? ''); ?></p>
                        </div>

                        <!-- Date Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar" class="inline h-4 w-4 mr-1"></i>
                                    วันที่รับรถ
                                </label>
                                <input type="date" id="start-date" name="start_date" min="<?php echo $today; ?>" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar" class="inline h-4 w-4 mr-1"></i>
                                    วันที่คืนรถ
                                </label>
                                <input type="date" id="end-date" name="end_date" min="<?php echo $today; ?>" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                            </div>
                        </div>

                        <!-- ✅ เพิ่ม: สถานที่รับรถ -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="map-pin" class="inline h-4 w-4 mr-1"></i>
                                สถานที่รับรถ 
                            </label>
                            <select name="pickup_location" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="ร้านเทมป์เทชัน">ร้านเทมป์เทชัน</option>
                                <option value="สนามบินหาดใหญ่">สนามบินหาดใหญ่</option>
                                <option value="สถานีรถไฟหาดใหญ่">สถานีรถไฟหาดใหญ่</option>
                                <option value="สถานีขนส่งหาดใหญ่">สถานีขนส่งหาดใหญ่</option>
                                <option value="โรงแรมในเมืองหาดใหญ่">โรงแรมในเมืองหาดใหญ่</option>
                            </select>
                        </div>

                        <!-- Return Location -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="map-pin" class="inline h-4 w-4 mr-1"></i>
                                สถานที่คืนรถ
                            </label>
                            <select name="return_location"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="ร้านเทมป์เทชัน">ร้านเทมป์เทชัน</option>
                                <option value="สนามบินหาดใหญ่">สนามบินหาดใหญ่</option>
                                <option value="โรงแรม (มีค่าบริการเพิ่มเติม)">โรงแรม (มีค่าบริการเพิ่มเติม)</option>
                            </select>
                        </div>
                        
                        <!-- Discount Code Section -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="percent" class="inline h-4 w-4 mr-1"></i>
                                โค้ดส่วนลด (ถ้ามี)
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="discount-code-input" name="discount_code"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="กรอกโค้ดส่วนลด" />
                                <button type="button" id="apply-discount-btn"
                                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 rounded-lg">
                                    ใช้
                                </button>
                            </div>
                            <p id="discount-message" class="text-sm mt-2"></p>
                        </div>

                        <!-- Price Summary -->
                        <div id="price-summary-container" class="bg-blue-50 p-4 rounded-lg hidden">
                            <h3 class="font-semibold text-gray-900 mb-3">สรุปการจอง</h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>จำนวนวัน:</span>
                                    <span id="summary-days">0 วัน</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>ราคาต่อวัน:</span>
                                    <span>฿<?php echo $motorcycle['pricePerDay']; ?></span>
                                </div>
                                <div id="summary-coupon-row" class="flex justify-between text-green-600 hidden">
                                    <span>โค้ดส่วนลด (<span id="summary-coupon-code"></span>):</span>
                                    <span id="summary-coupon-discount">-฿0</span>
                                </div>
                                <div id="summary-discount-row" class="flex justify-between text-green-600 hidden">
                                    <span>ส่วนลดอัตโนมัติ:</span>
                                    <span id="summary-discount">-฿0</span>
                                </div>
                                <div class="border-t pt-2 flex justify-between font-bold text-lg">
                                    <span>ราคารวม:</span>
                                    <span id="summary-total" class="text-blue-600">฿0</span>
                                </div>
                            </div>
                            <div id="summary-offer-text"
                                class="mt-3 p-2 bg-green-100 rounded text-green-800 text-sm hidden">
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded">
                            <p class="mb-2"><strong>เงื่อนไขการจอง:</strong></p>
                            <ul class="space-y-1 list-disc list-inside">
                                <li>สามารถยกเลิกการจองได้ก่อน 1 วัน</li>
                                <li>ต้องมีใบขับขี่ที่ถูกต้อง</li>
                                <li>ต้องมีบัตรประชาชนหรือหนังสือเดินทาง</li>
                                <li>ค่าเสียหายจะหักจากเงินมัดจำ</li>
                            </ul>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                            ยืนยันการจอง
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    const all_discounts_json = <?php echo json_encode(array_values($all_discounts)); ?>;
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const startDateInput = document.getElementById('start-date');
        const endDateInput = document.getElementById('end-date');
        const pricePerDayEl = document.getElementById('price-per-day');

        const summaryContainer = document.getElementById('price-summary-container');
        const summaryDays = document.getElementById('summary-days');
        const summaryDiscountRow = document.getElementById('summary-discount-row');
        const summaryDiscount = document.getElementById('summary-discount');
        const summaryTotal = document.getElementById('summary-total');
        const summaryOfferText = document.getElementById('summary-offer-text');
        
        const summaryCouponRow = document.getElementById('summary-coupon-row');
        const summaryCouponCode = document.getElementById('summary-coupon-code');
        const summaryCouponDiscount = document.getElementById('summary-coupon-discount');
        const discountCodeInput = document.getElementById('discount-code-input');
        const applyDiscountBtn = document.getElementById('apply-discount-btn');
        const discountMessage = document.getElementById('discount-message');
        
        let appliedCoupon = null;
        let lastValidCode = '';

        function calculatePrice() {
            if (!startDateInput || !endDateInput || !pricePerDayEl || !summaryContainer) return;

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            const pricePerDay = parseFloat(pricePerDayEl.getAttribute('data-price-per-day'));

            if (startDate && endDate && pricePerDay) {
                const start = new Date(startDate);
                const end = new Date(endDate);

                if (end <= start) {
                    summaryContainer.classList.add('hidden');
                    return;
                }

                const diffTime = Math.abs(end.getTime() - start.getTime());
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays > 0) {
                    let price = diffDays * pricePerDay;
                    let autoDiscountValue = 0;
                    let offerText = '';
                    
                    // ส่วนลดอัตโนมัติ (3 วัน 50 บาท)
                    if (diffDays >= 3) {
                        autoDiscountValue = Math.floor(diffDays / 3) * 50;
                        price -= autoDiscountValue;
                        offerText = `🎉 ส่วนลดอัตโนมัติ ${autoDiscountValue} บาท (เช่า ${diffDays} วัน)`;
                    }
                    
                    let priceAfterAutoDiscount = price;
                    let couponDiscountValue = 0;

                    // ส่วนลดคูปอง
                    if (appliedCoupon && lastValidCode === discountCodeInput.value.toUpperCase()) {
                        if (diffDays >= appliedCoupon.min_days) {
                            if (appliedCoupon.type === 'fixed') {
                                couponDiscountValue = appliedCoupon.value;
                            } else if (appliedCoupon.type === 'percentage') {
                                couponDiscountValue = priceAfterAutoDiscount * (appliedCoupon.value / 100);
                                if (appliedCoupon.max_discount && couponDiscountValue > appliedCoupon.max_discount) {
                                    couponDiscountValue = appliedCoupon.max_discount;
                                }
                            }
                            price -= couponDiscountValue;
                        } else {
                            appliedCoupon = null;
                            lastValidCode = '';
                            discountMessage.textContent = `โค้ดนี้ต้องเช่าอย่างน้อย ${appliedCoupon.min_days} วัน`;
                            discountMessage.className = 'text-sm mt-2 text-red-600';
                        }
                    } else if (lastValidCode && lastValidCode !== discountCodeInput.value.toUpperCase()) {
                        appliedCoupon = null;
                        lastValidCode = '';
                        discountMessage.textContent = 'กรุณากด "ใช้" เพื่อยืนยันโค้ด';
                        discountMessage.className = 'text-sm mt-2 text-yellow-600';
                    }

                    // Update UI
                    summaryContainer.classList.remove('hidden');
                    summaryDays.textContent = `${diffDays} วัน`;
                    summaryTotal.textContent = `฿${price.toFixed(0)}`;

                    if (autoDiscountValue > 0) {
                        summaryDiscountRow.classList.remove('hidden');
                        summaryDiscount.textContent = `-฿${autoDiscountValue}`;
                        summaryOfferText.textContent = offerText;
                        summaryOfferText.classList.remove('hidden');
                    } else {
                        summaryDiscountRow.classList.add('hidden');
                        summaryOfferText.classList.add('hidden');
                    }
                    
                    if (couponDiscountValue > 0 && appliedCoupon) {
                        summaryCouponRow.classList.remove('hidden');
                        summaryCouponCode.textContent = appliedCoupon.code;
                        summaryCouponDiscount.textContent = `-฿${couponDiscountValue.toFixed(0)}`;
                    } else {
                        summaryCouponRow.classList.add('hidden');
                    }

                } else {
                    summaryContainer.classList.add('hidden');
                }
            }
        }

        function applyDiscountCode() {
            const code = discountCodeInput.value.toUpperCase();
            if (!code) {
                discountMessage.textContent = 'กรุณากรอกโค้ด';
                discountMessage.className = 'text-sm mt-2 text-red-600';
                return;
            }

            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            if (!startDate || !endDate) {
                discountMessage.textContent = 'กรุณาเลือกวันที่ก่อนใช้โค้ด';
                discountMessage.className = 'text-sm mt-2 text-red-600';
                return;
            }

            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end.getTime() - start.getTime());
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays <= 0) {
                 discountMessage.textContent = 'วันที่ไม่ถูกต้อง';
                 discountMessage.className = 'text-sm mt-2 text-red-600';
                 return;
            }
            
            const foundCode = all_discounts_json.find(d => d.code === code);
            
            if (!foundCode) {
                discountMessage.textContent = 'โค้ดส่วนลดไม่ถูกต้อง';
                discountMessage.className = 'text-sm mt-2 text-red-600';
                appliedCoupon = null;
            } else {
                const now = new Date();
                const codeStart = new Date(foundCode.start_date);
                const codeEnd = new Date(foundCode.end_date);
                codeEnd.setHours(23, 59, 59);

                if (!foundCode.is_active) {
                     discountMessage.textContent = 'โค้ดนี้ถูกปิดใช้งาน';
                     discountMessage.className = 'text-sm mt-2 text-red-600';
                     appliedCoupon = null;
                } else if (now < codeStart) {
                     discountMessage.textContent = 'โค้ดนี้ยังไม่เริ่มใช้งาน';
                     discountMessage.className = 'text-sm mt-2 text-red-600';
                     appliedCoupon = null;
                } else if (now > codeEnd) {
                     discountMessage.textContent = 'โค้ดนี้หมดอายุแล้ว';
                     discountMessage.className = 'text-sm mt-2 text-red-600';
                     appliedCoupon = null;
                } else if (foundCode.usage_limit && foundCode.used_count >= foundCode.usage_limit) {
                     discountMessage.textContent = 'โค้ดนี้ถูกใช้เต็มจำนวนแล้ว';
                     discountMessage.className = 'text-sm mt-2 text-red-600';
                     appliedCoupon = null;
                } else if (diffDays < foundCode.min_days) {
                     discountMessage.textContent = `โค้ดนี้ต้องเช่าอย่างน้อย ${foundCode.min_days} วัน`;
                     discountMessage.className = 'text-sm mt-2 text-red-600';
                     appliedCoupon = null;
                } else {
                    discountMessage.textContent = 'ใช้โค้ดส่วนลดสำเร็จ!';
                    discountMessage.className = 'text-sm mt-2 text-green-600';
                    appliedCoupon = foundCode;
                    lastValidCode = foundCode.code;
                }
            }
            
            calculatePrice();
        }

        if (applyDiscountBtn) {
             applyDiscountBtn.addEventListener('click', applyDiscountCode);
        }
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function () {
                endDateInput.min = startDateInput.value;
                calculatePrice();
            });
            endDateInput.addEventListener('change', calculatePrice);
        }
         if (discountCodeInput) {
            discountCodeInput.addEventListener('input', function() {
                if (discountCodeInput.value.toUpperCase() !== lastValidCode) {
                    appliedCoupon = null;
                    discountMessage.textContent = '';
                }
            });
         }
    });
</script>