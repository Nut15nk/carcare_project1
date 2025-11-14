<?php
// pages/BookingPage.php

// (1) Auth Guard ถูกเรียกจาก index.php แล้ว ดังนั้นเราไม่ต้องตรวจสอบอีก

// (2) คัดลอก "ฐานข้อมูลจำลอง" และ "ฟังก์ชัน" จากไฟล์อื่น
// (เหมือน useBooking() ใน React)

// ข้อมูลรถ (คัดลอกจาก MotorcyclesPage.php)
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

/**
 * คำนวณส่วนลด (ลด 50 บาท ทุกๆ 3 วัน)
 * (คัดลอกจาก home.php)
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

// (3) ดึง ID และ ค้นหารถ
// (เหมือน useParams() และ find())
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
$today = date('Y-m-d'); // สำหรับ date input min

// (5) Initialize bookings in session if not exist
if (!isset($_SESSION['mock_bookings'])) {
    $_SESSION['mock_bookings'] = [];
}

// (5) ประมวลผลฟอร์ม (POST Request)
// (เหมือน handleSubmit)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ดึงข้อมูลจากฟอร์ม
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $returnLocation = $_POST['return_location'] ?? 'ร้านเทมป์เทชัน';
    
    // ดึงข้อมูลผู้ใช้จาก Session (เหมือน useAuth())
    $userEmail = $_SESSION['user_email'] ?? 'guest@example.com';
    $userName = $_SESSION['user_name'] ?? 'Guest User';
    
    // ตรวจสอบข้อมูล
    if (empty($startDate) || empty($endDate) || !$motorcycle) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        
        // (6) คำนวณราคาสุดท้าย (Server-side)
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $diff = $end->diff($start);
        $totalDays = $diff->days;
        
        $priceData = calculateDiscount($totalDays, $motorcycle['pricePerDay']);
        $totalPrice = $priceData['finalPrice'];
        $discount = $priceData['discount'];
        
        $specialOffers = '';
        if ($discount > 0) {
            $specialOffers = "ส่วนลด {$discount} บาท สำหรับการเช่า {$totalDays} วัน (รับส่วนลด 50 บาท ทุก ๆ 3 วันที่เช่า )";
        }

        // (7) จัดการไฟล์อัพโหลด (Payment Proof) - Mock: just check file exists
        $paymentProofPath = null;
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == UPLOAD_ERR_OK) {
            // Mock: just use filename (no actual upload)
            $paymentProofPath = basename($_FILES['payment_proof']['name']);
        } else {
            $error = 'กรุณาอัพโหลดหลักฐานการโอนเงิน';
        }
        
        // (8) บันทึกการจอง (ถ้าไม่มี Error)
        if (empty($error)) {
            
            // Mock: Save booking to session
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
                'discount' => $discount,
                'returnLocation' => $returnLocation,
                'paymentProof' => $paymentProofPath,
                'status' => 'confirmed', // confirmed, pending, cancelled
                'createdAt' => date('Y-m-d H:i:s'),
            ];
            
            $_SESSION['mock_bookings'][] = $booking;
            
            // Redirect to success page or show message
            $_SESSION['booking_success'] = 'สำเร็จ! การจองของคุณได้รับการยืนยัน';
            header('Location: index.php?page=profile');
            exit;
        }
    }
}

// Get user's bookings from session
$userBookings = [];
if (isset($_SESSION['mock_bookings']) && isset($_SESSION['user_email'])) {
    foreach ($_SESSION['mock_bookings'] as $booking) {
        if (isset($booking['userEmail']) && $booking['userEmail'] === $_SESSION['user_email']) {
            $userBookings[] = $booking;
        }
    }
}

?>

<!-- (10) เริ่มส่วน HTML (View) -->
<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Show booking success message -->
        <?php if (!empty($_SESSION['booking_success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
                <i data-lucide="check-circle" class="h-5 w-5"></i>
                <span><?php echo $_SESSION['booking_success']; unset($_SESSION['booking_success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- My Bookings Section (show only if user is logged in) -->
        <?php if (isset($_SESSION['user_email']) && !empty($userBookings)): ?>
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">การจองของคุณ</h2>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach ($userBookings as $booking): ?>
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex flex-col md:flex-row gap-4 justify-between">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-900"><?php echo $booking['motorcycleName']; ?></h3>
                                    <p class="text-sm text-gray-600">รหัสการจอง: <?php echo $booking['id']; ?></p>
                                    <div class="mt-2 space-y-1 text-sm text-gray-700">
                                        <p>📅 <?php echo date('d/m/Y', strtotime($booking['startDate'])); ?> ถึง <?php echo date('d/m/Y', strtotime($booking['endDate'])); ?></p>
                                        <p>📍 สถานที่คืนรถ: <?php echo $booking['returnLocation']; ?></p>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end justify-between">
                                    <div class="text-right">
                                        <p class="text-2xl font-bold text-blue-600">฿<?php echo number_format($booking['totalPrice']); ?></p>
                                        <p class="text-sm text-gray-600"><?php echo $booking['totalDays']; ?> วัน</p>
                                        <?php if ($booking['discount'] > 0): ?>
                                            <p class="text-sm text-green-600">ส่วนลด ฿<?php echo $booking['discount']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $booking['status'] === 'confirmed' ? '✓ ยืนยันแล้ว' : 'รอการยืนยัน'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- (11) แสดงผลหากไม่พบรถ -->
        <?php if (!$motorcycle): ?>
            <div class="min-h-[60vh] flex items-center justify-center">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">ไม่พบข้อมูลรถ</h2>
                    <a
                        href="index.php?page=motorcycles"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg"
                    >
                        กลับไปเลือกรถ
                    </a>
                </div>
            </div>

        <!-- (11) แสดงผลหากพบรถ -->
        <?php else: ?>
            
            <!-- Back Button -->
            <!-- (แปลง onClick={() => navigate...} เป็น <a>) -->
            <a
                href="index.php?page=motorcycles"
                class="flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-6"
            >
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
                กลับไปเลือกรถ
            </a>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Motorcycle Details -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="relative h-64">
                        <img
                            src="<?php echo htmlspecialchars($motorcycle['image']); ?>"
                            alt="<?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                            class="w-full h-full object-cover"
                        />
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
                                <span 
                                    id="price-per-day" 
                                    data-price-per-day="<?php echo $motorcycle['pricePerDay']; ?>"
                                    class="text-2xl font-bold text-blue-600"
                                >
                                    ฿<?php echo $motorcycle['pricePerDay']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">จองรถจักรยานยนต์</h2>
                    
                    <!-- (13) แสดง Error (ถ้ามี) -->
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- (14) เพิ่ม enctype="multipart/form-data" สำหรับไฟล์อัพโหลด -->
                    <form 
                        method="POST" 
                        action="index.php?page=booking&id=<?php echo $motorcycle['id']; ?>" 
                        enctype="multipart/form-data" 
                        class="space-y-6"
                    >
                        <!-- User Info (จาก Session) -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <i data-lucide="user" class="h-5 w-5 text-gray-600"></i>
                                <span class="font-medium">ข้อมูลผู้จอง</span>
                            </div>
                            <p class="text-gray-700"><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
                            <!-- (ดึงข้อมูล phone และ lineId จาก session ถ้ามี) -->
                        </div>

                        <!-- Date Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar" class="inline h-4 w-4 mr-1"></i>
                                    วันที่รับรถ
                                </label>
                                <input
                                    type="date"
                                    id="start-date"
                                    name="start_date"
                                    min="<?php echo $today; ?>"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar" class="inline h-4 w-4 mr-1"></i>
                                    วันที่คืนรถ
                                </label>
                                <input
                                    type="date"
                                    id="end-date"
                                    name="end_date"
                                    min="<?php echo $today; ?>"
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <!-- Return Location -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="map-pin" class="inline h-4 w-4 mr-1"></i>
                                สถานที่คืนรถ
                            </label>
                            <select
                                name="return_location"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="ร้านเทมป์เทชัน">ร้านเทมป์เทชัน</option>
                                <option value="สนามบินหาดใหญ่">สนามบินหาดใหญ่</option>
                                <option value="โรงแรม (มีค่าบริการเพิ่มเติม)">โรงแรม (มีค่าบริการเพิ่มเติม)</option>
                            </select>
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
                                <div id="summary-discount-row" class="flex justify-between text-green-600 hidden">
                                    <span>ส่วนลดพิเศษ:</span>
                                    <span id="summary-discount">-฿0</span>
                                </div>
                                <div class="border-t pt-2 flex justify-between font-bold text-lg">
                                    <span>ราคารวม:</span>
                                    <span id="summary-total" class="text-blue-600">฿0</span>
                                </div>
                            </div>
                            <div id="summary-offer-text" class="mt-3 p-2 bg-green-100 rounded text-green-800 text-sm hidden">
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
                                <input
                                    type="file"
                                    name="payment_proof"
                                    accept="image/*"
                                    required
                                    id="payment-proof-input"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                />
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
                        <button
                            type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white py-3 px-4 rounded-lg font-medium transition-colors"
                        >
                            ยืนยันการจอง
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; // จบ if ($motorcycle) ?>
    </div>
</div>

<!-- (15) JavaScript สำหรับคำนวณราคาสด (จำลอง useEffect) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const pricePerDayEl = document.getElementById('price-per-day');
    
    // Elements to update
    const summaryContainer = document.getElementById('price-summary-container');
    const summaryDays = document.getElementById('summary-days');
    const summaryDiscountRow = document.getElementById('summary-discount-row');
    const summaryDiscount = document.getElementById('summary-discount');
    const summaryTotal = document.getElementById('summary-total');
    const summaryOfferText = document.getElementById('summary-offer-text');

    // File upload text
    const paymentProofInput = document.getElementById('payment-proof-input');
    const paymentProofFilename = document.getElementById('payment-proof-filename');

    if (paymentProofInput && paymentProofFilename) {
        paymentProofInput.addEventListener('change', function() {
            if (paymentProofInput.files && paymentProofInput.files.length > 0) {
                paymentProofFilename.textContent = '✓ อัพโหลดไฟล์: ' + paymentProofInput.files[0].name;
            } else {
                paymentProofFilename.textContent = '';
            }
        });
    }

    // ฟังก์ชันคำนวณ (เหมือน React)
    function calculatePrice() {
        if (!startDateInput || !endDateInput || !pricePerDayEl || !summaryContainer) {
            return;
        }

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
                let discountValue = 0;
                let offerText = '';

                // ตรรกะส่วนลด (เหมือน React)
                if (diffDays >= 3) {
                    discountValue = Math.floor(diffDays / 3) * 50;
                    price -= discountValue;
                    offerText = `🎉 ส่วนลด ${discountValue} บาท สำหรับการเช่า ${diffDays} วัน (รับส่วนลด 50 บาท ทุก ๆ 3 วันที่เช่า )`;
                }

                // Update UI
                summaryContainer.classList.remove('hidden');
                summaryDays.textContent = `${diffDays} วัน`;
                summaryTotal.textContent = `฿${price}`;

                if (discountValue > 0) {
                    summaryDiscountRow.classList.remove('hidden');
                    summaryDiscount.textContent = `-฿${discountValue}`;
                    summaryOfferText.textContent = offerText;
                    summaryOfferText.classList.remove('hidden');
                } else {
                    summaryDiscountRow.classList.add('hidden');
                    summaryOfferText.classList.add('hidden');
                }
            } else {
                summaryContainer.classList.add('hidden');
            }
        }
    }

    // Listen for changes
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            // ตั้งค่า min ของ endDate
            endDateInput.min = startDateInput.value;
            calculatePrice();
        });
        endDateInput.addEventListener('change', calculatePrice);
    }
});
</script>