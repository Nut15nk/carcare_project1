<?php

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['user'])) {
        $_SESSION['redirect_url']  = $_SERVER['REQUEST_URI'];
        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'กรุณาเข้าสู่ระบบก่อนทำการชำระเงิน',
        ];
        header("Location: login.php");
        exit;
    }

    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../service/BookingService.php';
    require_once __DIR__ . '/../service/PaymentService.php';
    require_once __DIR__ . '/../service/MotorcycleService.php';

    $reservationId = $_GET['reservation'] ?? '';
    $booking       = null;
    $bankAccounts  = [];
    $error         = '';
    $success       = '';

    // ดึงข้อมูลการจอง
    if ($reservationId) {
        $rawBooking = BookingService::getBookingById($reservationId);

        if ($rawBooking) {
            $booking = BookingService::getBookingById($reservationId);

            // ดึงข้อมูลรถ
            if ($booking['motorcycleId']) {
                $motorcycleData = MotorcycleService::getMotorcycleById($booking['motorcycleId']);
                if ($motorcycleData) {
                    $booking['motorcycleBrand']    = $motorcycleData['brand'] ?? '';
                    $booking['motorcycleModel']    = $motorcycleData['model'] ?? '';
                    $booking['motorcycleEngineCc'] = $motorcycleData['engineCc'] ?? '';
                    $booking['motorcycleImageUrl'] = $motorcycleData['imageUrl'] ?? '';
                }
            }

            $bankAccounts = PaymentService::getBankAccounts();
        } else {
            $error = 'ไม่พบข้อมูลการจอง';
        }
    }

    // ตรวจสอบ payment ที่มีอยู่
    if ($booking && ! $error) {
        $existingPayment = PaymentService::getPaymentByReservation($reservationId);
        if ($existingPayment) {
            $error = 'การจองนี้ได้ชำระเงินแล้ว';
        }
    }

    // ประมวลผล form POST
    if ($_SERVER["REQUEST_METHOD"] === "POST" && ! $error) {
        $paymentMethod = $_POST['payment_method'] ?? '';
        $amount        = $_POST['amount'] ?? 0;

        if (empty($paymentMethod) || $amount <= 0) {
            $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } else {
            try {
                $paymentData = [
                    'reservationId' => $reservationId,
                    'customerId'    => $_SESSION['user']['userId'] ?? $_SESSION['user_id'],
                    'paymentMethod' => $paymentMethod,
                    'amount'        => $amount,
                ];

                $payment = PaymentService::createPayment($paymentData);

                if ($payment) {
                    // อัพโหลดสลิป
                    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/payments/';
                        if (! is_dir($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }

                        $fileName   = time() . '_' . basename($_FILES['payment_slip']['name']);
                        $targetPath = $uploadDir . $fileName;

                        if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $targetPath)) {
                            // บันทึก path ลง DB หากต้องการ
                        }
                    }

                    $_SESSION['flash_message'] = [
                        'type'    => 'success',
                        'message' => 'ชำระเงินสำเร็จ! เราจะตรวจสอบและติดต่อกลับภายใน 24 ชั่วโมง',
                    ];
                    header("Location: index.php?page=booking-confirmation&reservation=" . $reservationId);
                    exit;
                } else {
                    $error = 'ไม่สามารถบันทึกข้อมูลการชำระเงินได้';
                }
            } catch (Exception $e) {
                $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
                error_log("Payment Error: " . $e->getMessage());
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

        <?php if (! $error && $booking): ?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Payment Form -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">
                    เลือกวิธีการชำระเงิน
                </h2>

                <form method="POST" action="" enctype="multipart/form-data" class="space-y-8">

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-4">
                            <i data-lucide="credit-card" class="inline h-4 w-4 mr-1"></i>
                            วิธีการชำระเงิน
                        </label>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Bank Transfer -->
                            <label class="flex items-start gap-3 p-4 border border-gray-300 rounded-lg cursor-pointer hover:border-green-500 hover:bg-gray-50 transition">
                                <input type="radio" name="payment_method" value="BANK_TRANSFER" class="mt-1" required>
                                <div>
                                    <p class="font-medium text-gray-900">โอนผ่านธนาคาร</p>
                                    <p class="text-sm text-gray-600">โอนเงินผ่านบัญชีธนาคาร</p>
                                </div>
                            </label>

                            <!-- PromptPay -->
                            <label class="flex items-start gap-3 p-4 border border-gray-300 rounded-lg cursor-pointer hover:border-green-500 hover:bg-gray-50 transition">
                                <input type="radio" name="payment_method" value="PROMPTPAY" class="mt-1">
                                <div>
                                    <p class="font-medium text-gray-900">พร้อมเพย์</p>
                                    <p class="text-sm text-gray-600">ชำระผ่านพร้อมเพย์</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Bank Transfer Details -->
                    <div id="bank-transfer-details" class="hidden">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 space-y-3">
                            <h4 class="font-semibold text-gray-900">บัญชีธนาคาร</h4>

                            <div class="text-sm text-gray-700 space-y-1">
                                <p><span class="font-medium">ธนาคาร:</span> กรุงไทย</p>
                                <p><span class="font-medium">ชื่อบัญชี:</span> บริษัท เทมป์เทชัน จำกัด</p>
                                <p><span class="font-medium">สาขา:</span> เมืองสงขลา</p>
                            </div>
                        </div>
                    </div>

                    <!-- PromptPay Details -->
                    <div id="promptpay-details" class="hidden">
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 space-y-4">
                            <h4 class="font-semibold text-gray-900">พร้อมเพย์</h4>

                            <div class="text-sm text-gray-700 space-y-1">
                                <p><span class="font-medium">ชื่อ:</span> บริษัท เทมป์เทชัน จำกัด</p>
                                <p><span class="font-medium">เบอร์โทร:</span> 096-961-5248</p>
                                <p class="text-gray-600">สแกน QR Code ด้านล่างเพื่อชำระเงิน</p>
                            </div>

                            <div class="flex justify-center">
                                <div class="bg-white border rounded-lg p-4">
                                    <div class="w-32 h-32 bg-gray-200 flex items-center justify-center text-gray-500 text-xs text-center">
                                        QR Code<br>พร้อมเพย์
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

                        <input
                            type="file"
                            name="payment_slip"
                            accept="image/*,.pdf"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                        >

                        <p class="text-xs text-gray-500 mt-1">
                            รองรับไฟล์ภาพและ PDF ขนาดไม่เกิน 5MB
                        </p>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i data-lucide="dollar-sign" class="inline h-4 w-4 mr-1"></i>
                            จำนวนเงิน
                        </label>

                        <input
                            type="number"
                            name="amount"
                            value="<?php echo $booking['finalPrice'] ?? $booking['totalPrice'] ?? 0; ?>"
                            step="0.01"
                            min="0"
                            required
                            readonly
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none"
                        >
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium transition flex items-center justify-center gap-2"
                    >
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