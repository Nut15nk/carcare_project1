<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'owner') {
        http_response_code(403);
        exit('Access denied');
    }

    require_once __DIR__ . '/../../../service/Admin/AdminService.php';
    use Service\Admin\AdminService;

    $error = '';

    /* ================= POST ================= */
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if ($_POST['action'] === 'add') {
                AdminService::createUser([
                    'email'     => $_POST['email'],
                    'password'  => $_POST['password'],
                    'firstName' => $_POST['first_name'],
                    'lastName'  => $_POST['last_name'],
                    'phone'     => $_POST['phone'],
                    'position'  => $_POST['position'],
                ]);
            }

            if ($_POST['action'] === 'update') {
                AdminService::updateUser(
                    $_POST['id'],
                    $_POST['old_position'], // 👈 เดิม
                    $_POST['new_position'], // 👈 ใหม่
                    [
                        'firstName' => $_POST['first_name'],
                        'lastName'  => $_POST['last_name'],
                        'phone'     => $_POST['phone'],
                        'password'  => $_POST['password'] ?? null,
                    ]
                );
            }

            header('Location: index.php?page=admin&section=employees');
            exit;

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    /* ================= DELETE ================= */
    if (isset($_GET['action'], $_GET['id'], $_GET['position']) && $_GET['action'] === 'delete') {
        try {
            AdminService::deleteUser($_GET['id'], $_GET['position']);
            header('Location: index.php?page=admin&section=employees');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    $users = AdminService::getAllStaffWithOwners();

    /* ================= EDIT MODE ================= */
    $edit_mode = false;
    $edit_user = null;

    if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
        foreach ($users as $u) {
            if ((string) $u['id'] === (string) $_GET['id']) {
                $edit_user = $u;
                $edit_mode = true;
                break;
            }
        }

        if (! $edit_mode) {
            $error = 'ไม่พบผู้ใช้ที่ต้องการแก้ไข';
        }
    }

    /* ================= HELPER ================= */
    function getPositionBadge(string $position): string
    {
        return $position === 'owner'
            ? '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">เจ้าของร้าน</span>'
            : '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">พนักงาน</span>';
    }
?>


<div class="space-y-6">
    <h1 class="text-2xl font-bold text-gray-900">จัดการพนักงาน</h1>

    <?php if (! empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">
            <?php echo $edit_mode ? 'แก้ไขข้อมูลพนักงาน' : 'เพิ่มพนักงานใหม่'; ?>
        </h2>

        <form method="POST" action="index.php?page=admin&section=employees" class="space-y-4">
            <input type="hidden" name="action" value="<?php echo $edit_mode ? 'update' : 'add'; ?>">

            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_user['id']); ?>">
                <input type="hidden" name="old_position" value="<?php echo htmlspecialchars($edit_user['position']); ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                    <input type="text" name="first_name"
                           value="<?php echo htmlspecialchars($edit_user['firstName'] ?? ''); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">นามสกุล</label>
                    <input type="text" name="last_name"
                           value="<?php echo htmlspecialchars($edit_user['lastName'] ?? ''); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                    <input
                        type="email"
                        name="email"
                        value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>"
                        <?php if ($edit_mode): ?>readonly<?php endif; ?>
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg                                                                                  <?php echo $edit_mode ? 'bg-gray-100 cursor-not-allowed' : ''; ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                    <input type="tel" name="phone"
                           value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>

            <!-- ✅ ตำแหน่ง (เพิ่มเฉพาะตอน edit แต่ UI เดิม) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง</label>
                <select name="<?php echo $edit_mode ? 'new_position' : 'position'; ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="staff"                                          <?php echo($edit_user['position'] ?? '') === 'staff' ? 'selected' : ''; ?>>
                        พนักงาน
                    </option>
                    <option value="owner"                                          <?php echo($edit_user['position'] ?? '') === 'owner' ? 'selected' : ''; ?>>
                        เจ้าของร้าน
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                <input type="password" name="password"
                       placeholder="<?php echo $edit_mode ? '(เว้นว่างถ้าไม่เปลี่ยน)' : 'อย่างน้อย 6 ตัวอักษร'; ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                    <?php echo $edit_mode ? 'อัปเดตข้อมูล' : 'เพิ่มพนักงาน'; ?>
                </button>

                <?php if ($edit_mode): ?>
                    <a href="index.php?page=admin&section=employees"
                       class="bg-gray-300 hover:bg-gray-400 px-6 py-2 rounded-lg">
                        ยกเลิก
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>


    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold">รายชื่อพนักงานทั้งหมด (<?php echo count($users); ?> คน)</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">ชื่อ-นามสกุล</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">อีเมล</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">เบอร์โทร</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">ตำแหน่ง</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($u['firstName'] . ' ' . $u['lastName']); ?>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($u['phone']); ?></td>
                            <td class="px-6 py-4"><?php echo getPositionBadge($u['position']); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <a class="text-blue-600 mr-3"
                                   href="index.php?page=admin&section=employees&action=edit&id=<?php echo urlencode($u['id']); ?>">
                                    แก้ไข
                                </a>
                                <?php if ($u['id'] !== ($_SESSION['user']['id'] ?? '')): ?>
                                    <a class="text-red-600"
                                       href="index.php?page=admin&section=employees&action=delete&id=<?php echo urlencode($u['id']); ?>&position=<?php echo $u['position']; ?>"
                                       onclick="return confirm('ยืนยันการลบผู้ใช้นี้?')">
                                        ลบ
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-400">ลบ</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
