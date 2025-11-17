<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// โหลดไฟล์ API - ใช้ระบบ path array
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

// โหลด payments.php
$paymentPaths = [
    __DIR__ . '/../api/payments.php',
    __DIR__ . '/../../api/payments.php',
    'api/payments.php'  
];

$paymentLoaded = false;
foreach ($paymentPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $paymentLoaded = true;
        break;
    }
}

if (!$paymentLoaded) {
    die('ไม่พบไฟล์ payments.php');
}

// ✅ โหลด motorcycles.php ด้วย
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

// ดึงข้อมูลการจองจาก parameter หรือใช้ล่าสุด
$reservationId = $_GET['reservation'] ?? null;
$booking = null;
$customerId = $_SESSION['user']['userId'] ?? $_SESSION['user_id'] ?? '';

if ($reservationId) {
    // ดึงข้อมูลการจองตาม reservationId
    $booking = BookingService::getBookingById($reservationId);
} else {
    // ถ้าไม่มี parameter ให้ใช้การจองล่าสุด (สำหรับ backward compatibility)
    $booking = BookingService::getLatestCustomerBooking($customerId);
}

// ✅ เพิ่มส่วนนี้: ดึงข้อมูลรถแยกต่างหากเพื่อให้ได้ imageUrl
if ($booking && isset($booking['motorcycleId'])) {
    $motorcycleData = MotorcycleService::getMotorcycleById($booking['motorcycleId']);
    if ($motorcycleData) {
        // รวมข้อมูลรถเข้าไปใน booking
        $booking['motorcycleImageUrl'] = $motorcycleData['imageUrl'] ?? '';
        $booking['motorcycleBrand'] = $motorcycleData['brand'] ?? '';
        $booking['motorcycleModel'] = $motorcycleData['model'] ?? '';
        $booking['motorcycleEngineCc'] = $motorcycleData['engineCc'] ?? '';
    }
}

// หากไม่มีข้อมูลการจอง ให้ redirect กลับ
if (!$booking) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'ไม่พบข้อมูลการจอง'
    ];
    header("Location: index.php?page=my-bookings");
    exit;
}

// คำนวณจำนวนวัน
$startDate = new DateTime($booking['startDate']);
$endDate = new DateTime($booking['endDate']);
$totalDays = $endDate->diff($startDate)->days;

// ตรวจสอบว่ามีการชำระเงินแล้วหรือยัง
$existingPayment = PaymentService::getPaymentByReservation($booking['reservationId']);
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="flex justify-center mb-4">
                <div class="bg-green-100 p-3 rounded-full">
                    <i data-lucide="check-circle" class="h-12 w-12 text-green-600"></i>
                </div>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <?php echo $existingPayment ? 'ชำระเงินสำเร็จ!' : 'จองสำเร็จ!'; ?>
            </h1>
            <p class="text-lg text-gray-600">
                <?php echo $existingPayment ? 'การชำระเงินของคุณสำเร็จแล้ว' : 'การจองรถจักรยานยนต์ของคุณสำเร็จแล้ว'; ?>
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Booking Details -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">รายละเอียดการจอง</h2>
                    
                    <div class="space-y-4">
                        <!-- Reservation Info -->
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-600">รหัสการจอง</p>
                                <p class="font-semibold text-lg"><?php echo $booking['reservationId']; ?></p>
                            </div>
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                <?php echo $existingPayment ? 'ชำระเงินแล้ว' : 'รอชำระเงิน'; ?>
                            </span>
                        </div>

                        <!-- Motorcycle Info -->
                        <div class="border-t pt-4">
                            <h3 class="font-semibold text-gray-900 mb-2">ข้อมูลรถ</h3>
                            <div class="flex items-center gap-4">
                                <!-- ✅ แก้ไขตรงนี้: ใช้ motorcycleImageUrl โดยตรง -->
                                <img src="<?php echo htmlspecialchars($booking['motorcycleImageUrl'] ?? '../img/default-bike.jpg'); ?>" 
                                     alt="Motorcycle" 
                                     class="w-20 h-20 object-cover rounded-lg"
                                     onerror="this.src='../img/default-bike.jpg'">
                                <div>
                                    <p class="font-semibold"><?php echo htmlspecialchars(($booking['motorcycleBrand'] ?? '') . ' ' . ($booking['motorcycleModel'] ?? $booking['motorcycleId'] ?? 'N/A')); ?></p>
                                    <p class="text-gray-600 text-sm"><?php echo $booking['motorcycleEngineCc'] ?? $booking['engineCc'] ?? 'N/A'; ?> cc</p>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Location -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">วันที่รับรถ</p>
                                <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['startDate'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">วันที่คืนรถ</p>
                                <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['endDate'])); ?></p>
                            </div>
                            <div class="md:col-span-2">
                                <p class="text-sm text-gray-600">สถานที่คืนรถ</p>
                                <p class="font-semibold"><?php echo htmlspecialchars($booking['returnLocation'] ?? 'ร้านเทมป์เทชัน'); ?></p>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="border-t pt-4">
                            <h3 class="font-semibold text-gray-900 mb-3">สรุปราคา</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>จำนวนวัน:</span>
                                    <span><?php echo $totalDays; ?> วัน</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>ราคาต่อวัน:</span>
                                    <span>฿<?php echo number_format($booking['pricePerDay'] ?? 0); ?></span>
                                </div>
                                <?php if (($booking['discountAmount'] ?? 0) > 0): ?>
                                <div class="flex justify-between text-green-600">
                                    <span>ส่วนลด:</span>
                                    <span>-฿<?php echo number_format($booking['discountAmount']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="border-t pt-2 flex justify-between font-bold text-lg">
                                    <span>ราคารวม:</span>
                                    <span class="text-blue-600">฿<?php echo number_format($booking['finalPrice'] ?? $booking['totalPrice'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Info (แสดงเมื่อชำระเงินแล้ว) -->
                        <?php if ($existingPayment): ?>
                        <div class="border-t pt-4">
                            <h3 class="font-semibold text-gray-900 mb-3">ข้อมูลการชำระเงิน</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span>เลขที่การชำระเงิน:</span>
                                    <span><?php echo $existingPayment['paymentId'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>วิธีการชำระเงิน:</span>
                                    <span><?php echo $existingPayment['paymentMethod'] ?? 'N/A'; ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>วันที่ชำระเงิน:</span>
                                    <span><?php echo date('d/m/Y H:i', strtotime($existingPayment['paidAt'] ?? $existingPayment['createdAt'])); ?></span>
                                </div>
                                <div class="flex justify-between font-bold">
                                    <span>จำนวนเงิน:</span>
                                    <span class="text-green-600">฿<?php echo number_format($existingPayment['amount'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action Panel -->
            <div class="space-y-4">
                <?php if (!$existingPayment): ?>
                <!-- Payment Action -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">ชำระเงิน</h3>
                    <p class="text-sm text-gray-600 mb-4">ชำระเงินภายใน 24 ชั่วโมง เพื่อยืนยันการจอง</p>
                    <a href="index.php?page=payment&reservation=<?php echo $booking['reservationId']; ?>" 
                       class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="credit-card" class="h-5 w-5"></i>
                        ชำระเงินทันที
                    </a>
                </div>
                <?php else: ?>
                <!-- Payment Status -->
                <div class="bg-green-50 rounded-lg shadow-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">สถานะการชำระเงิน</h3>
                    <div class="flex items-center gap-2 text-green-600 mb-2">
                        <i data-lucide="check-circle" class="h-5 w-5"></i>
                        <span class="font-semibold">ชำระเงินแล้ว</span>
                    </div>
                    <p class="text-sm text-gray-600">เราจะติดต่อกลับเพื่อยืนยันการจองภายใน 24 ชั่วโมง</p>
                </div>
                <?php endif; ?>

                <!-- Navigation Actions -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">การดำเนินการต่อไป</h3>
                    <div class="space-y-3">
                        <a href="index.php?page=my-bookings" 
                           class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="list" class="h-4 w-4"></i>
                            ดูการจองทั้งหมด
                        </a>
                        <a href="index.php?page=motorcycles" 
                           class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="bike" class="h-4 w-4"></i>
                            จองรถเพิ่ม
                        </a>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">ติดต่อสอบถาม</h4>
                    <p class="text-sm text-blue-700">โทร: 099-123-4567</p>
                    <p class="text-sm text-blue-700">Line: @temptation</p>
                    <p class="text-sm text-blue-700">Email: support@temptation.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // เปิดใช้งาน Lucide icons
    lucide.createIcons();
</script>