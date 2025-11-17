<?php
    // เริ่ม session ในทุกหน้าที่ด้านบนสุด
    session_start();
    // เปิด output buffering เพื่อให้ไฟล์ที่ถูก include สามารถเรียก header() ได้
    ob_start();

    // ตรวจสอบว่ามี flash message จากหน้าที่แล้วหรือไม่
    $flash_message = $_SESSION['flash_message'] ?? null;
    if ($flash_message) {
        unset($_SESSION['flash_message']);
    }

    // (0) Auth Guard: ตรวจสอบว่า page ไหนต้อง login ก่อน
    $currentPage = $_GET['page'] ?? 'home';
    if ($currentPage === 'booking' && ! isset($_SESSION['user'])) {
        $_SESSION['redirect_url']  = $_SERVER['REQUEST_URI'];
        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง',
        ];
        header("Location: login.php");
        exit;
    }

    // หากเข้าถึง index.php โดยตรง ให้เด้งไปหน้า home
    if (basename($_SERVER['PHP_SELF']) === 'index.php' && empty($_GET['page'])) {
        header('Location: index.php?page=home');
        exit;
    }

    // ดึงชื่อหน้าจาก URL
    $page = $_GET['page'] ?? 'home';

    // รายการหน้าที่อนุญาต (Whitelist)
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

    // ตรวจสอบว่าหน้าที่ขอมีอยู่ใน $pageMap หรือไม่
    if (array_key_exists($page, $pageMap)) {
        $contentFile = $pageMap[$page];
    } else {
        http_response_code(404);
        $contentFile = 'pages/404.php';
    }

    // (POST Handler) ตรวจสอบ POST request จาก booking form
    if ($page === 'booking' && $_SERVER["REQUEST_METHOD"] == "POST") {
        // ดึง POST data
        $startDate      = $_POST['start_date'] ?? '';
        $endDate        = $_POST['end_date'] ?? '';
        $returnLocation = $_POST['return_location'] ?? '';

        // ตรวจสอบไฟล์อัพโหลด
        if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';

            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName   = time() . '_' . basename($_FILES['payment_proof']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
                // อัพโหลดสำเร็จ - ตั้งค่า flash message และ redirect
                $_SESSION['flash_message'] = [
                    'type'    => 'success',
                    'message' => 'จองสำเร็จ! เราจะติดต่อกลับภายใน 24 ชั่วโมง',
                ];
                header("Location: index.php?page=profile");
                exit;
            }
        }
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

    <!-- 1. ส่วน Navbar -->
    <?php include 'components/Navbar.php'; ?>

    <!-- 2. Flash Message -->
    <?php if ($flash_message): ?>
        <div class="fixed top-20 right-4 z-50<?php echo $flash_message['type'] === 'error' ? 'bg-red-500' : 'bg-green-500'; ?> text-white px-6 py-3 rounded-lg shadow-lg">
            <?php echo $flash_message['message']; ?>
        </div>
    <?php endif; ?>

    <!-- 3. ส่วนเนื้อหาหลัก -->
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

    <!-- 4. ส่วน Footer -->
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