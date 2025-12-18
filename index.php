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

    // Whitelist page map
    $pageMap = [
        'home'                 => 'pages/HomePages.php',
        'motorcycles'          => 'pages/MotorcyclesPages.php',
        'booking'              => 'pages/BookingPages.php',
        'booking-confirmation' => 'pages/BookingConfirmation.php',
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
        $contentFile = 'pages/404.php';
    }

    // --- หมายเหตุ ---
    // ลบ POST handler ของ booking ออกไป เพื่อให้ BookingPages.php จัดการเอง
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
        <div class="fixed top-20 right-4 z-50<?php echo $flash_message['type'] === 'error' ? 'bg-red-500' : 'bg-green-500'; ?> text-white px-6 py-3 rounded-lg shadow-lg">
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
                include 'pages/404.php';
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
