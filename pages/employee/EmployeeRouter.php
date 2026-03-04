<?php
    // pages/employee/EmployeeRouter.php
    // Router for employee area. Reuses admin/sections pages for functionality

    // Require session
    // (index.php already started session)

    // ✅ ใช้ structure เดียวกับระบบ (ใช้ $_SESSION['user']['role'])
    $user     = $_SESSION['user'] ?? null;
    $userRole = '';

    // ตรวจสอบจาก structure ใหม่ (จาก login.php)
    if ($user && isset($user['role'])) {
        $userRole = strtolower($user['role']);
    }
    // fallback ไป structure เก่า
    else if (isset($_SESSION['user_role'])) {
        $userRole = strtolower($_SESSION['user_role']);
    }

    // ตรวจสอบว่าเป็น employee เท่านั้นที่เข้าได้ (owner และ admin ใช้ admin router)
    $isEmployee = $userRole === 'employee';

    // อนุญาตให้ employee เท่านั้น
    if (! $isEmployee) {
        $_SESSION['flash_message'] = [
            'type'    => 'error',
            'message' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้',
        ];
        header('Location: index.php?page=home');
        exit;
    }

    $section = $_GET['section'] ?? 'motorcycles';

    $pages = [
        'bookings'    => 'pages/admin/sections/BookingManagement.php',
        'motorcycles' => 'pages/admin/sections/MotorcyclesManagement.php',
        'customers'   => 'pages/admin/sections/CustomersManagement.php',
        // 'reports'     => 'pages/admin/sections/ReportsPage.php', // ไม่ให้ employee เข้าถึง reports
    ];

    if (! array_key_exists($section, $pages)) {
        $section = 'motorcycles';
    }

    $employeeContentFile = $pages[$section];

?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">พนักงาน - จัดการงาน</h1>
            <p class="text-gray-600 mt-2">ยินดีต้อนรับ 
                <?php 
                    // แสดงชื่อจาก structure ใหม่
                    if (isset($_SESSION['user']['firstName'])) {
                        echo htmlspecialchars($_SESSION['user']['firstName'] . ' ' . ($_SESSION['user']['lastName'] ?? ''));
                    } else if (isset($_SESSION['user_email'])) {
                        echo htmlspecialchars($_SESSION['user_email']);
                    } else {
                        echo 'พนักงาน';
                    }
                ?>
            </p>
        </div>

        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-4" aria-label="Tabs">

                <!-- Motorcycles Management -->
                <a
                    href="index.php?page=employee&section=motorcycles"
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'motorcycles') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="bike" class="inline h-4 w-4 mr-2"></i>
                    จัดการรถ
                </a>

                <!-- Bookings Management -->
                <a
                    href="index.php?page=employee&section=bookings"
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'bookings') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="calendar" class="inline h-4 w-4 mr-2"></i>
                    จัดการการจอง
                </a>

                <!-- Customers Management -->
                <a
                    href="index.php?page=employee&section=customers"
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo($section === 'customers') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="users" class="inline h-4 w-4 mr-2"></i>
                    จัดการลูกค้า
                </a>

            </nav>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <?php
                if (file_exists($employeeContentFile)) {
                    include $employeeContentFile;
                } else {
                    echo "<div class='bg-yellow-50 border border-yellow-200 p-4 rounded-lg'>";
                    echo "<p class='text-yellow-800'><strong>⚠️ ข้อมูล:</strong> ไฟล์ " . htmlspecialchars($employeeContentFile) . " ยังไม่ได้สร้าง</p>";
                    echo "</div>";
                }
            ?>
        </div>
    </div>
</div>