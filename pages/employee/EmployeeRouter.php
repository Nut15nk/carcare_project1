<?php
// pages/employee/EmployeeRouter.php
// Router for employee area. Reuses admin/sections pages for functionality

// Require session
// (index.php already started session)

// Check role: allow if employee or admin
$isAdmin = (isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'admin@temptation.com');
$isEmployee = (
    (isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'employee@temptation.com') ||
    (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employee')
);

if (!($isAdmin || $isEmployee)) {
    $_SESSION['flash_message'] = [
        'type' => 'error',
        'message' => 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้'
    ];
    header('Location: index.php?page=home');
    exit;
}

$section = $_GET['section'] ?? 'dashboard';

$pages = [
    'dashboard' => 'pages/admin/sections/EmployeeDashboard.php',
    'employees' => 'pages/admin/sections/EmployeeDashboard.php',
    'payments' => 'pages/admin/sections/PaymentsPage.php',
    'bookings' => 'pages/admin/sections/BookingManagement.php',
    'motorcycles' => 'pages/admin/sections/MotorcyclesManagement.php',
    'customers' => 'pages/admin/sections/CustomersManagement.php',
    'reports' => 'pages/admin/sections/ReportsPage.php',
];

if (!array_key_exists($section, $pages)) {
    $section = 'dashboard';
}

$employeeContentFile = $pages[$section];

?>

<div class="min-h-screen bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">พนักงาน - จัดการงาน</h1>
            <p class="text-gray-600 mt-2">ยินดีต้อนรับ <?php echo htmlspecialchars($_SESSION['user_email'] ?? 'พนักงาน'); ?></p>
        </div>

        <div class="mb-6 border-b border-gray-200">
            <nav class="flex space-x-4" aria-label="Tabs">
                
                <!-- Dashboard Tab -->
                <a 
                    href="index.php?page=employee&section=dashboard" 
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo ($section === 'dashboard') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="bar-chart-3" class="inline h-4 w-4 mr-2"></i>
                    แดชบอร์ด
                </a>

                <!-- Employees Tab -->
                <a 
                    href="index.php?page=employee&section=employees" 
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo ($section === 'employees') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="users" class="inline h-4 w-4 mr-2"></i>
                    จัดการพนักงาน
                </a>

                <!-- Payments Tab -->
                <a 
                    href="index.php?page=employee&section=payments" 
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo ($section === 'payments') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="credit-card" class="inline h-4 w-4 mr-2"></i>
                    ชำระเงิน
                </a>
                <!-- Bookings Management -->
                <a 
                    href="index.php?page=employee&section=bookings" 
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo ($section === 'bookings') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="calendar" class="inline h-4 w-4 mr-2"></i>
                    จัดการการจอง
                </a>

                <!-- Motorcycles Management -->
                <a 
                    href="index.php?page=employee&section=motorcycles" 
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo ($section === 'motorcycles') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="bike" class="inline h-4 w-4 mr-2"></i>
                    จัดการรถเช่า
                </a>

                <!-- Customers Management -->
                <a 
                    href="index.php?page=employee&section=customers" 
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo ($section === 'customers') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="users" class="inline h-4 w-4 mr-2"></i>
                    จัดการลูกค้า
                </a>

                <!-- Reports (Admin only) -->
                <?php if ($isAdmin): ?>
                <a 
                    href="index.php?page=employee&section=reports" 
                    class="px-3 py-2 font-medium text-sm rounded-t-lg <?php echo ($section === 'reports') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:text-gray-900'; ?>"
                >
                    <i data-lucide="file-text" class="inline h-4 w-4 mr-2"></i>
                    รายงาน
                </a>
                <?php endif; ?>
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
