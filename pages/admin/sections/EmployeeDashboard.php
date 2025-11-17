<?php
// pages/admin/sections/EmployeesPage.php
// จัดการพนักงาน - ใช้ข้อมูลจริงจาก API

require_once 'api/admin.php';

$error = '';
$success = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // CREATE new employee
        $employeeData = [
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'firstName' => $_POST['first_name'],
            'lastName' => $_POST['last_name'],
            'phone' => $_POST['phone'],
            'position' => $_POST['position'] ?? 'staff'
        ];
        
        $success = AdminService::createEmployee($employeeData);
        if ($success) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'เพิ่มพนักงานใหม่สำเร็จ'];
            header('Location: index.php?page=admin&section=employees');
            exit;
        } else {
            $error = 'ไม่สามารถเพิ่มพนักงานได้';
        }
        
    } elseif ($action === 'update' && isset($_POST['employee_id'])) {
        // UPDATE employee
        $employeeId = $_POST['employee_id'];
        $employeeData = [
            'firstName' => $_POST['first_name'],
            'lastName' => $_POST['last_name'],
            'phone' => $_POST['phone'],
            'position' => $_POST['position'] ?? 'staff'
        ];
        
        // Add password only if provided
        if (!empty($_POST['password'])) {
            $employeeData['password'] = $_POST['password'];
        }
        
        $success = AdminService::updateEmployee($employeeId, $employeeData);
        if ($success) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'อัปเดตข้อมูลพนักงานสำเร็จ'];
            header('Location: index.php?page=admin&section=employees');
            exit;
        } else {
            $error = 'ไม่สามารถอัปเดตข้อมูลพนักงานได้';
        }
    }
}

// Handle DELETE action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $employeeId = $_GET['id'];
    
    // Prevent self-deletion
    $currentUserId = $_SESSION['user']['userId'] ?? $_SESSION['user']['id'] ?? '';
    if ($employeeId === $currentUserId) {
        $error = 'คุณไม่สามารถลบตัวคุณเองได้';
    } else {
        $success = AdminService::deleteEmployee($employeeId);
        if ($success) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'ลบพนักงานสำเร็จ'];
            header('Location: index.php?page=admin&section=employees');
            exit;
        } else {
            $error = 'ไม่สามารถลบพนักงานได้';
        }
    }
}

// ดึงข้อมูลพนักงานทั้งหมดจาก API
$employees = AdminService::getAllEmployees();

// เตรียมข้อมูลสำหรับฟอร์มแก้ไข
$edit_mode = false;
$edit_employee = [
    'employeeId' => '',
    'firstName' => '',
    'lastName' => '',
    'email' => '',
    'phone' => '',
    'position' => 'staff'
];

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $employeeId = $_GET['id'];
    $edit_mode = true;
    
    // Find employee by ID
    foreach ($employees as $emp) {
        if ($emp['employeeId'] === $employeeId || $emp['id'] === $employeeId) {
            $edit_employee = $emp;
            break;
        }
    }
    
    if (!$edit_employee['employeeId'] && !$edit_employee['id']) {
        $error = 'ไม่พบพนักงานที่ต้องการแก้ไข';
        $edit_mode = false;
    }
}

// ฟังก์ชันช่วยในการแสดงตำแหน่ง
function getPositionBadge($position) {
    $positionMap = [
        'admin' => ['text' => 'ผู้ดูแลระบบ', 'class' => 'bg-purple-100 text-purple-800'],
        'manager' => ['text' => 'ผู้จัดการ', 'class' => 'bg-blue-100 text-blue-800'],
        'staff' => ['text' => 'พนักงาน', 'class' => 'bg-green-100 text-green-800'],
        'owner' => ['text' => 'เจ้าของ', 'class' => 'bg-red-100 text-red-800']
    ];
    
    $positionInfo = $positionMap[$position] ?? ['text' => $position, 'class' => 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $positionInfo['class'] . '">' . $positionInfo['text'] . '</span>';
}
?>

<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">จัดการพนักงาน</h1>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <?php echo $_SESSION['flash_message']['message']; ?>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Form: CREATE / UPDATE -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">
            <?php echo $edit_mode ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่'; ?>
        </h2>
        
        <form method="POST" action="index.php?page=admin&section=employees" class="space-y-4">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'add'; ?>">
            
            <?php if ($edit_mode): ?>
                <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($edit_employee['employeeId'] ?? $edit_employee['id']); ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                    <input type="text" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($edit_employee['firstName'] ?? $edit_employee['first_name'] ?? ''); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">นามสกุล</label>
                    <input type="text" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($edit_employee['lastName'] ?? $edit_employee['last_name'] ?? ''); ?>"
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($edit_employee['email'] ?? ''); ?>"
                           <?php if ($edit_mode) echo 'readonly'; ?>
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 <?php if ($edit_mode) echo 'bg-gray-100 cursor-not-allowed'; ?>">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($edit_employee['phone'] ?? ''); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="position" class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง</label>
                    <select id="position" name="position" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="staff" <?php echo ($edit_employee['position'] ?? '') === 'staff' ? 'selected' : ''; ?>>พนักงาน</option>
                        <option value="manager" <?php echo ($edit_employee['position'] ?? '') === 'manager' ? 'selected' : ''; ?>>ผู้จัดการ</option>
                        <option value="admin" <?php echo ($edit_employee['position'] ?? '') === 'admin' ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
                    </select>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" 
                           <?php if (!$edit_mode) echo 'required'; ?>
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" 
                           placeholder="<?php echo $edit_mode ? '(เว้นว่างไว้หากไม่ต้องการเปลี่ยน)' : 'อย่างน้อย 6 ตัวอักษร'; ?>">
                    <?php if (!$edit_mode): ?>
                        <p class="text-xs text-gray-500 mt-1">รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
                    <?php echo $edit_mode ? 'อัปเดตข้อมูล' : 'เพิ่มพนักงาน'; ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="index.php?page=admin&section=employees" class="bg-gray-300 hover:bg-gray-400 text-gray-900 px-6 py-2 rounded-lg font-medium text-center transition">
                        ยกเลิกการแก้ไข
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Table: Employee List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold">รายชื่อพนักงานทั้งหมด (<?php echo count($employees); ?> คน)</h2>
        </div>
        
        <?php if (empty($employees)): ?>
            <div class="p-6 text-center text-gray-500">
                <p>ยังไม่มีข้อมูลพนักงาน</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อ-นามสกุล</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">อีเมล</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เบอร์โทร</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ตำแหน่ง</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($employees as $employee): ?>
                            <?php
                            $employeeId = $employee['employeeId'] ?? $employee['id'];
                            $fullName = ($employee['firstName'] ?? $employee['first_name'] ?? '') . ' ' . ($employee['lastName'] ?? $employee['last_name'] ?? '');
                            $isCurrentUser = $employeeId === ($_SESSION['user']['userId'] ?? $_SESSION['user']['id'] ?? '');
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars(trim($fullName)); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($employee['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($employee['phone'] ?? 'ไม่ระบุ'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo getPositionBadge($employee['position'] ?? 'staff'); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo ($employee['isActive'] ?? true) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ($employee['isActive'] ?? true) ? 'ใช้งาน' : 'ปิดใช้งาน'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="index.php?page=admin&section=employees&action=edit&id=<?php echo urlencode($employeeId); ?>" 
                                       class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i data-lucide="edit-2" class="inline h-4 w-4"></i> แก้ไข
                                    </a>
                                    
                                    <?php if (!$isCurrentUser): ?>
                                        <a href="index.php?page=admin&section=employees&action=delete&id=<?php echo urlencode($employeeId); ?>" 
                                           class="text-red-600 hover:text-red-900"
                                           onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ <?php echo htmlspecialchars(trim($fullName)); ?> ?')">
                                            <i data-lucide="trash-2" class="inline h-4 w-4"></i> ลบ
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 cursor-not-allowed">
                                            <i data-lucide="trash-2" class="inline h-4 w-4"></i> ลบ
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>