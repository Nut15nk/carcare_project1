<?php
// pages/ProfilePage.php
// ไม่ต้อง session_start() อีก เพราะ index.php เรียกไปแล้ว

// (1) Auth Guard: ตรวจสอบว่ามี session 'user_email' หรือไม่
if (!isset($_SESSION['user_email'])) {
    header("Location: index.php?page=login");
    exit; // จบการทำงานทันที
}

// (2) ตรวจสอบ Flash Message (จากหน้า Booking หรือ Login)
$flash_message = null;
if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']); // ลบ message ออกจาก session
}

// (3) คัดลอก "ฐานข้อมูลจำลอง" (Mock Database) ของรถ
// (เพื่อให้เราสามารถ find() ชื่อและรุ่นรถได้)
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

// (4) ดึงข้อมูลการจอง (จำลอง getUserBookings(userId))
// เราจะกรองเฉพาะการจองที่เป็นของ user ที่ล็อกอินอยู่
// (แทนการ hardcode userId = "1")
$currentUserId = $_SESSION['user_email']; 
$allBookings = $_SESSION['bookings'] ?? [];
$userBookings = array_filter($allBookings, function($booking) use ($currentUserId) {
    return $booking['userId'] === $currentUserId;
});

// เรียงลำดับการจองใหม่ล่าสุดขึ้นก่อน
usort($userBookings, function($a, $b) {
    return strtotime($b['createdAt']) - strtotime($a['createdAt']);
});

?>

<!-- (5) เริ่มส่วน HTML (View) -->
<div class="max-w-3xl mx-auto py-8 px-4">

    <!-- (6) ส่วนแสดง Flash Message (จาก BookingPage) -->
    <?php if ($flash_message): ?>
        <?php
        $bgColor = ($flash_message['type'] === 'success') ? 'bg-green-100' : 'bg-red-100';
        $textColor = ($flash_message['type'] === 'success') ? 'text-green-800' : 'text-red-800';
        $borderColor = ($flash_message['type'] === 'success') ? 'border-green-300' : 'border-red-300';
        ?>
        <div class="<?php echo "$bgColor $textColor $borderColor"; ?> border p-4 rounded-lg mb-6 shadow">
            <?php echo htmlspecialchars($flash_message['message']); ?>
        </div>
    <?php endif; ?>
    <!-- จบส่วน Flash Message -->

    <h1 class="text-3xl font-bold mb-6 text-gray-900">โปรไฟล์ของฉัน</h1>
    
    <!-- (7) ข้อมูลผู้ใช้ (จาก Session) -->
    <div class="bg-white p-6 rounded-lg shadow mb-8">
        <h2 class="text-xl font-semibold mb-4 text-blue-700">ข้อมูลผู้ใช้</h2>
        <div class="space-y-2">
            <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></p>
            <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
            <p><strong>ระดับ:</strong> <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'customer'); ?></p>
            <!-- (ในอนาคต สามารถดึง "เบอร์โทร" และ "Line ID" จากฐานข้อมูล) -->
        </div>
    </div>

    <!-- (8) ส่วนการจองของฉัน (แปลงจาก React) -->
    <h2 class="text-2xl font-semibold mb-4 text-gray-900">การจองของฉัน</h2>
    
    <?php if (empty($userBookings)): ?>
        <div class="bg-white p-6 rounded-lg shadow text-gray-500">
            ยังไม่มีการจอง
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($userBookings as $booking): ?>
                <?php
                // (9) ค้นหารถที่ตรงกับการจอง (เหมือน .find())
                $motorcycle = null;
                foreach ($motorcycles_data as $m) {
                    if ($m['id'] == $booking['motorcycleId']) {
                        $motorcycle = $m;
                        break;
                    }
                }
                
                // (10) กำหนดสีของสถานะ
                $statusColor = 'text-gray-600';
                if ($booking['status'] === 'pending') $statusColor = 'text-yellow-600';
                if ($booking['status'] === 'confirmed') $statusColor = 'text-blue-600';
                if ($booking['status'] === 'active') $statusColor = 'text-green-600';
                if ($booking['status'] === 'cancelled') $statusColor = 'text-red-600';
                
                $paymentColor = 'text-gray-600';
                if ($booking['paymentStatus'] === 'pending') $paymentColor = 'text-yellow-600';
                if ($booking['paymentStatus'] === 'paid') $paymentColor = 'text-green-600';

                ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- รูปภาพรถ (ซ้าย) -->
                        <?php if ($motorcycle): ?>
                        <div class="w-full sm:w-1/3">
                            <img src="<?php echo htmlspecialchars($motorcycle['image']); ?>" alt="Bike" class="w-full h-32 object-cover rounded-lg">
                        </div>
                        <?php endif; ?>

                        <!-- ข้อมูลการจอง (ขวา) -->
                        <div class="flex-1">
                            <h3 class="text-xl font-bold mb-2">
                                <?php if ($motorcycle): ?>
                                    <?php echo htmlspecialchars($motorcycle['brand'] . ' ' . $motorcycle['model']); ?>
                                <?php else: ?>
                                    ไม่พบข้อมูลรถ (ID: <?php echo htmlspecialchars($booking['motorcycleId']); ?>)
                                <?php endif; ?>
                            </h3>
                            
                            <!-- แปลงจาก React -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-2 text-sm text-gray-700 mb-3">
                                <div><strong>วันที่รับ:</strong> <?php echo date('d/m/Y', strtotime($booking['startDate'])); ?></div>
                                <div><strong>วันที่คืน:</strong> <?php echo date('d/m/Y', strtotime($booking['endDate'])); ?></div>
                                <div><strong>จำนวนวัน:</strong> <?php echo $booking['totalDays']; ?> วัน</div>
                                <div><strong>สถานที่คืน:</strong> <?php echo htmlspecialchars($booking['returnLocation']); ?></div>
                            </div>
                            
                            <div class="border-t pt-3 mt-3">
                                <div class="text-lg font-semibold mb-2">
                                    ราคารวม: <span class="text-blue-700">฿<?php echo number_format($booking['totalPrice'], 0); ?></span>
                                </div>
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    <div>
                                        สถานะ: <span class="font-medium <?php echo $statusColor; ?>"><?php echo htmlspecialchars($booking['status']); ?></span>
                                    </div>
                                    <div>
                                        การชำระเงิน: <span class="font-medium <?php echo $paymentColor; ?>"><?php echo htmlspecialchars($booking['paymentStatus']); ?></span>
                                    </div>
                                </div>
                                <?php if (!empty($booking['specialOffers'])): ?>
                                    <div class="text-green-700 text-sm mt-2 p-2 bg-green-50 rounded">
                                        🎉 <?php echo htmlspecialchars($booking['specialOffers']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <!-- จบส่วนแปลงจาก React -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>