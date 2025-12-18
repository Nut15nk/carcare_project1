<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../service/BookingService.php';
    require_once __DIR__ . '/../service/MotorcycleService.php';

    // เช็ค user login
    if (! isset($_SESSION['user'])) {
        $_SESSION['redirect_url']  = $_SERVER['REQUEST_URI'];
        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง',
        ];
        header("Location: login.php");
        exit;
    }

    $error = '';
    $today = date('Y-m-d');

    $motorcycle_id = $_GET['id'] ?? null;
    $motorcycle    = $motorcycle_id ? MotorcycleService::getMotorcycleById($motorcycle_id) : null;
    $customerId    = $_SESSION['user']['userId'] ?? $_SESSION['user_id'] ?? null;

    if (! $motorcycle_id || ! $motorcycle) {
        $error = 'ไม่พบรหัสรถหรือข้อมูลรถจักรยานยนต์';
    }

    // POST Booking
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $startDateRaw   = $_POST['start_date'] ?? '';
        $endDateRaw     = $_POST['end_date'] ?? '';
        $pickupLocation = $_POST['pickup_location'] ?? '';
        $returnLocation = $_POST['return_location'] ?? 'ร้านเทมป์เทชัน';
        $discountCode   = strtoupper(trim($_POST['discount_code'] ?? ''));

        // Log raw input
        error_log("Booking POST: start_date={$startDateRaw}, end_date={$endDateRaw}");

        if (empty($startDateRaw) || empty($endDateRaw) || empty($pickupLocation) || ! $motorcycle || ! $customerId) {
            $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        } else {
            try {
                // แปลงวันที่เป็น Y-m-d
                $startObj  = new DateTime($startDateRaw);
                $endObj    = new DateTime($endDateRaw);
                $start     = $startObj->format('Y-m-d');
                $end       = $endObj->format('Y-m-d');
                $diff      = $endObj->diff($startObj);
                $totalDays = $diff->days;

                // log after conversion
                error_log("Booking Dates: start={$start}, end={$end}, totalDays={$totalDays}");

                if ($totalDays <= 0) {
                    $error = 'วันที่คืนรถต้องอยู่หลังวันที่รับรถ';
                } else {
                    $pricePerDay    = floatval($motorcycle['price_per_day']);
                    $totalPrice     = $pricePerDay * $totalDays;
                    $discountAmount = ($discountCode === 'WELCOME50') ? 50 : 0;
                    $finalPrice     = max($totalPrice - $discountAmount, 0);
                    $depositAmount  = round($totalPrice * 0.2, 2);

                    $bookingData = [
                        'customerId'     => $customerId,
                        'motorcycleId'   => $motorcycle['motorcycle_id'],
                        'startDate'      => $start,
                        'endDate'        => $end,
                        'totalDays'      => $totalDays,
                        'totalPrice'     => $totalPrice,
                        'depositAmount'  => $depositAmount,
                        'discountAmount' => $discountAmount,
                        'finalPrice'     => $finalPrice,
                        'pickupLocation' => $pickupLocation,
                        'returnLocation' => $returnLocation,
                    ];

                    if (! empty($discountCode)) {
                        $bookingData['discountCode'] = $discountCode;
                    }

                    // Log prepared booking
                    error_log("Booking Data Prepared: " . json_encode($bookingData));

                    $booking = BookingService::createBooking($bookingData);

                    if ($booking) {
                        $_SESSION['flash_message'] = [
                            'type'    => 'success',
                            'message' => 'จองสำเร็จ! เราจะติดต่อกลับภายใน 24 ชั่วโมง',
                        ];
                        header("Location: index.php?page=profile");
                        exit;
                    } else {
                        $error = 'ไม่สามารถทำการจองได้ กรุณาลองใหม่';
                    }
                }
            } catch (Exception $e) {
                error_log("Booking Exception: " . $e->getMessage());
                $error = 'เกิดข้อผิดพลาดในการจอง: ' . $e->getMessage();
            }
        }
    }
?>

<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (! $motorcycle): ?>
            <div class="min-h-[60vh] flex items-center justify-center">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">ไม่พบข้อมูลรถ</h2>
                    <a href="index.php?page=motorcycles" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">กลับไปเลือกรถ</a>
                </div>
            </div>
        <?php else: ?>
            <a href="index.php?page=motorcycles" class="flex items-center gap-2 text-blue-600 hover:text-blue-700 mb-6">
                <i data-lucide="arrow-left" class="h-5 w-5"></i> กลับไปเลือกรถ
            </a>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="relative h-64">
                        <img src="<?php echo htmlspecialchars($motorcycle['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>"
                             class="w-full h-full object-cover"
                             onerror="this.src='../img/default-bike.jpg';"/>
                        <div class="absolute top-4 right-4">
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                <?php echo($motorcycle['is_available']) ? 'พร้อมใช้งาน' : 'ไม่ว่าง'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <h1 class="text-2xl font-bold text-gray-900 mb-4">
                            <?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>
                        </h1>
                        <div class="flex items-center gap-6 text-gray-600 mb-6">
                            <div class="flex items-center gap-2">
                                <i data-lucide="fuel" class="h-5 w-5"></i>
                                <span><?php echo $motorcycle['engine_cc']; ?> cc</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="calendar" class="h-5 w-5"></i>
                                <span><?php echo $motorcycle['year']; ?></span>
                            </div>
                        </div>
                        <div class="mb-6">
                            <h3 class="font-semibold text-gray-900 mb-3">รายละเอียด</h3>
                            <p class="text-gray-700"><?php echo htmlspecialchars($motorcycle['description']); ?></p>
                        </div>
                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">ราคาต่อวัน</span>
                                <span id="price-per-day" data-price-per-day="<?php echo $motorcycle['price_per_day']; ?>" class="text-2xl font-bold text-blue-600">
                                    ฿<?php echo $motorcycle['price_per_day']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">จองรถจักรยานยนต์</h2>
                    <?php if (! empty($error)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="index.php?page=booking&id=<?php echo $motorcycle['motorcycle_id']; ?>" class="space-y-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-700"><?php echo htmlspecialchars($_SESSION['user']['firstName'] ?? 'ผู้ใช้'); ?></p>
                            <p class="text-gray-600"><?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?></p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่รับรถ</label>
                                <input type="date" name="start_date" min="<?php echo $today; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">วันที่คืนรถ</label>
                                <input type="date" name="end_date" min="<?php echo $today; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"/>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">สถานที่รับรถ</label>
                            <select name="pickup_location" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="ร้านเทมป์เทชัน">ร้านเทมป์เทชัน</option>
                                <option value="สนามบินหาดใหญ่">สนามบินหาดใหญ่</option>
                                <option value="สถานีรถไฟหาดใหญ่">สถานีรถไฟหาดใหญ่</option>
                                <option value="สถานีขนส่งหาดใหญ่">สถานีขนส่งหาดใหญ่</option>
                                <option value="โรงแรมในเมืองหาดใหญ่">โรงแรมในเมืองหาดใหญ่</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">สถานที่คืนรถ</label>
                            <select name="return_location" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="ร้านเทมป์เทชัน">ร้านเทมป์เทชัน</option>
                                <option value="สนามบินหาดใหญ่">สนามบินหาดใหญ่</option>
                                <option value="โรงแรม (มีค่าบริการเพิ่มเติม)">โรงแรม (มีค่าบริการเพิ่มเติม)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">โค้ดส่วนลด (ถ้ามี)</label>
                            <input type="text" name="discount_code" placeholder="กรอกโค้ดส่วนลด" class="w-full px-3 py-2 border border-gray-300 rounded-lg"/>
                        </div>
                        <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded">
                            <ul class="list-disc list-inside space-y-1">
                                <li>สามารถยกเลิกการจองได้ก่อน 1 วัน</li>
                                <li>ต้องมีใบขับขี่ที่ถูกต้อง</li>
                                <li>ต้องมีบัตรประชาชนหรือหนังสือเดินทาง</li>
                                <li>ค่าเสียหายจะหักจากเงินมัดจำ</li>
                            </ul>
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-4 rounded-lg font-medium">ยืนยันการจอง</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
