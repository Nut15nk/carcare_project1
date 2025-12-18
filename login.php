<?php
session_start();
$error = '';
$email = $_POST['email'] ?? "";

// โหลด Service ใหม่ (ไม่ใช่ /api/ แล้ว)
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/service/AuthService.php';

// Logic เมื่อ submit form
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST['password'];

    try {
        // Auto Detect Role
        $user = AuthService::login($email, $password);

        if ($user) {

            // เก็บข้อมูลลง session
            $_SESSION['user'] = $user;
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_id'] = $user['id'];

            // redirect ตาม role
            switch ($user["role"]) {
                case "employee":
                    header("Location: index.php?page=employee");
                    exit;

                case "owner":
                    header("Location: index.php?page=admin");
                    exit;

                case "customer":
                default:
                    header("Location: index.php");
                    exit;
            }
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="min-h-screen bg-blue-50 flex items-center justify-center">

<div class="max-w-md w-full bg-white p-8 shadow-lg rounded-xl">

    <h2 class="text-2xl font-bold text-center mb-6">เข้าสู่ระบบ</h2>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">

        <div>
            <label class="block font-medium mb-1">อีเมล</label>
            <input
                type="email"
                name="email"
                value="<?php echo htmlspecialchars($email); ?>"
                required
                class="w-full border px-3 py-2 rounded-lg"
            >
        </div>

        <div>
            <label class="block font-medium mb-1">รหัสผ่าน</label>
            <input
                type="password"
                name="password"
                required
                class="w-full border px-3 py-2 rounded-lg"
            >
        </div>

        <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700"
        >
            เข้าสู่ระบบ
        </button>
    </form>

    <p class="text-center text-sm mt-4">
        ยังไม่มีบัญชี?
        <a href="register.php" class="text-blue-600 font-medium">สมัครสมาชิก</a>
    </p>

</div>

<script>
    lucide.createIcons();
</script>

</body>
</html>
