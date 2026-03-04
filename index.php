<?php
    // เริ่ม session และ output buffering
    if (session_status() === PHP_SESSION_NONE) {
    session_start();
    }
    ob_start();

    // ตรวจสอบ flash message จากหน้าก่อนหน้า
    $flash_message = $_SESSION['flash_message'] ?? null;
    if ($flash_message) {
    unset($_SESSION['flash_message']);
    }

    // กำหนดหน้าเริ่มต้น
    $currentPage = $_GET['page'] ?? 'home';

    // Auth Guard: หน้า booking ต้อง login
    if ($currentPage === 'booking' && ! isset($_SESSION['user'])) {
    $_SESSION['redirect_url']  = $_SERVER['REQUEST_URI'];
    $_SESSION['flash_message'] = [
        'type'    => 'error',
        'message' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง',
    ];
    header("Location: login.php");
    exit;
    }

    // redirect ถ้าเข้าผ่าน index.php โดยตรง
    if (basename($_SERVER['PHP_SELF']) === 'index.php' && empty($_GET['page'])) {
    header('Location: index.php?page=home');
    exit;
    }

    // ดึงชื่อ page
    $page = $_GET['page'] ?? 'home';

    // ===== API ROUTER (สำคัญมาก) =====
    if (($page) === 'api') {
    require __DIR__ . '/pages/api.php';
    exit;
    }

    // Whitelist page map
    $pageMap = [
    'home'                 => 'pages/HomePages.php',
    'motorcycles'          => 'pages/MotorcyclesPages.php',
    'booking'              => 'pages/BookingPages.php',
    'booking-confirmation' => 'pages/TrackingPage.php',
    'tracking'             => 'pages/TrackingPage.php', // เพิ่ม tracking ด้วย
    'payment'              => 'pages/PaymentPages.php',
    'my-bookings'          => 'pages/MyBookings.php',
    'profile'              => 'pages/ProfilePages.php',
    'employee'             => 'pages/employee/EmployeeRouter.php',
    'admin'                => 'pages/admin/AdminRouter.php',
    ];

    // ตรวจสอบว่า page ที่ขอมีอยู่หรือไม่
    if (array_key_exists($page, $pageMap)) {
    $contentFile = $pageMap[$page];
    } else {
    http_response_code(404);
    $contentFile = 'pages/404.php'; // ใช้ไฟล์ 404 ที่เราสร้าง
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motorcycle Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">

    <!-- Navbar -->
    <?php include 'components/Navbar.php'; ?>

    <!-- Flash Message -->
    <?php if ($flash_message): ?>
        <div class="fixed top-20 right-4 z-50 <?php echo $flash_message['type'] === 'error' ? 'bg-red-500' : 'bg-green-500'; ?> text-white px-6 py-3 rounded-lg shadow-lg">
            <?php echo $flash_message['message']; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1">
        <?php
            if (file_exists($contentFile)) {
                include $contentFile;
            } else {
                echo "<p class='text-center text-red-500 p-4'>Error: Content file not found.</p>";
                // ถ้าไม่มีไฟล์ 404 จริงๆ ก็แสดงข้อความธรรมดา
                echo "<div class='min-h-[60vh] flex items-center justify-center'>";
                echo "<div class='text-center'>";
                echo "<h1 class='text-4xl font-bold text-gray-900 mb-4'>404</h1>";
                echo "<p class='text-gray-600 mb-8'>ไม่พบหน้าที่คุณต้องการ</p>";
                echo "<a href='index.php?page=home' class='bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg'>กลับหน้าหลัก</a>";
                echo "</div></div>";
            }
        ?>
    </main>

    <!-- Footer -->
    <?php include 'components/Footer.php'; ?>

    <script>
        lucide.createIcons();

        // Auto-hide flash message
        setTimeout(() => {
            const flashMsg = document.querySelector('.fixed.top-20');
            if (flashMsg) {
                flashMsg.remove();
            }
        }, 5000);
    </script>
</body>
</html>