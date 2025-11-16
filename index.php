<?php
// เริ่ม session และ output buffering
session_start();
ob_start();

// โหลดไฟล์ API
require_once 'api/config.php';
require_once 'api/auth.php';

// (0) Auth Guard: ตรวจสอบการล็อกอิน
$currentPage = $_GET['page'] ?? 'home';

// หน้าที่ต้องล็อกอินก่อน
$protectedPages = ['booking', 'profile', 'admin', 'employee'];

if (in_array($currentPage, $protectedPages) && !AuthService::isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit;
}

// หากเข้าถึง index.php โดยตรง ให้เด้งไปหน้า home
if (basename($_SERVER['PHP_SELF']) === 'index.php' && empty($_GET['page'])) {
    header('Location: index.php?page=home');
    exit;
}

// รายการหน้าที่อนุญาต (Whitelist)
$pageMap = [
    'home' => 'pages/HomePages.php',
    'motorcycles' => 'pages/MotorcyclesPages.php',
    'booking' => 'pages/BookingPages.php',
    'profile' => 'pages/ProfilePages.php',
    'employee' => 'pages/employee/EmployeeRouter.php',
    'admin' => 'pages/admin/AdminRouter.php',
];

// ตรวจสอบว่าหน้าที่ขอมีอยู่ใน $pageMap หรือไม่
if (array_key_exists($currentPage, $pageMap)) {
    $contentFile = $pageMap[$currentPage];
} else {
    // ถ้าไม่มี, ให้ไปหน้า 404
    http_response_code(404);
    $contentFile = 'pages/404.php';
}

// จัดการ Flash Messages
$flashMessage = $_SESSION['flash_message'] ?? null;
if ($flashMessage) {
    unset($_SESSION['flash_message']); // ลบหลังจากแสดงแล้ว
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motorcycle Rental System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">

    <!-- Navbar -->
    <?php include 'components/Navbar.php'; ?>

    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
        <div class="fixed top-20 right-4 z-50 max-w-sm">
            <div class="<?php echo $flashMessage['type'] === 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-6 py-3 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <i data-lucide="<?php echo $flashMessage['type'] === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="h-5 w-5 mr-2"></i>
                    <span><?php echo htmlspecialchars($flashMessage['message']); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="flex-1">
        <?php
        // โหลดไฟล์เนื้อหาที่เลือก
        if (file_exists($contentFile)) {
            include $contentFile;
        } else {
            echo "<div class='container mx-auto px-4 py-8'>
                    <div class='text-center'>
                        <h1 class='text-2xl font-bold text-red-600 mb-4'>ไฟล์ไม่พบ</h1>
                        <p class='text-gray-600'>ไม่สามารถโหลดหน้า '$currentPage' ได้</p>
                        <a href='index.php?page=home' class='mt-4 inline-block bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700'>
                            กลับหน้าหลัก
                        </a>
                    </div>
                  </div>";
        }
        ?>
    </main>

    <!-- Footer -->
    <?php include 'components/Footer.php'; ?>

    <script>
        // เปิดใช้งาน Lucide icons
        lucide.createIcons();

        // Auto-hide flash messages after 5 seconds
        <?php if ($flashMessage): ?>
            setTimeout(() => {
                const flashMsg = document.querySelector('.fixed.top-20');
                if (flashMsg) {
                    flashMsg.style.transition = 'opacity 0.5s';
                    flashMsg.style.opacity = '0';
                    setTimeout(() => flashMsg.remove(), 500);
                }
            }, 5000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php ob_end_flush(); ?>