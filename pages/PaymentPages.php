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

$error = '';
$success = '';

// ดึงข้อมูลการจอง
$reservationId = $_GET['reservation'] ?? '';
$booking = null;
$bankAccounts = [];

if ($reservationId) {
    $booking = BookingService::getBookingById($reservationId);
    
    // ✅ เพิ่มส่วนนี้: ดึงข้อมูลรถแยกต่างหาก
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
    
    $bankAccounts = PaymentService::getBankAccounts();
}

// ตรวจสอบว่ามีการจองและยังไม่ได้ชำระเงิน
if (!$booking) {
    $error = 'ไม่พบข้อมูลการจอง';
} else {
    $existingPayment = PaymentService::getPaymentByReservation($reservationId);
    if ($existingPayment) {
        $error = 'การจองนี้ได้ชำระเงินแล้ว';
    }
}

// ประมวลผลฟอร์มชำระเงิน
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$error) {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    
    if (empty($paymentMethod) || $amount <= 0) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        try {
            // ✅ เพิ่ม debugging
            error_log("Payment Form Data:");
            error_log("Reservation ID: " . $reservationId);
            error_log("Payment Method: " . $paymentMethod);
            error_log("Amount: " . $amount);
            
            // สร้างการชำระเงิน
            $paymentData = [
                'reservationId' => $reservationId,
                'paymentMethod' => $paymentMethod,
                'amount' => floatval($amount)
            ];
            
            $payment = PaymentService::createPayment($paymentData);
            
            if ($payment) {
                $success = 'บันทึกข้อมูลการชำระเงินสำเร็จ';
                
                // อัพโหลดสลิปถ้ามี
                if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/payments/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileName = time() . '_' . basename($_FILES['payment_slip']['name']);
                    $targetPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $targetPath)) {
                        // อัพโหลดสลิปสำเร็จ
                    }
                }
                
                // Redirect ไปหน้ายืนยัน
                $_SESSION['flash_message'] = [
                    'type' => 'success',
                    'message' => 'ชำระเงินสำเร็จ! เราจะตรวจสอบและติดต่อกลับภายใน 24 ชั่วโมง'
                ];
                header("Location: index.php?page=booking-confirmation&reservation=" . $reservationId);
                exit;
            } else {
                $error = 'ไม่สามารถบันทึกข้อมูลการชำระเงินได้ (ตรวจสอบการเชื่อมต่อ API)';
            }
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            error_log("Payment Processing Error: " . $e->getMessage());
        }
    }
}
?>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">ชำระเงิน</h1>
            <p class="text-lg text-gray-600">ชำระเงินเพื่อยืนยันการจอง</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $error; ?>
                <?php if (strpos($error, 'ไม่พบข้อมูลการจอง') !== false): ?>
                    <a href="index.php?page=my-bookings" class="text-red-800 underline ml-2">ดูการจองทั้งหมด</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (!$error && $booking): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Payment Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">เลือกวิธีการชำระเงิน</h2>

                    <form method="POST" action="" enctype="multipart/form-data" class="space-y-6">
                        
                        <!-- Payment Method -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                <i data-lucide="credit-card" class="inline h-4 w-4 mr-1"></i>
                                วิธีการชำระเงิน
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="BANK_TRANSFER" class="mr-3" required>
                                    <div>
                                        <span class="font-medium">โอนผ่านธนาคาร</span>
                                        <p class="text-sm text-gray-600">โอนเงินผ่านบัญชีธนาคาร</p>
                                    </div>
                                </label>
                                
                                <label class="flex items-center p-4 border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="PROMPTPAY" class="mr-3">
                                    <div>
                                        <span class="font-medium">พร้อมเพย์</span>
                                        <p class="text-sm text-gray-600">ชำระผ่านพร้อมเพย์</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Bank Transfer Details -->
                        <div id="bank-transfer-details" class="hidden">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-900 mb-3">บัญชีธนาคาร</h4>
                                <div class="space-y-3">
                                    <?php foreach ($bankAccounts as $bank): ?>
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <p class="font-semibold"><?php echo $bank['bankName']; ?></p>
                                        <p class="text-sm">บัญชี: <?php echo $bank['accountNumber']; ?></p>
                                        <p class="text-sm">ชื่อ: <?php echo $bank['accountName']; ?></p>
                                        <p class="text-sm">สาขา: <?php echo $bank['branch']; ?></p>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- PromptPay Details -->
                        <div id="promptpay-details" class="hidden">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="font-semibold text-gray-900 mb-3">พร้อมเพย์</h4>
                                <div class="space-y-3">
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <p class="font-semibold">พร้อมเพย์ (PromptPay)</p>
                                        <p class="text-sm">เบอร์โทร: 096-961-5248</p>
                                        <p class="text-sm">ชื่อ: บริษัท เทมป์เทชัน จำกัด</p>
                                        <p class="text-sm">สแกน QR Code ด้านล่างเพื่อชำระเงิน</p>
                                        <div class="mt-2 text-center">
                                            <div class="bg-white p-4 inline-block border rounded-lg">
                                                <div class="w-32 h-32 bg-gray-200 flex items-center justify-center text-gray-500 text-xs">
                                                    QR Code<br>พร้อมเพย์
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Slip -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="upload" class="inline h-4 w-4 mr-1"></i>
                                อัพโหลดสลิปการโอน (ถ้ามี)
                            </label>
                            <input type="file" name="payment_slip" 
                                   accept="image/*,.pdf"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">รองรับไฟล์ภาพและ PDF ขนาดไม่เกิน 5MB</p>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i data-lucide="dollar-sign" class="inline h-4 w-4 mr-1"></i>
                                จำนวนเงิน
                            </label>
                            <input type="number" name="amount" value="<?php echo $booking['finalPrice'] ?? $booking['totalPrice'] ?? 0; ?>" 
                                   step="0.01" min="0" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="check-circle" class="h-5 w-5"></i>
                            ยืนยันการชำระเงิน
                        </button>
                    </form>
                </div>
            </div>

            <!-- Booking Summary -->
            <div class="space-y-6">
                <!-- Booking Details -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">สรุปการจอง</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-600">รหัสการจอง</p>
                            <p class="font-semibold"><?php echo $booking['reservationId']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">รถจักรยานยนต์</p>
                            <p class="font-semibold">
                                <?php 
                                $brand = $booking['motorcycleBrand'] ?? '';
                                $model = $booking['motorcycleModel'] ?? $booking['motorcycleId'] ?? 'N/A';
                                echo htmlspecialchars($brand . ' ' . $model);
                                ?>
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <p class="text-sm text-gray-600">วันที่รับ</p>
                                <p class="font-semibold text-sm"><?php echo date('d/m/Y', strtotime($booking['startDate'])); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">วันที่คืน</p>
                                <p class="font-semibold text-sm"><?php echo date('d/m/Y', strtotime($booking['endDate'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t mt-4 pt-4">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">ยอดชำระ</span>
                            <span class="text-2xl font-bold text-green-600">
                                ฿<?php echo number_format($booking['finalPrice'] ?? $booking['totalPrice'] ?? 0); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Help Info -->
                <div class="bg-blue-50 rounded-lg p-4">
                    <h4 class="font-semibold text-blue-900 mb-2">คำแนะนำ</h4>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• ชำระเงินภายใน 24 ชั่วโมง</li>
                        <li>• เก็บสลิปการโอนไว้เป็นหลักฐาน</li>
                        <li>• เราจะติดต่อกลับภายใน 24 ชั่วโมง</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Show payment details based on selected method
    const bankTransferRadio = document.querySelector('input[value="BANK_TRANSFER"]');
    const promptpayRadio = document.querySelector('input[value="PROMPTPAY"]');
    const bankDetails = document.getElementById('bank-transfer-details');
    const promptpayDetails = document.getElementById('promptpay-details');
    
    function updatePaymentDetails() {
        if (bankTransferRadio.checked) {
            bankDetails.classList.remove('hidden');
            promptpayDetails.classList.add('hidden');
        } else if (promptpayRadio.checked) {
            bankDetails.classList.add('hidden');
            promptpayDetails.classList.remove('hidden');
        } else {
            bankDetails.classList.add('hidden');
            promptpayDetails.classList.add('hidden');
        }
    }
    
    if (bankTransferRadio && promptpayRadio && bankDetails && promptpayDetails) {
        bankTransferRadio.addEventListener('change', updatePaymentDetails);
        promptpayRadio.addEventListener('change', updatePaymentDetails);
        
        // Trigger on page load if already selected
        updatePaymentDetails();
    }

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>