<?php
// (A) ส่วนตรรกะ (Logic) - ต้องอยู่ด้านบนสุด
session_start();

$error = '';
$email = '';

// ตรวจสอบว่ามีการส่งฟอร์มมาหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ดึงข้อมูลจากฟอร์ม
    $email = $_POST['email'];
    $password = $_POST['password'];

    // โหลดไฟล์ API
    $apiPaths = [
        'api/config.php',
        '../api/config.php',
        '../../api/config.php',
    ];
    
    $configLoaded = false;
    foreach ($apiPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $configLoaded = true;
            break;
        }
    }
    
    if (!$configLoaded) {
        die('ไม่พบไฟล์ config.php กรุณาตรวจสอบโครงสร้างโฟลเดอร์');
    }
    
    // โหลดไฟล์ auth.php
    $authPaths = [
        'api/auth.php',
        '../api/auth.php', 
        '../../api/auth.php',
    ];
    
    $authLoaded = false;
    foreach ($authPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $authLoaded = true;
            break;
        }
    }
    
    if (!$authLoaded) {
        die('ไม่พบไฟล์ auth.php กรุณาตรวจสอบโครงสร้างโฟลเดอร์');
    }

    // ใช้ API จริง
    try {
        $user = AuthService::login($email, $password);
        
        if ($user) {
            // ล็อกอินสำเร็จ: เก็บข้อมูลผู้ใช้ใน session ใหม่ (ใช้ structure หลัก)
            $_SESSION['user'] = $user; // structure หลัก
            $_SESSION['user_email'] = $user['email'] ?? $email; // สำหรับ backward compatibility
            $_SESSION['user_name'] = $user['firstName'] ?? $user['name'] ?? 'ผู้ใช้'; // สำหรับ backward compatibility
            $_SESSION['user_role'] = $user['role'] ?? 'customer'; // สำหรับ backward compatibility
            $_SESSION['user_id'] = $user['userId'] ?? $user['id'] ?? null; // สำหรับ backward compatibility

            // ตรวจสอบว่า redirect_url มีอยู่ใน session หรือไม่
            $redirectUrl = $_SESSION['redirect_url'] ?? 'index.php';
            if ($redirectUrl) {
                unset($_SESSION['redirect_url']);
                header("Location: $redirectUrl");
                exit;
            }

            // ส่งผู้ใช้ไปหน้าหลักตาม role
            $userRole = $user['role'] ?? 'customer';
            switch (strtoupper($userRole)) {
                case 'EMPLOYEE':
                case 'STAFF':
                    header("Location: index.php?page=employee");
                    break;
                case 'OWNER':
                case 'ADMIN':
                    header("Location: index.php?page=admin");
                    break;
                case 'CUSTOMER':
                default:
                    header("Location: index.php");
            }
            exit;
            
        } else {
            // ล็อกอินไม่สำเร็จ
            $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
        }
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $e->getMessage();
    }
}

// ตรวจสอบ flash message
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    unset($_SESSION['flash_message']);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Motorcycle Rental</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .loading {
            display: none;
        }
        .loading.active {
            display: inline-block;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">

    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="flex justify-center">
                <div class="bg-blue-600 p-3 rounded-full">
                    <i data-lucide="bike" class="h-8 w-8 text-white"></i>
                </div>
            </div>
            <h2 class="mt-6 text-3xl font-bold text-gray-900">เข้าสู่ระบบ</h2>
            <p class="mt-2 text-sm text-gray-600">
                เข้าสู่ระบบเพื่อจองรถจักรยานยนต์
            </p>
        </div>

        <div class="bg-white py-8 px-6 shadow-lg rounded-lg">
            
            <form class="space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="loginForm">
                
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center">
                        <i data-lucide="alert-circle" class="h-5 w-5 mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- แสดงข้อความจาก session ถ้ามี -->
                <?php if ($flash_message): ?>
                    <div class="<?php echo $flash_message['type'] === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'; ?> px-4 py-3 rounded-lg flex items-center">
                        <i data-lucide="<?php echo $flash_message['type'] === 'error' ? 'alert-circle' : 'check-circle'; ?>" class="h-5 w-5 mr-2"></i>
                        <?php echo $flash_message['message']; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        อีเมล
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            value="<?php echo htmlspecialchars($email); ?>"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกอีเมลของคุณ"
                        />
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        รหัสผ่าน
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกรหัสผ่านของคุณ"
                        />
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                        >
                            <i data-lucide="eye" class="h-5 w-5" id="eye-icon"></i>
                            <i data-lucide="eye-off" class="h-5 w-5 hidden" id="eye-off-icon"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        id="submitBtn"
                        class="w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        <span id="submitText">เข้าสู่ระบบ</span>
                        <div id="loadingSpinner" class="loading ml-2">
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i>
                        </div>
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">หรือ</span>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        ยังไม่มีบัญชี?
                        <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                            สมัครสมาชิก
                        </a>
                    </p>
                </div>
            </div>

          
        </div>
    </div>

    <script>
        // 1. สั่งให้ Lucide วาดไอคอน
        lucide.createIcons();

        // 2. โค้ดสำหรับสลับการแสดงรหัสผ่าน
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeOffIcon = document.getElementById('eye-off-icon');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                eyeIcon.classList.toggle('hidden');
                eyeOffIcon.classList.toggle('hidden');
            });
        }

        // 3. Loading state เมื่อกด submit
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const loadingSpinner = document.getElementById('loadingSpinner');

        if (loginForm) {
            loginForm.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitText.textContent = 'กำลังเข้าสู่ระบบ...';
                loadingSpinner.classList.add('active');
            });
        }

        // 4. Auto-focus ที่ input email
        document.getElementById('email')?.focus();
    </script>
</body>
</html>