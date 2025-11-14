<?php
// เริ่ม session ในทุกหน้าที่ด้านบนสุด
session_start();
// เปิด output buffering เพื่อให้ไฟล์ที่ถูก include สามารถเรียก header() ได้
// โดยไม่เกิด "headers already sent" เมื่อมีการส่ง HTML แล้ว
ob_start();

// (0) Auth Guard: ตรวจสอบว่า page ไหนต้อง login ก่อน
// ถ้า page เป็น 'booking' แต่ยังไม่ได้ login ให้เด้งไปหน้า login
if (($_GET['page'] ?? 'home') === 'booking' && !isset($_SESSION['user_email'])) {
  $_SESSION['redirect_url'] = "index.php?page=booking&id=" . ($_GET['id'] ?? '');
  $_SESSION['flash_message'] = [
    'type' => 'error',
    'message' => 'กรุณาเข้าสู่ระบบก่อนทำการจอง'
  ];
  header("Location: /login.php");
  exit;
}

// หากเข้าถึง index.php โดยตรง ให้เด้งไปหน้า home
if (basename($_SERVER['PHP_SELF']) === 'index.php' && empty($_GET['page'])) {
  header('Location: /index.php?page=home');
  exit;
}

// ดึงชื่อหน้าจาก URL, ถ้าไม่ระบุ ให้เป็น 'home'
// เช่น: index.php?page=login
$page = $_GET['page'] ?? 'home';

// เด้งไปหน้า login.php โดยตรง
if ($page === 'login') {
  header('Location: /login.php');
  exit;
}

// เด้งไปหน้า register.php โดยตรง
if ($page === 'register') {
  header('Location: /register.php');
  exit;
}

// รายการหน้าที่อนุญาต (Whitelist) เพื่อความปลอดภัย
// นี่คือการจับคู่ "page" parameter กับ "ไฟล์" ที่จะ include
$pageMap = [
  'home' => 'pages/HomePages.php',
  'motorcycles' => 'pages/MotorcyclesPages.php',
  'booking' => 'pages/BookingPages.php', // จะต้องรับ 'id' เพิ่ม
  'profile' => 'pages/ProfilePages.php',

  // สำหรับ /employee/* เราจะชี้ไปที่ router ของ employee
  'employee' => 'pages/employee/EmployeeRouter.php',

  // สำหรับ /admin/* เราจะชี้ไปที่ router ของ admin
  'admin' => 'pages/admin/AdminRouter.php',
];

// ตรวจสอบว่าหน้าที่ขอมีอยู่ใน $pageMap หรือไม่
if (array_key_exists($page, $pageMap)) {
  $contentFile = $pageMap[$page];
} else {
  // ถ้าไม่มี, ให้ไปหน้า 404
  http_response_code(404);
  $contentFile = 'pages/404.php';
}

// (POST Handler) ตรวจสอบ POST request จาก booking form
// ต้องทำก่อน include navbar เพื่อให้สามารถเรียก header() ได้
if ($page === 'booking' && $_SERVER["REQUEST_METHOD"] == "POST") {
  // ดึง POST data
  $startDate = $_POST['start_date'] ?? '';
  $endDate = $_POST['end_date'] ?? '';
  $returnLocation = $_POST['return_location'] ?? '';
  
  // ตรวจสอบไฟล์อัพโหลด
  if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] == UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }
    
    $fileName = time() . '_' . basename($_FILES['payment_proof']['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $targetPath)) {
      // อัพโหลดสำเร็จ - ตั้งค่า flash message และ redirect
      $_SESSION['flash_message'] = [
        'type' => 'success',
        'message' => 'จองสำเร็จ! เราจะติดต่อกลับภายใน 24 ชั่วโมง'
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
  <!-- 
      ใช้ Tailwind CSS CDN เพื่อให้ตรงกับ App.tsx 
      (ไฟล์ PHP เดิมของคุณใช้ Bootstrap แต่ App.tsx ใช้ Tailwind)
    -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons CDN -->
  <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="min-h-screen bg-gray-50 flex flex-col">

  <!-- 1. ส่วน Navbar (เหมือน <Navbar />) -->
  <?php include 'components/Navbar.php'; ?>

  <!-- 2. ส่วนเนื้อหาหลัก (เหมือน <main className="flex-1">) -->
  <main class="flex-1">
    <?php
    // โหลดไฟล์เนื้อหาที่เลือกโดย router
    if (file_exists($contentFile)) {
      include $contentFile;
    } else {
      // กรณีไฟล์ที่ระบุใน $pageMap หายไป
      echo "<p class='text-center text-red-500 p-4'>Error: Content file not found.</p>";
      include 'pages/404.php';
    }
    ?>
  </main>

  <!-- 3. ส่วน Footer (เหมือน <Footer />) -->
  <?php include 'components/Footer.php'; ?>
  <script>
    lucide.createIcons();
  </script>

</body>

</html>