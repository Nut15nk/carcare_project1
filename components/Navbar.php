<?php
    // ตรวจสอบ session status - ใช้ structure เดียวกันทั่วระบบ
    $isLoggedIn = isset($_SESSION['user']);
    $userEmail  = $_SESSION['user']['email'] ?? $_SESSION['user_email'] ?? '';
    $userName   = $_SESSION['user']['firstName'] ?? $_SESSION['user_name'] ?? $userEmail;
    $userRole   = $_SESSION['user']['role'] ?? $_SESSION['user_role'] ?? '';

    // ตรวจสอบ role จาก database จริง
    $isOwner    = in_array($userRole, ['owner', 'OWNER'], true);
    $isAdmin    = in_array($userRole, ['admin', 'ADMIN'], true);
    $isEmployee = in_array($userRole, ['employee', 'EMPLOYEE'], true);
    $isCustomer = ! $isOwner && ! $isAdmin && ! $isEmployee;

?>
<style>
.menu-item {
    display: block;
    padding: 0.5rem 1rem;
    color: #000000;
    font-size: 0.875rem;
}

.menu-item:hover {
    background-color: #fff;
}
</style>


<nav class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- Logo -->
            <a href="index.php?page=home" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                <i data-lucide="bike" class="h-8 w-8"></i>
                <div>
                    <span class="text-xl font-bold">เทมป์เทชัน</span>
                    <p class="text-xs text-blue-200">มอเตอร์ไซค์ให้เช่า</p>
                </div>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php?page=home" class="hover:text-blue-200 transition-colors font-medium">หน้าแรก</a>
                <a href="index.php?page=motorcycles" class="hover:text-blue-200 transition-colors font-medium">รถเช่า</a>

                <?php if ($isLoggedIn): ?>
                    <!-- เมนูสำหรับผู้ใช้ที่ล็อกอินแล้ว -->
                    <div class="flex items-center space-x-6">
                        <!-- เมนูการจอง -->
                        <a href="index.php?page=my-bookings" class="flex items-center space-x-1 hover:text-blue-200 transition-colors font-medium">
                            <i data-lucide="calendar" class="h-4 w-4"></i>
                            <span>การจองของฉัน</span>
                        </a>



                        <?php if ($isOwner || $isAdmin || $isEmployee): ?>
                            <div class="relative" id="management-menu">
                                <button class="flex items-center space-x-1 hover:text-blue-200 transition-colors font-medium"
                                        id="management-button">
                                    <i data-lucide="settings" class="h-4 w-4"></i>
                                    <span>จัดการ</span>
                                    <i data-lucide="chevron-down" class="h-4 w-4" id="management-chevron"></i>
                                </button>

                                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden z-50 border"
                                    id="management-dropdown">

                                    <?php if ($isOwner): ?>
                                        <!-- OWNER เห็นทุกอย่าง -->
                                        <a href="index.php?page=admin" class="menu-item">แดชบอร์ด</a>
                                        <a href="index.php?page=admin&section=motorcycles" class="menu-item">จัดการรถ</a>
                                        <a href="index.php?page=admin&section=bookings" class="menu-item">จัดการการจอง</a>
                                        <a href="index.php?page=admin&section=customers" class="menu-item">จัดการลูกค้า</a>
                                        <a href="index.php?page=admin&section=users" class="menu-item">จัดการผู้ใช้</a>

                                    <?php elseif ($isAdmin): ?>
                                        <!-- ADMIN เห็นแค่นี้ -->
                                        <a href="index.php?page=admin&section=motorcycles" class="menu-item">จัดการรถ</a>
                                        <a href="index.php?page=admin&section=bookings" class="menu-item">จัดการการจอง</a>
                                        <a href="index.php?page=admin&section=customers" class="menu-item">จัดการลูกค้า</a>

                                    <?php elseif ($isEmployee): ?>
                                        <!-- EMPLOYEE เห็นแค่นี้ -->
                                        <a href="index.php?page=employee&section=bookings" class="menu-item">จัดการการจอง</a>
                                        <a href="index.php?page=employee&section=motorcycles" class="menu-item">จัดการรถ</a>
                                    <?php endif; ?>

                                </div>
                            </div>
                            <?php endif; ?>


                        <!-- เมนูผู้ใช้ -->
                        <div class="relative" id="user-menu">
                            <button class="flex items-center space-x-2 hover:text-blue-200 transition-colors font-medium" id="user-button">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                    <i data-lucide="user" class="h-4 w-4"></i>
                                </div>
                                <span class="max-w-32 truncate"><?php echo htmlspecialchars($userName ?: $userEmail); ?></span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition-transform" id="user-chevron"></i>
                            </button>

                            <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg py-2 hidden z-50 border" id="user-dropdown">
                                <div class="px-4 py-3 border-b">
                                    <p class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($userName ?: $userEmail); ?></p>
                                    <p class="text-sm text-gray-600 truncate"><?php echo htmlspecialchars($userEmail); ?></p>
                                    <?php if ($isAdmin || $isEmployee): ?>
                                        <div class="mt-1">
                                            <?php if ($isAdmin): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                    <i data-lucide="shield" class="h-3 w-3 mr-1"></i>
                                                    ผู้ดูแลระบบ
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <i data-lucide="briefcase" class="h-3 w-3 mr-1"></i>
                                                    พนักงาน
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <a href="index.php?page=profile" class="flex items-center space-x-2 px-4 py-2 text-gray-700 hover:bg-blue-50 transition-colors">
                                    <i data-lucide="user" class="h-4 w-4"></i>
                                    <span>โปรไฟล์ของฉัน</span>
                                </a>
                                <a href="index.php?page=my-bookings" class="flex items-center space-x-2 px-4 py-2 text-gray-700 hover:bg-blue-50 transition-colors">
                                    <i data-lucide="calendar" class="h-4 w-4"></i>
                                    <span>การจองของฉัน</span>
                                </a>

                                <hr class="my-2">

                                <a href="logout.php" class="flex items-center space-x-2 px-4 py-2 text-gray-700 hover:bg-red-50 transition-colors">
                                    <i data-lucide="log-out" class="h-4 w-4"></i>
                                    <span>ออกจากระบบ</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- เมนูสำหรับผู้ใช้ที่ยังไม่ได้ล็อกอิน -->
                    <div class="flex items-center space-x-4">
                        <a href="login.php" class="hover:text-blue-200 transition-colors font-medium">เข้าสู่ระบบ</a>
                        <a href="register.php" class="bg-blue-500 hover:bg-blue-400 px-4 py-2 rounded-lg transition-colors font-medium">
                            สมัครสมาชิก
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-white hover:text-blue-200 p-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile menu -->
        <div id="mobile-menu-content" class="md:hidden mt-2 flex flex-col space-y-1 hidden pb-4 border-t border-blue-700 pt-4">
            <a href="index.php?page=home" class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium">หน้าแรก</a>
            <a href="index.php?page=motorcycles" class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium">รถเช่า</a>

            <?php if ($isLoggedIn): ?>
                <hr class="border-blue-700 mx-4"/>

                <!-- เมนูการจอง (Mobile) -->
                <a href="index.php?page=my-bookings" class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium flex items-center space-x-3">
                    <i data-lucide="calendar" class="h-5 w-5"></i>
                    <span>การจองของฉัน</span>
                </a>

                <?php if ($isOwner || $isAdmin || $isEmployee): ?>
                    <div class="px-4 py-3">
                        <p class="text-blue-200 text-sm font-medium mb-2">การจัดการ</p>

                        <?php if ($isOwner): ?>
                            <a href="index.php?page=admin" class="block py-2">แดชบอร์ด</a>
                            <a href="index.php?page=admin&section=motorcycles" class="block py-2">จัดการรถ</a>
                            <a href="index.php?page=admin&section=bookings" class="block py-2">จัดการการจอง</a>
                            <a href="index.php?page=admin&section=customers" class="block py-2">จัดการลูกค้า</a>
                            <a href="index.php?page=admin&section=users" class="block py-2">จัดการผู้ใช้</a>

                        <?php elseif ($isAdmin): ?>
                            <a href="index.php?page=admin&section=motorcycles" class="block py-2">จัดการรถ</a>
                            <a href="index.php?page=admin&section=bookings" class="block py-2">จัดการการจอง</a>
                            <a href="index.php?page=admin&section=customers" class="block py-2">จัดการลูกค้า</a>

                        <?php elseif ($isEmployee): ?>
                            <a href="index.php?page=employee&section=bookings" class="menu-item">จัดการการจอง</a>
                            <a href="index.php?page=employee&section=motorcycles" class="menu-item">จัดการรถ</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>


                <!-- ข้อมูลผู้ใช้ (Mobile) -->
                <div class="px-4 py-3 border-t border-blue-700 mt-2 pt-4">
                    <p class="font-medium text-blue-100"><?php echo htmlspecialchars($userName ?: $userEmail); ?></p>
                    <p class="text-sm text-blue-300"><?php echo htmlspecialchars($userEmail); ?></p>
                    <?php if ($isAdmin || $isEmployee): ?>
                        <div class="mt-2">
                            <?php if ($isAdmin): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-600 text-white">
                                    <i data-lucide="shield" class="h-3 w-3 mr-1"></i>
                                    ผู้ดูแลระบบ
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-600 text-white">
                                    <i data-lucide="briefcase" class="h-3 w-3 mr-1"></i>
                                    พนักงาน
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- เมนูผู้ใช้ (Mobile) -->
                <a href="index.php?page=profile" class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium flex items-center space-x-3">
                    <i data-lucide="user" class="h-5 w-5"></i>
                    <span>โปรไฟล์ของฉัน</span>
                </a>

                <a href="logout.php" class="mobile-menu-link px-4 py-3 hover:bg-red-700 transition-colors rounded-lg font-medium flex items-center space-x-3 mt-2">
                    <i data-lucide="log-out" class="h-5 w-5"></i>
                    <span>ออกจากระบบ</span>
                </a>
            <?php else: ?>
                <hr class="border-blue-700 mx-4"/>
                <a href="login.php" class="mobile-menu-link px-4 py-3 hover:bg-blue-700 transition-colors rounded-lg font-medium">เข้าสู่ระบบ</a>
                <a href="register.php" class="mobile-menu-link px-4 py-3 bg-blue-500 hover:bg-blue-400 transition-colors rounded-lg font-medium text-center mx-4">
                    สมัครสมาชิก
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Mobile Menu Toggle
    const menuButton = document.getElementById('mobile-menu-button');
    const menuContent = document.getElementById('mobile-menu-content');
    const menuLinks = document.querySelectorAll('.mobile-menu-link');

    if (menuButton && menuContent) {
        menuButton.addEventListener('click', function() {
            menuContent.classList.toggle('hidden');
        });

        menuLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                menuContent.classList.add('hidden');
            });
        });
    }

    // User Dropdown Toggle
    const userButton = document.getElementById('user-button');
    const userDropdown = document.getElementById('user-dropdown');
    const userChevron = document.getElementById('user-chevron');

    if (userButton && userDropdown) {
        userButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const isHidden = userDropdown.classList.contains('hidden');

            // Close all other dropdowns
            closeAllDropdowns();

            // Toggle this dropdown
            userDropdown.classList.toggle('hidden', !isHidden);
            userChevron.classList.toggle('rotate-180', !isHidden);
        });
    }

    // Management Dropdown Toggle
    const managementButton = document.getElementById('management-button');
    const managementDropdown = document.getElementById('management-dropdown');
    const managementChevron = document.getElementById('management-chevron');

    if (managementButton && managementDropdown) {
        managementButton.addEventListener('click', function(e) {
            e.stopPropagation();
            const isHidden = managementDropdown.classList.contains('hidden');

            // Close all other dropdowns
            closeAllDropdowns();

            // Toggle this dropdown
            managementDropdown.classList.toggle('hidden', !isHidden);
            managementChevron.classList.toggle('rotate-180', !isHidden);
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        closeAllDropdowns();
    });

    // Prevent dropdown close when clicking inside dropdown
    if (userDropdown) {
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    if (managementDropdown) {
        managementDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    function closeAllDropdowns() {
        if (userDropdown) {
            userDropdown.classList.add('hidden');
            userChevron.classList.remove('rotate-180');
        }
        if (managementDropdown) {
            managementDropdown.classList.add('hidden');
            managementChevron.classList.remove('rotate-180');
        }
    }

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>