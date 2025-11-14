<?php
// (A) ส่วนตรรกะ (Logic) - ต้องอยู่ด้านบนสุด
session_start(); // เริ่ม session เพื่อเก็บสถานะล็อกอิน

$error = '';
$email = ''; // เก็บค่า email ไว้แสดงในฟอร์มหากกรอกผิด

// ตรวจสอบว่ามีการส่งฟอร์มมาหรือไม่ (ใช้วิธี POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ดึงข้อมูลจากฟอร์ม
    $email = $_POST['email'];
    $password = $_POST['password'];

    // --- ส่วนจำลองการตรวจสอบสิทธิ์ (Simulate Authentication) ---
    // ในแอปจริง คุณจะเชื่อมต่อฐานข้อมูล (Database) ที่นี่
    // เพื่อตรวจสอบว่า email และ password (ที่ควร 'hash') ตรงกันหรือไม่
    // แต่ตอนนี้เราจะใช้ข้อมูลทดสอบจากโค้ดเดิม
    
    $demo_accounts = [
        'admin@temptation.com' => 'admin123',
        'employee@temptation.com' => 'emp123',
        'customer@example.com' => 'cust123'
    ];

    if (isset($demo_accounts[$email]) && $demo_accounts[$email] === $password) {
        // !! สำคัญ: ในชีวิตจริง ห้ามเก็บรหัสผ่านเป็นข้อความธรรมดา !!
        // ควรใช้ password_hash() ตอนสมัคร และ password_verify() ตอนล็อกอิน
        
        // ล็อกอินสำเร็จ: เก็บข้อมูลผู้ใช้ใน session
        $_SESSION['user_email'] = $email;
        // (คุณอาจเก็บ role หรือ user_id แทน)

        // ส่งผู้ใช้ไปหน้าหลัก (เหมือน navigate('/'))
        header("Location: /"); // หรือ "index.php"
        exit; // จบการทำงานทันทีหลัง redirect
        
    } else {
        // ล็อกอินไม่สำเร็จ
        $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
    }
}
// (B) ส่วนแสดงผล (View) - เริ่ม HTML
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide"></script>
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
            
            <form class="space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <?php echo $error; ?>
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
                            value="<?php echo htmlspecialchars($email); // แสดง email เดิมหากล็อกอินผิด ?>"
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
                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                        >
                            <i data-lucide="eye" class="h-5 w-5 text-gray-400" id="eye-icon"></i>
                            <i data-lucide="eye-off" class="h-5 w-5 text-gray-400 hidden" id="eye-off-icon"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        เข้าสู่ระบบ
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
                        <a href="register.php" class="font-medium text-blue-600 hover:text-blue-500">
                            สมัครสมาชิก
                        </a>
                    </p>
                </div>
            </div>

            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-2">บัญชีทดสอบ:</h3>
                <div class="text-xs text-gray-600 space-y-1">
                    <p><strong>ผู้ดูแลระบบ:</strong> admin@temptation.com / admin123</p>
                    <p><strong>พนักงาน:</strong> employee@temptation.com / emp123</p>
                    <p><strong>ลูกค้า:</strong> customer@example.com / cust123</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 1. สั่งให้ Lucide วาดไอคอน
        lucide.createIcons();

        // 2. โค้ดสำหรับสลับการแสดงรหัสผ่าน (เหมือนใน React)
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        const eyeOffIcon = document.getElementById('eye-off-icon');

        togglePassword.addEventListener('click', function () {
            // สลับ type ของ input
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // สลับไอคอน
            eyeIcon.classList.toggle('hidden');
            eyeOffIcon.classList.toggle('hidden');
        });
    </script>
</body>
</html>