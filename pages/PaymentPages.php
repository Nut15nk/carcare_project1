<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../service/BookingService.php';
require_once __DIR__ . '/../service/PaymentService.php';
require_once __DIR__ . '/../service/MotorcycleService.php';

$reservationId = $_GET['reservation'] ?? '';
$booking = null;
$bankAccounts = [];
$error = '';
$success = '';

// ดึงข้อมูลการจอง
if ($reservationId) {
    $rawBooking = BookingService::getBookingById($reservationId);

    if ($rawBooking) {
        $booking = [
            'reservationId'   => $rawBooking['reservation_id'] ?? '',
            'customerId'      => $rawBooking['customer_id'] ?? '',
            'motorcycleId'    => $rawBooking['motorcycle_id'] ?? '',
            'startDate'       => $rawBooking['start_date'] ?? '1970-01-01',
            'endDate'         => $rawBooking['end_date'] ?? '1970-01-01',
            'totalPrice'      => $rawBooking['total_price'] ?? 0,
            'finalPrice'      => $rawBooking['final_price'] ?? $rawBooking['total_price'] ?? 0,
            'pickupLocation'  => $rawBooking['pickup_location'] ?? '',
            'returnLocation'  => $rawBooking['return_location'] ?? '',
        ];

        // ดึงข้อมูลรถ
        if ($booking['motorcycleId']) {
            $motorcycleData = MotorcycleService::getMotorcycleById($booking['motorcycleId']);
            if ($motorcycleData) {
                $booking['motorcycleBrand'] = $motorcycleData['brand'] ?? '';
                $booking['motorcycleModel'] = $motorcycleData['model'] ?? '';
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
if ($booking && !$error) {
    $existingPayment = PaymentService::getPaymentByReservation($reservationId);
    if ($existingPayment) {
        $error = 'การจองนี้ได้ชำระเงินแล้ว';
    }
}

// ประมวลผล form POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$error) {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $amount        = floatval($_POST['amount'] ?? 0);

    if (empty($paymentMethod) || $amount <= 0) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        try {
            $paymentData = [
                'reservationId' => $reservationId,
                'paymentMethod' => $paymentMethod,
                'amount'        => $amount
            ];

            $payment = PaymentService::createPayment($paymentData);

            if ($payment) {
                // อัพโหลดสลิป
                if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/payments/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileName   = time() . '_' . basename($_FILES['payment_slip']['name']);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['payment_slip']['tmp_name'], $targetPath)) {
                        // บันทึก path ลง DB หากต้องการ
                    }
                }

                $_SESSION['flash_message'] = [
                    'type'    => 'success',
                    'message' => 'ชำระเงินสำเร็จ! เราจะตรวจสอบและติดต่อกลับภายใน 24 ชั่วโมง'
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

<script>
document.addEventListener("DOMContentLoaded", function() {
    const bankRadio   = document.querySelector('input[value="BANK_TRANSFER"]');
    const promptRadio = document.querySelector('input[value="PROMPTPAY"]');
    const bankDetails   = document.getElementById('bank-transfer-details');
    const promptDetails = document.getElementById('promptpay-details');

    function updateDetails() {
        if (bankRadio.checked) {
            bankDetails.classList.remove('hidden');
            promptDetails.classList.add('hidden');
        } else if (promptRadio.checked) {
            bankDetails.classList.add('hidden');
            promptDetails.classList.remove('hidden');
        } else {
            bankDetails.classList.add('hidden');
            promptDetails.classList.add('hidden');
        }
    }

    if (bankRadio && promptRadio && bankDetails && promptDetails) {
        bankRadio.addEventListener('change', updateDetails);
        promptRadio.addEventListener('change', updateDetails);
        updateDetails();
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
