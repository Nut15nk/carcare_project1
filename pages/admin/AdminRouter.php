<?php
    // pages/admin/AdminRouter.php

    require_once __DIR__ . '/../../service/Admin/AdminService.php';


    // (1) ดึง sub-page จาก URL (ต้องรู้ section ก่อนตรวจสิทธิ์)
    $section = $_GET['section'] ?? 'dashboard';

    // (2) ตรวจสอบสิทธิ์: ใช้ structure เดียวกัน
    $user     = $_SESSION['user'] ?? null;
    $userRole = '';

    // ตรวจสอบจาก structure ใหม่ก่อน (จาก login.php)
    if ($user && isset($user['role'])) {
        $userRole = strtolower($user['role']);
    }
    // fallback ไป structure เก่า
    else if (isset($_SESSION['user_role'])) {
        $userRole = strtolower($_SESSION['user_role']);
    }

    $isOwner    = $userRole === 'owner';
    $isAdmin    = $userRole === 'admin';
    $isEmployee = $userRole === 'employee';

    // ตรวจสอบว่าล็อกอินแล้วหรือยัง
    $isLoggedIn = isset($_SESSION['user']) || isset($_SESSION['user_email']);

    if (! $isLoggedIn) {
        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'กรุณาเข้าสู่ระบบก่อน',
        ];
        header("Location: login.php");
        exit;
    }

    // ✅ owner เข้าทุกหน้าได้
    // ✅ admin เข้าหน้าที่กำหนด
    // ✅ employee ใช้ employee router เท่านั้น (ไม่ควรมาที่นี่)

    // ตรวจสอบสิทธิ์ตาม section
    $allowedForAdmin = ['dashboard', 'motorcycles', 'bookings', 'customers'];
    $allowedForOwner = ['dashboard', 'motorcycles', 'bookings', 'customers', 'employees', 'discounts', 'reports'];

    if ($isOwner) {
        // owner เข้าได้ทุกหน้าที่มีใน $adminPages
        // ไม่ต้องตรวจสอบเพิ่ม
    } 
    elseif ($isAdmin) {
        // admin เข้าได้เฉพาะหน้าที่กำหนด
        if (!in_array($section, $allowedForAdmin)) {
            $_SESSION['flash_message'] = [
                'type'    => 'error',
                'message' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้',
            ];
            header("Location: index.php?page=admin&section=dashboard");
            exit;
        }
    } 
    else {
        // คนอื่นไม่มีสิทธิ์ (รวมถึง employee)
        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้',
        ];
        header("Location: index.php?page=home");
        exit;
    }

    // (3) Whitelist ของ sub-pages
    $adminPages = [
        'dashboard'   => 'pages/admin/sections/AdminDashboard.php',
        'employees'   => 'pages/admin/sections/EmployeeDashboard.php',
        'bookings'    => 'pages/admin/sections/BookingManagement.php',
        'motorcycles' => 'pages/admin/sections/MotorcyclesManagement.php',
        'customers'   => 'pages/admin/sections/CustomersManagement.php',
        'reports'     => 'pages/admin/sections/ReportsPage.php',
        'discounts'   => 'pages/admin/sections/DiscountManagement.php',
    ];

    // (4) ตรวจสอบว่า section มีอยู่หรือไม่
    if (array_key_exists($section, $adminPages)) {
        $adminContentFile = $adminPages[$section];
    } else {
        // ถ้าไม่มี default ไป dashboard
        $adminContentFile = $adminPages['dashboard'];
    }

?>

<!-- Admin Layout -->
<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Admin Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">จัดการระบบ</h1>
            <p class="text-gray-600 mt-2">
                ยินดีต้อนรับ
                <?php
                    if (isset($_SESSION['user']['firstName'])) {
                        echo htmlspecialchars($_SESSION['user']['firstName'] . ' ' . ($_SESSION['user']['lastName'] ?? ''));
                    } else if (isset($_SESSION['user_name'])) {
                        echo htmlspecialchars($_SESSION['user_name']);
                    } else if (isset($_SESSION['user_email'])) {
                        echo htmlspecialchars($_SESSION['user_email']);
                    }
                ?>
            </p>
        </div>

        <!-- Admin Navigation Tabs (แสดงตามสิทธิ์) -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-4" aria-label="Tabs">

                <!-- Dashboard (owner และ admin) -->
                <?php if ($isOwner || $isAdmin): ?>
                <a href="index.php?page=admin&section=dashboard"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'dashboard') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="bar-chart-3" class="inline h-4 w-4 mr-2"></i>
                    แดชบอร์ด
                </a>
                <?php endif; ?>

                <!-- Motorcycles (owner และ admin) -->
                <?php if ($isOwner || $isAdmin): ?>
                <a href="index.php?page=admin&section=motorcycles"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'motorcycles') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="bike" class="inline h-4 w-4 mr-2"></i>
                    จัดการรถ
                </a>
                <?php endif; ?>

                <!-- Bookings (owner และ admin) -->
                <?php if ($isOwner || $isAdmin): ?>
                <a href="index.php?page=admin&section=bookings"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'bookings') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="calendar" class="inline h-4 w-4 mr-2"></i>
                    จัดการการจอง
                </a>
                <?php endif; ?>

                <!-- Employees (owner เท่านั้น) -->
                <?php if ($isOwner): ?>
                <a href="index.php?page=admin&section=employees"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'employees') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="users" class="inline h-4 w-4 mr-2"></i>
                    จัดการพนักงาน
                </a>
                <?php endif; ?>

                <!-- Customers (owner และ admin) -->
                <?php if ($isOwner || $isAdmin): ?>
                <a href="index.php?page=admin&section=customers"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'customers') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="users" class="inline h-4 w-4 mr-2"></i>
                    จัดการลูกค้า
                </a>
                <?php endif; ?>

                <!-- Discounts (owner เท่านั้น) -->
                <?php if ($isOwner): ?>
                <a href="index.php?page=admin&section=discounts"
                   class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'discounts') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>">
                    <i data-lucide="tag" class="inline h-4 w-4 mr-2"></i>
                    ส่วนลด
                </a>
                <?php endif; ?>

            </nav>
        </div>

        <!-- Admin Content -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <?php
                if (file_exists($adminContentFile)) {
                    include $adminContentFile;
                } else {
                    echo "<div class='bg-yellow-50 border border-yellow-200 p-4 rounded-lg'>";
                    echo "<p class='text-yellow-800'><strong>⚠️ ข้อมูล:</strong> ไฟล์ " . htmlspecialchars($adminContentFile) . " ยังไม่ได้สร้าง</p>";
                    echo "</div>";
                }
            ?>
        </div>
    </div>
</div>