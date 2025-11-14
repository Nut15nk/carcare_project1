<?php
// pages/admin/sections/EmployeesPage.php
// (ไฟล์นี้จะถูก include โดย AdminRouter.php)

$error = '';
$success = '';

// (1) เริ่มต้นฐานข้อมูลจำลอง (ถ้ายังไม่มี)
if (!isset($_SESSION['employees'])) {
    $_SESSION['employees'] = [
        // เราจะใช้ email เป็น key
        'admin@temptation.com' => [
            'name' => $_SESSION['user_name'] ?? 'Admin', // ดึงจาก session ที่ล็อกอิน
            'email' => $_SESSION['user_email'] ?? 'admin@temptation.com',
            'phone' => '0801234567',
            'role' => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT) // เข้ารหัสผ่าน
        ],
        'employee@temptation.com' => [
            'name' => 'Employee One',
            'email' => 'employee@temptation.com',
            'phone' => '0809876543',
            'role' => 'employee',
            'password_hash' => password_hash('emp123', PASSWORD_DEFAULT)
        ]
    ];
}

// (2) Controller: จัดการ Actions (POST = Create/Update, GET = Delete)

// (2A) CREATE / UPDATE (เมื่อฟอร์มถูกส่ง)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role = $_POST['role'] ?? 'employee';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($phone)) {
        $error = 'กรุณากรอกชื่อ, อีเมล, และเบอร์โทร';
    } else {
        if ($action === 'add') {
            // --- CREATE ---
            if (isset($_SESSION['employees'][$email])) {
                $error = 'อีเมลนี้ถูกใช้แล้ว';
            } elseif (empty($password)) {
                $error = 'กรุณากรอกรหัสผ่านสำหรับพนักงานใหม่';
            } else {
                $_SESSION['employees'][$email] = [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'role' => $role,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT)
                ];
                $success = 'เพิ่มพนักงานใหม่สำเร็จ!';
            }
        } elseif ($action === 'update') {
            // --- UPDATE ---
            if (!isset($_SESSION['employees'][$email])) {
                $error = 'ไม่พบพนักงานที่ต้องการอัปเดต';
            } else {
                $_SESSION['employees'][$email]['name'] = $name;
                $_SESSION['employees'][$email]['phone'] = $phone;
                $_SESSION['employees'][$email]['role'] = $role;
                
                // อัปเดตรหัสผ่าน (ถ้ากรอกใหม่)
                if (!empty($password)) {
                    $_SESSION['employees'][$email]['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                }
                $success = 'อัปเดตข้อมูลพนักงานสำเร็จ!';
            }
        }
    }
}

// (2B) DELETE (เมื่อคลิกลิงก์)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $email = $_GET['email'] ?? '';
    if (isset($_SESSION['employees'][$email])) {
        // ป้องกันการลบตัวเอง (Admin ที่กำลังล็อกอิน)
        if ($email === $_SESSION['user_email']) {
            $error = 'คุณไม่สามารถลบตัวคุณเองได้';
        } else {
            unset($_SESSION['employees'][$email]);
            $success = 'ลบพนักงานสำเร็จ!';
        }
    } else {
        $error = 'ไม่พบพนักงานที่ต้องการลบ';
    }
}

// (3) Model: ดึงข้อมูลเพื่อแสดงผล (Read)

// (3A) ดึงข้อมูลทั้งหมด
$employees = $_SESSION['employees'];

// (3B) เตรียมฟอร์มสำหรับ Edit (ถ้ามี)
$edit_mode = false;
$edit_employee = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'role' => 'employee'
];

if (isset($_GET['action']) && $_GET['action'] === 'edit') {
    $email = $_GET['email'] ?? '';
    if (isset($employees[$email])) {
        $edit_mode = true;
        $edit_employee = $employees[$email];
    } else {
        $error = 'ไม่พบพนักงานที่ต้องการแก้ไข';
    }
}

?>

<!-- (4) View: เริ่ม HTML -->
<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">จัดการพนักงาน</h1>

    <!-- (5) Flash Messages (แสดง Error หรือ Success) -->
    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- (6) Form: CREATE / UPDATE -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">
            <?php echo $edit_mode ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่'; ?>
        </h2>
        
        <form method="POST" action="index.php?page=admin&section=employees" class="space-y-4">
            
            <!-- ซ่อน action และ email (สำหรับ update) -->
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'add'; ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($edit_employee['email']); ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($edit_employee['name']); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($edit_employee['email']); ?>"
                           <?php if ($edit_mode) echo 'readonly'; // ห้ามแก้ไข email (key) ?>
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg <?php if ($edit_mode) echo 'bg-gray-100 cursor-not-allowed'; ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($edit_employee['phone']); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">บทบาท</label>
                    <select id="role" name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="employee" <?php if ($edit_employee['role'] === 'employee') echo 'selected'; ?>>Employee</option>
                        <option value="admin" <?php if ($edit_employee['role'] === 'admin') echo 'selected'; ?>>Admin</option>
                    </select>
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                <input type="password" id="password" name="password" 
                       <?php if (!$edit_mode) echo 'required'; // บังคับกรอกถ้าเป็น "Add" ?>
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg" 
                       placeholder="<?php echo $edit_mode ? '(เว้นว่างไว้หากไม่ต้องการเปลี่ยน)' : 'อย่างน้อย 6 ตัวอักษร'; ?>">
            </div>

            <div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <?php echo $edit_mode ? 'อัปเดตข้อมูล' : 'เพิ่มพนักงาน'; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="index.php?page=admin&section=employees" class="ml-2 text-gray-600 hover:text-gray-800">
                        ยกเลิกการแก้ไข
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- (7) Table: READ -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <h2 class="text-xl font-semibold p-6">รายชื่อพนักงานทั้งหมด (<?php echo count($employees); ?> คน)</h2>
        <table class="w-full border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border-b px-4 py-2 text-left">ชื่อ-นามสกุล</th>
                    <th class="border-b px-4 py-2 text-left">อีเมล</th>
                    <th class="border-b px-4 py-2 text-left">เบอร์โทร</th>
                    <th class="border-b px-4 py-2 text-left">บทบาท</th>
                    <th class="border-b px-4 py-2 text-left">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $email => $emp): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="border-b px-4 py-2"><?php echo htmlspecialchars($emp['name']); ?></td>
                        <td class="border-b px-4 py-2"><?php echo htmlspecialchars($emp['email']); ?></td>
                        <td class="border-b px-4 py-2"><?php echo htmlspecialchars($emp['phone']); ?></td>
                        <td class="border-b px-4 py-2">
                            <?php if ($emp['role'] === 'admin'): ?>
                                <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">Admin</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">Employee</span>
                            <?php endif; ?>
                        </td>
                        <td class="border-b px-4 py-2">
                            <a href="index.php?page=admin&section=employees&action=edit&email=<?php echo urlencode($email); ?>" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium mr-2">
                                <i data-lucide="edit-2" class="inline h-4 w-4"></i> แก้ไข
                            </a>
                            
                            <?php // ห้ามแสดงปุ่มลบ ถ้าเป็นตัวเอง ?>
                            <?php if ($email !== $_SESSION['user_email']): ?>
                                <a href="index.php?page=admin&section=employees&action=delete&email=<?php echo urlencode($email); ?>" 
                                   class="text-red-600 hover:text-red-800 text-sm font-medium"
                                   onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ <?php echo htmlspecialchars($emp['name']); ?> ?')">
                                    <i data-lucide="trash-2" class="inline h-4 w-4"></i> ลบ
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>