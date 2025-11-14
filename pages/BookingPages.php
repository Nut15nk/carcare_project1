<?php
// pages/BookingPage.php
// ไม่ต้อง session_start() เพราะ index.php เรียกไปแล้ว

// --- (1) ฐานข้อมูลจำลอง (Mock Database) ---
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
    ]
];

// --- (2) ฟังก์ชัน Helpers ---
/**
 * คำนวณส่วนลด (ลด 50 บาท ทุกๆ 3 วัน)
 */
function calculateDiscount($days, $pricePerDay)
{
    $normalPrice = $days * $pricePerDay;
    $discount = 0;
    if ($days >= 3) {
        $discount = floor($days / 3) * 50;
    }
    return [
        'normalPrice' => $normalPrice,
        'finalPrice' => $normalPrice - $discount, // ราคาก่อนหักคูปอง
        'discount' => $discount // ส่วนลดอัตโนมัติ
    ];
}

// --- (3) ดึง ID และ ค้นหารถ ---
$motorcycle_id = $_GET['id'] ?? null;
$motorcycle = null;
if ($motorcycle_id) {
    foreach ($motorcycles_data as $m) {
        if ($m['id'] == $motorcycle_id) {
            $motorcycle = $m;
            break;
        }
    }
}

// (4) กำหนดตัวแปร (เหมือน useState)
$error = '';
$today = date('Y-m-d');

// (5) Initialize bookings & discounts
if (!isset($_SESSION['mock_bookings'])) {
    $_SESSION['mock_bookings'] = [];
}
if (!isset($_SESSION['discounts'])) {
    $_SESSION['discounts'] = [];
}
$all_discounts = $_SESSION['discounts'];


// (6) ประมวลผลฟอร์ม (POST Request)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // (A) ดึงข้อมูลจากฟอร์ม
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $returnLocation = $_POST['return_location'] ?? 'ร้านเทมป์เทชัน';
    $discount_code_input = strtoupper(trim($_POST['discount_code'] ?? ''));

    // (B) ดึงข้อมูลผู้ใช้จาก Session
    $userEmail = $_SESSION['user_email'] ?? 'guest@example.com';
    $userName = $_SESSION['user_name'] ?? 'Guest User';

    // (C) ตรวจสอบข้อมูล
    if (empty($startDate) || empty($endDate) || !$motorcycle) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {

        // (D) คำนวณวันและส่วนลดอัตโนมัติ
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $diff = $end->diff($start);
        $totalDays = $diff->days;
        
        if ($totalDays <= 0) {
            $error = 'วันที่คืนรถต้องอยู่หลังวันที่รับรถ';
        }

        $priceData = calculateDiscount($totalDays, $motorcycle['pricePerDay']);
        $price_after_auto_discount = $priceData['finalPrice'];
        $auto_discount = $priceData['discount'];
        
        $specialOffers = '';
        if ($auto_discount > 0) {
            $specialOffers = "ส่วนลด {$auto_discount} บาท (เช่า {$totalDays} วัน)";
        }
        
        // (E) ตรวจสอบโค้ดส่วนลด (Server-side Validation)
        $coupon_discount = 0;
        $applied_discount_code = null;

        if (!empty($discount_code_input) && empty($error)) {
            $found_code = null;
            foreach ($all_discounts as $code) {
                if ($code['code'] === $discount_code_input) {
                    $found_code = $code;
                    break;
                }
            }
            
            if (!$found_code) {
                $error = 'โค้ดส่วนลดไม่ถูกต้อง';
            } else {
                $now = time();
                $is_active = $found_code['is_active'];
                $is_valid_date = (strtotime($found_code['start_date']) <= $now && $now <= strtotime($found_code['end_date']));
                $is_under_limit = !$found_code['usage_limit'] || ($found_code['used_count'] < $found_code['usage_limit']);
                $meets_min_days = ($totalDays >= $found_code['min_days']);

                if (!$is_active || !$is_valid_date || !$is_under_limit) {
                    $error = 'โค้ดส่วนลดนี้ไม่สามารถใช้งานได้ในขณะนี้';
                } elseif (!$meets_min_days) {
                    $error = 'ต้องเช่าอย่างน้อย ' . $found_code['min_days'] . ' วันเพื่อใช้โค้ดนี้';
                } else {
                    // โค้ดถูกต้อง, คำนวณส่วนลด
                    if ($found_code['type'] === 'fixed') {
                        $coupon_discount = $found_code['value'];
                    } elseif ($found_code['type'] === 'percentage') {
                        $coupon_discount = $price_after_auto_discount * ($found_code['value'] / 100);
                        if ($found_code['max_discount'] && $coupon_discount > $found_code['max_discount']) {
                            $coupon_discount = $found_code['max_discount'];
                        }
                    }
                    $applied_discount_code = $found_code['code'];
                }
            }
        }
        
        // (F) คำนวณราคาสุดท้าย
        $totalPrice = $price_after_auto_discount - $coupon_discount;

        // (G) จัดการไฟล์อัพโหลด (Payment Proof)
        $paymentProofPath = null;
        if (empty($error)) { // ตรวจสอบไฟล์ต่อเมื่อไม่มี error อื่น
            if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == UPLOAD_ERR_OK) {
                $paymentProofPath = basename($_FILES['payment_proof']['name']); // จำลอง (ไม่ได้ย้ายไฟล์จริง)
            } else {
                $error = 'กรุณาอัพโหลดหลักฐานการโอนเงิน';
            }
        }

        // (H) บันทึกการจอง (ถ้าไม่มี Error)
        if (empty($error)) {
            
            // อัปเดตการใช้โค้ด (ถ้ามี)
            if ($applied_discount_code) {
                foreach ($_SESSION['discounts'] as &$d) { // ใช้ reference (&)
                    if ($d['code'] === $applied_discount_code) {
                        $d['used_count']++;
                        break;
                    }
                }
            }

            // บันทึกการจอง
            $bookingId = 'BK' . time();
            $booking = [
                'id' => $bookingId,
                'motorcycleId' => $motorcycle['id'],
                'motorcycleName' => $motorcycle['brand'] . ' ' . $motorcycle['model'],
                'userEmail' => $userEmail,
                'userName' => $userName,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalDays' => $totalDays,
                'pricePerDay' => $motorcycle['pricePerDay'],
                'totalPrice' => $totalPrice,
                'discount' => $auto_discount, // ส่วนลดอัตโนมัติ
                'coupon_code' => $applied_discount_code,
                'coupon_discount' => $coupon_discount,
                'returnLocation' => $returnLocation,
                'paymentProof' => $paymentProofPath,
                'status' => 'confirmed', 
                'createdAt' => date('Y-m-d H:i:s'),
                'specialOffers' => $specialOffers // ข้อความส่วนลดอัตโนมัติ
            ];

            $_SESSION['mock_bookings'][] = $booking;

            // Redirect
            $_SESSION['booking_success'] = 'สำเร็จ! การจองของคุณได้รับการยืนยัน';
            header('Location: index.php?page=profile');
            exit;
        }
    }
}

// (7) Get user's bookings from session
$userBookings = [];
if (isset($_SESSION['mock_bookings']) && isset($_SESSION['user_email'])) {
    foreach ($_SESSION['mock_bookings'] as $booking) {
        if (isset($booking['userEmail']) && $booking['userEmail'] === $_SESSION['user_email']) {
            $userBookings[] = $booking;
        }
    }
}

?>

<!-- (8) เริ่มส่วน HTML (View) -->
<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- (9) แสดงผลหากไม่พบรถ -->
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

        <!-- (10) แสดงผลหากพบรถ -->
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
                        <img src="<?php echo htmlspecialchars($motorcycle['image']); ?>"
                            alt="<?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                            class="w-full h-full object-cover" />
                        <div class="absolute top-4 right-4">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                พร้อมใช้งาน
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
                                <span><?php echo $motorcycle['cc']; ?>cc</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="users" class="h-5 w-5"></i>
                                <span><?php echo htmlspecialchars($motorcycle['type']); ?></span>
                            </div>
                        </div>
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-900 mb-3">คุณสมบัติ</h3>
                            <div class="grid grid-cols-1 gap-2">
                                <?php foreach ($motorcycle['features'] as $feature): ?>
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                        <span class="text-gray-700"><?php echo htmlspecialchars($feature); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">ราคาต่อวัน</span>
                                <!-- (สำคัญ) เพิ่ม data- attribute นี้สำหรับ JavaScript -->
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

                    <!-- (11) แสดง Error (ถ้ามี) -->
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- (12) เพิ่ม enctype="multipart/form-data" สำหรับไฟล์อัพโหลด -->
                    <form method="POST" action="index.php?page=booking&id=<?php echo $motorcycle['id']; ?>"
                        enctype="multipart/form-data" class="space-y-6">
                        
                        <!-- User Info (จาก Session) -->
                        <?php if (isset($_SESSION['user_email'])): ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <i data-lucide="user" class="h-5 w-5 text-gray-600"></i>
                                <span class="font-medium">ข้อมูลผู้จอง</span>
                            </div>
                            <p class="text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                        </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 p-4 rounded-lg text-yellow-800">
                                <i data-lucide="alert-triangle" class="inline h-5 w-5"></i>
                                คุณยังไม่ได้เข้าสู่ระบบ <a href="index.php?page=login" class="font-bold underline">คลิกที่นี่เพื่อเข้าสู่ระบบ</a>
                            </div>
                        <?php endif; ?>

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
                        
                        <!-- (13) *** NEW: Discount Code Section *** -->
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
                            <!-- (14) *** NEW: Discount Message Area *** -->
                            <p id="discount-message" class="text-sm mt-2"></p>
                        </div>

                        <!-- Price Summary (นี่คือส่วนที่จะอัปเดตแบบ Live) -->
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
                                <!-- (15) *** NEW: Coupon Discount Row *** -->
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
                                <!-- ข้อความโปรโมชั่น -->
                            </div>
                        </div>

                        <!-- Payment Section -->
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
                                <i data-lucide="credit-card" class="h-5 w-5"></i>
                                การชำระเงินมัดจำ
                            </h3>
                            <div class="bg-yellow-50 p-4 rounded-lg mb-4">
                                <p class="text-sm text-yellow-800 mb-2">
                                    <strong>ชำระเงินมัดจำ 500 บาท</strong> ผ่านการโอนเงิน
                                </p>
                                <div class="text-sm text-yellow-700">
                                    <p>ธนาคารกสิกรไทย</p>
                                    <p>เลขที่บัญชี: 123-4-56789-0</p>
                                    <p>ชื่อบัญชี: ร้านเทมป์เทชัน</p>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="upload" class="inline h-4 w-4 mr-1"></i>
                                    อัพโหลดหลักฐานการโอนเงิน *
                                </label>
                                <input type="file" name="payment_proof" accept="image/*" required id="payment-proof-input"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                                <p id="payment-proof-filename" class="text-sm text-green-600 mt-1"></p>
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
                                <?php if (!isset($_SESSION['user_email'])) echo 'disabled'; // (16) ปิดปุ่มถ้าไม่ล็อกอิน ?>
                                class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white py-3 px-4 rounded-lg font-medium transition-colors">
                            <?php echo isset($_SESSION['user_email']) ? 'ยืนยันการจอง' : 'กรุณาเข้าสู่ระบบก่อน'; ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; // จบ if ($motorcycle) ?>
    </div>
</div>

<!-- (17) *** NEW: Inject Discount Data for JS *** -->
<script>
    const all_discounts_json = <?php echo json_encode(array_values($all_discounts)); ?>;
</script>

<!-- (18) JavaScript (อัปเดตสำหรับ Discount Code) -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const startDateInput = document.getElementById('start-date');
        const endDateInput = document.getElementById('end-date');
        const pricePerDayEl = document.getElementById('price-per-day');

        // (A) ส่วนสรุปราคา
        const summaryContainer = document.getElementById('price-summary-container');
        const summaryDays = document.getElementById('summary-days');
        const summaryDiscountRow = document.getElementById('summary-discount-row');
        const summaryDiscount = document.getElementById('summary-discount');
        const summaryTotal = document.getElementById('summary-total');
        const summaryOfferText = document.getElementById('summary-offer-text');
        
        // (B) ส่วนคูปอง (ใหม่)
        const summaryCouponRow = document.getElementById('summary-coupon-row');
        const summaryCouponCode = document.getElementById('summary-coupon-code');
        const summaryCouponDiscount = document.getElementById('summary-coupon-discount');
        const discountCodeInput = document.getElementById('discount-code-input');
        const applyDiscountBtn = document.getElementById('apply-discount-btn');
        const discountMessage = document.getElementById('discount-message');
        
        let appliedCoupon = null; // สถานะของคูปองที่ใช้ได้
        let lastValidCode = '';   // โค้ดที่ใช้ได้ล่าสุด

        // (C) ส่วนไฟล์อัพโหลด
        const paymentProofInput = document.getElementById('payment-proof-input');
        const paymentProofFilename = document.getElementById('payment-proof-filename');

        if (paymentProofInput && paymentProofFilename) {
            paymentProofInput.addEventListener('change', function () {
                if (paymentProofInput.files && paymentProofInput.files.length > 0) {
                    paymentProofFilename.textContent = '✓ อัพโหลดไฟล์: ' + paymentProofInput.files[0].name;
                } else {
                    paymentProofFilename.textContent = '';
                }
            });
        }

        // (D) ฟังก์ชันคำนวณราคาทั้งหมด (อัปเดตใหม่)
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
                    // 1. คำนวณส่วนลดอัตโนมัติ (3 วัน 50 บาท)
                    let price = diffDays * pricePerDay;
                    let autoDiscountValue = 0;
                    let offerText = '';
                    if (diffDays >= 3) {
                        autoDiscountValue = Math.floor(diffDays / 3) * 50;
                        price -= autoDiscountValue;
                        offerText = `🎉 ส่วนลดอัตโนมัติ ${autoDiscountValue} บาท (เช่า ${diffDays} วัน)`;
                    }
                    
                    let priceAfterAutoDiscount = price;
                    let couponDiscountValue = 0;

                    // 2. คำนวณส่วนลดคูปอง (ถ้ามี)
                    // (ตรวจสอบว่าโค้ดที่กรอก ตรงกับโค้ดที่ "ใช้" ได้หรือไม่)
                    if (appliedCoupon && lastValidCode === discountCodeInput.value.toUpperCase()) {
                        // เช็ค min_days อีกครั้ง (เผื่อผู้ใช้เปลี่ยนวัน)
                        if (diffDays >= appliedCoupon.min_days) {
                            if (appliedCoupon.type === 'fixed') {
                                couponDiscountValue = appliedCoupon.value;
                            } else if (appliedCoupon.type === 'percentage') {
                                couponDiscountValue = priceAfterAutoDiscount * (appliedCoupon.value / 100);
                                if (appliedCoupon.max_discount && couponDiscountValue > appliedCoupon.max_discount) {
                                    couponDiscountValue = appliedCoupon.max_discount;
                                }
                            }
                            price -= couponDiscountValue; // หักส่วนลดคูปอง
                        } else {
                            // ถ้าวันไม่ถึงขั้นต่ำ, ยกเลิกคูปอง
                            appliedCoupon = null;
                            lastValidCode = '';
                            discountMessage.textContent = `โค้ดนี้ต้องเช่าอย่างน้อย ${appliedCoupon.min_days} วัน`;
                            discountMessage.className = 'text-sm mt-2 text-red-600';
                        }
                    } else if (lastValidCode && lastValidCode !== discountCodeInput.value.toUpperCase()) {
                        // ถ้าผู้ใช้เปลี่ยนโค้ด แต่ยังไม่กด "ใช้"
                        appliedCoupon = null;
                        lastValidCode = '';
                        discountMessage.textContent = 'กรุณากด "ใช้" เพื่อยืนยันโค้ด';
                        discountMessage.className = 'text-sm mt-2 text-yellow-600';
                    }

                    // 3. Update UI
                    summaryContainer.classList.remove('hidden');
                    summaryDays.textContent = `${diffDays} วัน`;
                    summaryTotal.textContent = `฿${price.toFixed(0)}`;

                    // UI: ส่วนลดอัตโนมัติ
                    if (autoDiscountValue > 0) {
                        summaryDiscountRow.classList.remove('hidden');
                        summaryDiscount.textContent = `-฿${autoDiscountValue}`;
                        summaryOfferText.textContent = offerText;
                        summaryOfferText.classList.remove('hidden');
                    } else {
                        summaryDiscountRow.classList.add('hidden');
                        summaryOfferText.classList.add('hidden');
                    }
                    
                    // UI: ส่วนลดคูปอง
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

        // (E) *** NEW: ฟังก์ชันตรวจสอบโค้ดส่วนลด ***
        function applyDiscountCode() {
            const code = discountCodeInput.value.toUpperCase();
            if (!code) {
                discountMessage.textContent = 'กรุณากรอกโค้ด';
                discountMessage.className = 'text-sm mt-2 text-red-600';
                return;
            }

            // (ตรวจสอบวัน)
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
            
            // (ค้นหาโค้ดใน JSON)
            const foundCode = all_discounts_json.find(d => d.code === code);
            
            if (!foundCode) {
                discountMessage.textContent = 'โค้ดส่วนลดไม่ถูกต้อง';
                discountMessage.className = 'text-sm mt-2 text-red-600';
                appliedCoupon = null;
            } else {
                // (ตรวจสอบความถูกต้องของโค้ด)
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
                    // !! สำเร็จ !!
                    discountMessage.textContent = 'ใช้โค้ดส่วนลดสำเร็จ!';
                    discountMessage.className = 'text-sm mt-2 text-green-600';
                    appliedCoupon = foundCode; // บันทึกคูปองที่ใช้ได้
                    lastValidCode = foundCode.code; // บันทึกชื่อโค้ดที่ใช้ได้
                }
            }
            
            // คำนวณราคาใหม่ทุกครั้งที่กดปุ่ม
            calculatePrice();
        }

        // (F) Event Listeners
        if (applyDiscountBtn) {
             applyDiscountBtn.addEventListener('click', applyDiscountCode);
        }
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function () {
                endDateInput.min = startDateInput.value;
                calculatePrice(); // คำนวณใหม่ (จะล้างคูปองถ้าโค้ดไม่ตรง)
            });
            endDateInput.addEventListener('change', calculatePrice);
        }
         if (discountCodeInput) {
             // ถ้าผู้ใช้พิมพ์โค้ดใหม่, ให้ล้างสถานะ "ใช้ได้" จนกว่าจะกดยืนยัน
            discountCodeInput.addEventListener('input', function() {
                if (discountCodeInput.value.toUpperCase() !== lastValidCode) {
                    appliedCoupon = null;
                    discountMessage.textContent = '';
                }
            });
         }
    });
</script>