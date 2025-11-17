<?php
// pages/ProfilePages.php
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/bookings.php';
require_once __DIR__ . '/../api/auth.php';

// เริ่ม session ถ้ายังไม่เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบการล็อกอิน
if (!AuthService::isLoggedIn()) {
    error_log("User not logged in, redirecting to login");
    header("Location: index.php?page=login");
    exit;
}

// โหลด payments.php
$paymentPaths = [
    __DIR__ . '/../api/payments.php',
    __DIR__ . '/../../api/payments.php',
    'api/payments.php',
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

// ดึงข้อมูลการจองจาก API
$userBookings = [];
$error_message = '';
$userDetails = null;
$paymentMap = [];

try {
    // ใช้ AuthService เพื่อดึง user ID
    $userId = AuthService::getUserId();
    
    error_log("Profile Page - User ID: " . ($userId ?? 'NOT FOUND'));
    
    if (empty($userId)) {
        $error_message = "ไม่พบ User ID ใน session";
        error_log("Error: " . $error_message);
    } else {
        // ดึงข้อมูลผู้ใช้จาก API
        $userDetails = BookingService::getUserById($userId);
        error_log("User Details from API: " . print_r($userDetails, true));
        
        // ดึงข้อมูลการจอง
        $userBookings = BookingService::getCustomerBookings($userId);
        error_log("Bookings retrieved: " . count($userBookings));
        
        // ✅ ดึงข้อมูล payment สำหรับแต่ละ booking
        foreach ($userBookings as $booking) {
            $reservationId = $booking['reservationId'];
            $payment = PaymentService::getPaymentByReservation($reservationId);
            
            // ✅ เพิ่ม debugging
            error_log("Checking payment for reservation: " . $reservationId);
            error_log("Payment data: " . print_r($payment, true));
            
            if ($payment) {
                $paymentMap[$reservationId] = $payment;
            }
        }
    }
} catch (Exception $e) {
    $error_message = "ไม่สามารถโหลดข้อมูลการจอง: " . $e->getMessage();
    error_log("Profile Page Exception: " . $e->getMessage());
}

// ใช้ข้อมูลจาก API หรือจาก session
if ($userDetails) {
    $userFirstName = $userDetails['firstName'] ?? '';
    $userLastName = $userDetails['lastName'] ?? '';
    $userEmail = $userDetails['email'] ?? '';
    $userRole = $userDetails['role'] ?? 'CUSTOMER';
    $userPhone = $userDetails['phone'] ?? '';
    $userAddress = $userDetails['address'] ?? '';
} else {
    // Fallback ใช้ข้อมูลจาก session
    $userData = AuthService::getUserData();
    $userFirstName = $userData['firstName'] ?? '';
    $userLastName = $userData['lastName'] ?? '';
    $userEmail = $userData['email'] ?? '';
    $userRole = $userData['role'] ?? 'CUSTOMER';
    $userPhone = $userData['phone'] ?? '';
    $userAddress = '';
}

$fullName = trim($userFirstName . ' ' . $userLastName);
if (empty($fullName)) {
    $fullName = $userEmail;
}

// เรียงลำดับการจองใหม่ล่าสุดขึ้นก่อน
usort($userBookings, function($a, $b) {
    $timeA = strtotime($a['createdAt'] ?? '');
    $timeB = strtotime($b['createdAt'] ?? '');
    return $timeB - $timeA;
});

$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']);
}
?>

<div class="max-w-3xl mx-auto py-8 px-4">
    <?php if ($flash_message): ?>
        <div class="bg-green-100 border border-green-300 text-green-800 p-4 rounded-lg mb-6 shadow">
            <?php echo htmlspecialchars($flash_message['message'] ?? $flash_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 border border-red-300 text-red-800 p-4 rounded-lg mb-6 shadow">
            <strong>ข้อผิดพลาด:</strong> <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <h1 class="text-3xl font-bold mb-6 text-gray-900">โปรไฟล์ของฉัน</h1>
    
    <!-- ข้อมูลผู้ใช้ -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <h2 class="text-xl font-semibold mb-4 text-blue-700">ข้อมูลผู้ใช้</h2>
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 p-3 rounded-full">
                    <i data-lucide="user" class="h-6 w-6 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">ชื่อ-นามสกุล</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($fullName); ?></p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="bg-green-100 p-3 rounded-full">
                    <i data-lucide="mail" class="h-6 w-6 text-green-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">อีเมล</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($userEmail); ?></p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="bg-purple-100 p-3 rounded-full">
                    <i data-lucide="shield" class="h-6 w-6 text-purple-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">ระดับ</p>
                    <p class="font-semibold">
                        <?php 
                        $roleText = [
                            'CUSTOMER' => 'ลูกค้า',
                            'EMPLOYEE' => 'พนักงาน', 
                            'ADMIN' => 'ผู้ดูแลระบบ'
                        ];
                        echo htmlspecialchars($roleText[$userRole] ?? $userRole);
                        ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($userPhone)): ?>
            <div class="flex items-center gap-3">
                <div class="bg-orange-100 p-3 rounded-full">
                    <i data-lucide="phone" class="h-6 w-6 text-orange-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">โทรศัพท์</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($userPhone); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($userAddress)): ?>
            <div class="flex items-center gap-3">
                <div class="bg-indigo-100 p-3 rounded-full">
                    <i data-lucide="map-pin" class="h-6 w-6 text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-600">ที่อยู่</p>
                    <p class="font-semibold"><?php echo htmlspecialchars($userAddress); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- ปุ่มจัดการโปรไฟล์ -->
        <div class="mt-6 pt-4 border-t">
            <a href="index.php?page=my-bookings" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center gap-2">
                <i data-lucide="calendar" class="h-4 w-4"></i>
                ดูการจองทั้งหมด
            </a>
        </div>
    </div>

    <!-- การจองของฉัน -->
    <div class="bg-white p-6 rounded-lg shadow">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900">การจองของฉัน</h2>
            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                <?php echo count($userBookings); ?> การจอง
            </span>
        </div>
        
        <?php if (empty($userBookings)): ?>
            <div class="text-center py-8">
                <div class="flex justify-center mb-4">
                    <i data-lucide="calendar" class="h-16 w-16 text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">ยังไม่มีการจอง</h3>
                <p class="text-gray-600 mb-4">เริ่มต้นการจองรถจักรยานยนต์ครั้งแรกของคุณ</p>
                <a href="index.php?page=motorcycles" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors inline-flex items-center gap-2">
                    <i data-lucide="bike" class="h-5 w-5"></i>
                    จองรถเลย
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($userBookings as $booking): 
                    try {
                        $payment = $paymentMap[$booking['reservationId']] ?? null;
                        $startDate = new DateTime($booking['startDate'] ?? '');
                        $endDate = new DateTime($booking['endDate'] ?? '');
                        $totalDays = $endDate->diff($startDate)->days;
                        $status = $booking['status'] ?? 'PENDING';

                        // ✅ กำหนดสถานะตาม payment status
                        if ($payment) {
                            $statusText = 'ชำระเงินแล้ว';
                            $statusColor = 'bg-green-100 text-green-800';
                            $statusIcon = 'check-circle';
                        } else {
                            $statusText = 'รอชำระเงิน';
                            $statusColor = 'bg-yellow-100 text-yellow-800';
                            $statusIcon = 'clock';
                        }
                ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 mb-3">
                                <div>
                                    <h3 class="font-semibold text-lg text-gray-900">
                                        รถจักรยานยนต์ #<?php echo htmlspecialchars($booking['motorcycleId'] ?? 'N/A'); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600">รหัสการจอง: <?php echo $booking['reservationId'] ?? 'N/A'; ?></p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                                    <i data-lucide="<?php echo $statusIcon; ?>" class="h-4 w-4 mr-1"></i>
                                    <?php echo $statusText; ?>
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-600">📅 วันที่รับ</p>
                                    <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['startDate'] ?? '')); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">📅 วันที่คืน</p>
                                    <p class="font-semibold"><?php echo date('d/m/Y', strtotime($booking['endDate'] ?? '')); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-600">⏱️ จำนวนวัน</p>
                                    <p class="font-semibold"><?php echo $totalDays; ?> วัน</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">💰 ราคารวม</p>
                                    <p class="font-semibold text-blue-600">฿<?php echo number_format($booking['finalPrice'] ?? $booking['totalPrice'] ?? 0); ?></p>
                                </div>
                            </div>

                            <?php if (!empty($booking['returnLocation'])): ?>
                            <div class="mt-3">
                                <p class="text-sm text-gray-600">📍 สถานที่คืนรถ</p>
                                <p class="font-semibold text-sm"><?php echo htmlspecialchars($booking['returnLocation']); ?></p>
                            </div>
                            <?php endif; ?>

                            <!-- ✅ แสดงข้อมูลการชำระเงินถ้ามี -->
                            <?php if ($payment): ?>
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 text-sm text-gray-600">
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="calendar" class="h-4 w-4"></i>
                                        <span>ชำระเงินเมื่อ: <?php echo date('d/m/Y H:i', strtotime($payment['paidAt'] ?? $payment['createdAt'])); ?></span>
                                    </div>
                                    <span class="hidden sm:inline">•</span>
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="credit-card" class="h-4 w-4"></i>
                                        <span>วิธีการ: <?php echo $payment['paymentMethod'] ?? 'N/A'; ?></span>
                                    </div>
                                    <span class="hidden sm:inline">•</span>
                                    <div class="flex items-center gap-1">
                                        <i data-lucide="dollar-sign" class="h-4 w-4"></i>
                                        <span>จำนวน: ฿<?php echo number_format($payment['amount'] ?? 0); ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- ✅ Actions -->
                        <div class="flex flex-col sm:flex-row gap-2">
                            <?php if (!$payment): ?>
                            <a href="index.php?page=payment&reservation=<?php echo $booking['reservationId']; ?>"
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="credit-card" class="h-4 w-4"></i>
                                ชำระเงิน
                            </a>
                            <?php else: ?>
                            <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                                <i data-lucide="check-circle" class="h-4 w-4"></i>
                                ชำระแล้ว
                            </span>
                            <?php endif; ?>

                            <a href="index.php?page=booking-confirmation&reservation=<?php echo $booking['reservationId']; ?>"
                               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                                ดูรายละเอียด
                            </a>
                        </div>
                    </div>
                </div>
                <?php 
                    } catch (Exception $e) {
                        error_log("Error processing booking: " . $e->getMessage());
                        continue;
                    }
                endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // เปิดใช้งาน Lucide icons
    lucide.createIcons();
</script>