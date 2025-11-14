<!-- components/Navbar.php -->
<!-- อัปเดตเป็นเวอร์ชัน PHP ที่แปลงจาก React TSX -->
<nav class="bg-gradient-to-r from-blue-600 to-blue-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <!-- Logo -->
            <a href="index.php?page=home" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                <!-- แปลง <Bike> เป็น <i data-lucide="..."> -->
                <i data-lucide="bike" class="h-8 w-8"></i>
                <div>
                    <span class="text-xl font-bold">เทมป์เทชัน</span>
                    <p class="text-xs text-blue-200">มอเตอร์ไซค์ให้เช่า</p>
                </div>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="index.php?page=home" class="hover:text-blue-200 transition-colors">หน้าแรก</a>
                <a href="index.php?page=motorcycles" class="hover:text-blue-200 transition-colors">รถเช่า</a>
                
                <?php // แปลง {user ? (...) : (...)} เป็น if/else ของ PHP ?>
                <?php
                // Dev helper: จำลองการล็อกอินแบบรวดเร็วโดยใช้ query param ?as=employee | as=admin | as=logout
                if (isset($_GET['as'])) {
                    $who = $_GET['as'];
                    if ($who === 'employee') {
                        $_SESSION['user_email'] = 'employee@temptation.com';
                        $_SESSION['user_role'] = 'employee';
                        $_SESSION['user_name'] = 'พนักงานตัวอย่าง';
                    } elseif ($who === 'admin') {
                        $_SESSION['user_email'] = 'admin@temptation.com';
                        unset($_SESSION['user_role']);
                        $_SESSION['user_name'] = 'แอดมินตัวอย่าง';
                    } elseif ($who === 'logout') {
                        unset($_SESSION['user_email'], $_SESSION['user_role'], $_SESSION['user_name']);
                    }
                }
                ?>
                <?php if (isset($_SESSION['user_email'])): // ตรวจสอบว่าล็อกอินหรือยัง (ใช้ session ที่เรามี) ?>
                    
                    <div class="flex items-center space-x-4">
                        <?php // ตรวจสอบ Role - จำลอง admin@temptation.com เป็น admin ?>
                        <?php 
                            $isAdmin = (isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'admin@temptation.com');
                            $isEmployee = (
                                (isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'employee@temptation.com') ||
                                (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employee')
                            );
                        ?>
                        <?php if ($isAdmin): ?>
                            <a href="index.php?page=admin" class="flex items-center space-x-1 hover:text-blue-200 transition-colors">
                                <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                                <span>จัดการระบบ</span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($isEmployee): ?>
                            <a href="index.php?page=employee" class="flex items-center space-x-1 hover:text-blue-200 transition-colors">
                                <i data-lucide="settings" class="h-4 w-4"></i>
                                <span>จัดการงาน</span>
                            </a>
                        <?php endif; ?>

                        <a href="index.php?page=profile" class="flex items-center space-x-1 hover:text-blue-200 transition-colors">
                            <i data-lucide="user" class="h-4 w-4"></i>
                            <?php // สมมติว่าเก็บชื่อใน $_SESSION['user_name'] หรือใช้ email แทน ?>
                            <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']); ?></span>
                        </a>
                        
                        <?php // แปลง handleLogout() เป็นลิงก์ไป logout.php ?>
                        <a href="logout.php" class="flex items-center space-x-1 hover:text-blue-200 transition-colors">
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                            <span>ออกจากระบบ</span>
                        </a>
                    </div>

                <?php else: // ถ้ายังไม่ล็อกอิน ?>
                    
                    <div class="flex items-center space-x-4">
                        <a href="index.php?page=login" class="hover:text-blue-200 transition-colors">เข้าสู่ระบบ</a>
                        <a 
                            href="index.php?page=register" 
                            class="bg-blue-500 hover:bg-blue-400 px-4 py-2 rounded-lg transition-colors"
                        >
                            สมัครสมาชิก
                        </a>
                    </div>

                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden">
                <button id="mobile-menu-button" class="text-white hover:text-blue-200">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu (ซ่อนไว้โดยค่าเริ่มต้น) -->
        <!-- แปลง {mobileOpen && (...)} โดยใช้ id และ JS ด้านล่าง -->
        <div id="mobile-menu-content" class="md:hidden mt-2 flex flex-col space-y-2 hidden pb-4">
            <a href="index.php?page=home" class="mobile-menu-link hover:text-blue-200 transition-colors">หน้าแรก</a>
            <a href="index.php?page=motorcycles" class="mobile-menu-link hover:text-blue-200 transition-colors">รถเช่า</a>
            
            <?php if (isset($_SESSION['user_email'])): ?>
                <hr class="border-blue-700"/>
                <?php 
                    $isAdmin = (isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'admin@temptation.com');
                    $isEmployee = (
                        (isset($_SESSION['user_email']) && $_SESSION['user_email'] === 'employee@temptation.com') ||
                        (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employee')
                    );
                ?>
                <?php if ($isAdmin): ?>
                    <a href="index.php?page=admin" class="mobile-menu-link flex items-center space-x-1 hover:text-blue-200 transition-colors">
                        <i data-lucide="bar-chart-3" class="h-4 w-4"></i>
                        <span>จัดการระบบ</span>
                    </a>
                <?php endif; ?>
                
                <?php if ($isEmployee): ?>
                    <a href="index.php?page=employee" class="mobile-menu-link flex items-center space-x-1 hover:text-blue-200 transition-colors">
                        <i data-lucide="settings" class="h-4 w-4"></i>
                        <span>จัดการงาน</span>
                    </a>
                <?php endif; ?>

                <a href="index.php?page=profile" class="mobile-menu-link flex items-center space-x-1 hover:text-blue-200 transition-colors">
                    <i data-lucide="user" class="h-4 w-4"></i>
                    <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['user_email']); ?></span>
                </a>
                
                <a href="logout.php" class="mobile-menu-link flex items-center space-x-1 hover:text-blue-200 transition-colors">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    <span>ออกจากระบบ</span>
                </a>
            <?php else: ?>
                <hr class="border-blue-700"/>
                <a href="index.php?page=login" class="mobile-menu-link hover:text-blue-200 transition-colors">เข้าสู่ระบบ</a>
                <a href="index.php?page=register" class="mobile-menu-link bg-blue-500 hover:bg-blue-400 px-4 py-2 rounded-lg transition-colors text-center">
                    สมัครสมาชิก
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- JavaScript สำหรับ Mobile Menu (จำลอง useState) -->
<!-- สคริปต์นี้ควรจะทำงานเพราะ index.php โหลดหน้านี้เข้ามา -->
<script>
    // รอให้ DOM โหลดเสร็จก่อน (ป้องกันสคริปต์รันก่อน HTML)
    document.addEventListener("DOMContentLoaded", function() {
        const menuButton = document.getElementById('mobile-menu-button');
        const menuContent = document.getElementById('mobile-menu-content');
        const menuLinks = document.querySelectorAll('.mobile-menu-link');

        if (menuButton && menuContent) {
            // 1. กดปุ่มเพื่อเปิด/ปิดเมนู (เหมือน setMobileOpen(!mobileOpen))
            menuButton.addEventListener('click', function() {
                menuContent.classList.toggle('hidden');
            });

            // 2. กดลิงก์ในเมนูเพื่อปิดเมนู (เหมือน onClick={() => setMobileOpen(false)})
            menuLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    if (!menuContent.classList.contains('hidden')) {
                        menuContent.classList.add('hidden');
                    }
                });
            });
        }
    });
</script>