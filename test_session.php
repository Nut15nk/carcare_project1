<?php
session_start();

if (isset($_POST['set_customer'])) {
    $_SESSION['user_email'] = 'customer@example.com';
    $_SESSION['user_name'] = 'ลูกค้าทดสอบ';
    $_SESSION['user_role'] = 'customer';
    header("Location: test_session.php");
    exit;
}

if (isset($_POST['set_employee'])) {
    $_SESSION['user_email'] = 'employee@temptation.com';
    $_SESSION['user_name'] = 'พนักงานทดสอบ';
    $_SESSION['user_role'] = 'employee';
    header("Location: test_session.php");
    exit;
}

if (isset($_POST['set_admin'])) {
    $_SESSION['user_email'] = 'admin@temptation.com';
    $_SESSION['user_name'] = 'แอดมินทดสอบ';
    $_SESSION['user_role'] = 'admin';
    header("Location: test_session.php");
    exit;
}

if (isset($_POST['clear'])) {
    session_destroy();
    header("Location: test_session.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Session</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8">
    <h1 class="text-2xl font-bold mb-4">Session Test</h1>
    
    <div class="mb-6 p-4 bg-gray-100 rounded">
        <h2 class="font-bold mb-2">Session Data:</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <form method="post" class="space-y-2">
        <button name="set_customer" class="bg-blue-500 text-white px-4 py-2 rounded">Set Customer Session</button>
        <button name="set_employee" class="bg-green-500 text-white px-4 py-2 rounded">Set Employee Session</button>
        <button name="set_admin" class="bg-purple-500 text-white px-4 py-2 rounded">Set Admin Session</button>
        <button name="clear" class="bg-red-500 text-white px-4 py-2 rounded">Clear Session</button>
    </form>
    
    <div class="mt-6">
        <a href="index.php" class="text-blue-500 underline">ไปหน้าแรก</a> | 
        <a href="login.php" class="text-blue-500 underline">ไปหน้า Login</a>
    </div>
</body>
</html>