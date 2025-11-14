<?php
// pages/RegisterPage.php
// ไม่ต้อง session_start() เพราะ index.php เรียกไปแล้ว

// ถ้าล็อกอินอยู่แล้ว, ให้ redirect ไปหน้าโปรไฟล์
if (isset($_SESSION['user_email'])) {
    header("Location: index.php?page=profile");
    exit;
}

$error = '';
// (1) กำหนดค่าเริ่มต้นสำหรับตัวแปร (สำหรับ GET request)
$name = '';
$email = '';
$phone = '';
$lineId = '';
$password = '';
$confirmPassword = '';

// (2) ตรวจสอบว่าเป็นการส่งฟอร์ม (POST) หรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // (3) ดึงข้อมูลจากฟอร์ม (แทน formData)
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $lineId = $_POST['lineId'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // (4) ตรวจสอบ Validation (เหมือน handleSubmit)
    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = 'กรุณากรอกข้อมูลที่จำเป็น (*) ให้ครบถ้วน';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 6) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    }
    // (หมายเหตุ: ในแอปจริง ควรตรวจสอบด้วยว่า email นี้มีในระบบแล้วหรือยัง)

    // (5) ถ้าไม่มีข้อผิดพลาด, ดำเนินการสมัคร
    if (empty($error)) {
        
        // --- จำลองการสมัครสมาชิก (Simulate AuthContext.register) ---
        // ในชีวิตจริง:
        // 1. $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        // 2. INSERT INTO users (name, email, phone, lineId, password) VALUES (...)
        // 3. ตรวจสอบว่า INSERT สำเร็จหรือไม่
        
        // สมมติว่าสำเร็จ
        $success = true; // จำลอง

        if ($success) {
            // สมัครเสร็จแล้ว, ล็อกอินให้เลย
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $name; // เก็บชื่อไว้ใน session ด้วย
            $_SESSION['user_role'] = 'customer'; // กำหนด role พื้นฐาน
            
            // ส่งไปหน้าหลัก (เหมือน navigate('/'))
            header("Location: index.php?page=home");
            exit;
        } else {
            $error = 'เกิดข้อผิดพลาดในการสมัครสมาชิก';
        }
    }
    // ถ้ามี $error, สคริปต์จะทำงานต่อไปยังส่วน HTML และแสดง $error
}
?>

<!-- (6) เริ่มส่วน HTML (View) -->
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="flex justify-center">
                <div class="bg-blue-600 p-3 rounded-full">
                    <!-- แปลง <Bike> เป็น <i> -->
                    <i data-lucide="bike" class="h-8 w-8 text-white"></i>
                </div>
            </div>
            <h2 class="mt-6 text-3xl font-bold text-gray-900">สมัครสมาชิก</h2>
            <p class="mt-2 text-sm text-gray-600">
                สร้างบัญชีเพื่อเริ่มจองรถจักรยานยนต์
            </p>
        </div>

        <div class="bg-white py-8 px-6 shadow-lg rounded-lg">
            
            <!-- (7) ตั้งค่าฟอร์มให้ส่งแบบ POST ไปที่หน้านี้ -->
            <form class="space-y-6" method="POST" action="index.php?page=register">
                
                <!-- (8) แสดง Error (ถ้ามี) -->
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        ชื่อ-นามสกุล *
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="user" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            required
                            value="<?php echo htmlspecialchars($name); // (9) จำค่าเดิมไว้ ?>"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกชื่อ-นามสกุลของคุณ"
                        />
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        อีเมล *
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autoComplete="email"
                            required
                            value="<?php echo htmlspecialchars($email); ?>"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกอีเมลของคุณ"
                        />
                    </div>
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        เบอร์โทรศัพท์ *
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="phone" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            required
                            value="<?php echo htmlspecialchars($phone); ?>"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกเบอร์โทรศัพท์ของคุณ"
                        />
                    </div>
                </div>

                <div>
                    <label for="lineId" class="block text-sm font-medium text-gray-700 mb-2">
                        Line ID (ไม่บังคับ)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="message-circle" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input
                            id="lineId"
                            name="lineId"
                            type="text"
                            value="<?php echo htmlspecialchars($lineId); ?>"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอก Line ID ของคุณ"
                        />
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        รหัสผ่าน *
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autoComplete="new-password"
                            required
                            class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกรหัสผ่าน (อย่างน้อย 6 ตัวอักษร)"
                        />
                        <button
                            type="button"
                            id="toggle-password-btn"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                        >
                            <i data-lucide="eye" id="eye-icon-1" class="h-5 w-5 text-gray-400"></i>
                            <i data-lucide="eye-off" id="eye-off-icon-1" class="h-5 w-5 text-gray-400 hidden"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="confirmPassword" class="block text-sm font-medium text-gray-700 mb-2">
                        ยืนยันรหัสผ่าน *
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                        </div>
                        <input
                            id="confirmPassword"
                            name="confirmPassword"
                            type="password"
                            autoComplete="new-password"
                            required
                            class="block w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="กรอกรหัสผ่านอีกครั้ง"
                        />
                        <button
                            type="button"
                            id="toggle-confirm-password-btn"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                        >
                            <i data-lucide="eye" id="eye-icon-2" class="h-5 w-5 text-gray-400"></i>
                            <i data-lucide="eye-off" id="eye-off-icon-2" class="h-5 w-5 text-gray-400 hidden"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <!-- (10) ลบตรรกะ isLoading -->
                    <button
                        type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        สมัครสมาชิก
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
                        มีบัญชีอยู่แล้ว?
                        <!-- (11) แปลง <Link> เป็น <a> -->
                        <a href="index.php?page=login" class="font-medium text-blue-600 hover:text-blue-500">
                            เข้าสู่ระบบ
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- (12) JavaScript สำหรับ Toggle Password (เหมือน login.php) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Toggle 1: Password
    const toggleBtn1 = document.getElementById('toggle-password-btn');
    const passwordInput1 = document.getElementById('password');
    const eyeIcon1 = document.getElementById('eye-icon-1');
    const eyeOffIcon1 = document.getElementById('eye-off-icon-1');

    if (toggleBtn1 && passwordInput1 && eyeIcon1 && eyeOffIcon1) {
        toggleBtn1.addEventListener('click', function() {
            const type = passwordInput1.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput1.setAttribute('type', type);
            eyeIcon1.classList.toggle('hidden');
            eyeOffIcon1.classList.toggle('hidden');
        });
    }

    // Toggle 2: Confirm Password
    const toggleBtn2 = document.getElementById('toggle-confirm-password-btn');
    const passwordInput2 = document.getElementById('confirmPassword');
    const eyeIcon2 = document.getElementById('eye-icon-2');
    const eyeOffIcon2 = document.getElementById('eye-off-icon-2');

    if (toggleBtn2 && passwordInput2 && eyeIcon2 && eyeOffIcon2) {
        toggleBtn2.addEventListener('click', function() {
            const type = passwordInput2.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput2.setAttribute('type', type);
            eyeIcon2.classList.toggle('hidden');
            eyeOffIcon2.classList.toggle('hidden');
        });
    }
});
</script>