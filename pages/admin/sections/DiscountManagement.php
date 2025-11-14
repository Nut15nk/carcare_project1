<?php
/**
 * จัดการโค้ดส่วนลด (Discount Code Management)
 * หน้าสำหรับ Admin ในการ สร้าง, แก้ไข, และจัดการโค้ดส่วนลด
 */

// (ไฟล์นี้ถูก include โดย AdminRouter.php ซึ่งเริ่ม session และตรวจสอบ role 'admin' แล้ว)

// เริ่มต้น session สำหรับส่วนลด (ถ้ายังไม่มี)
if (!isset($_SESSION['discounts'])) {
    $_SESSION['discounts'] = [];
}

// (1) Controller: จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // (หมายเหตุ: เราใช้ $_GET['action'] เพราะฟอร์มส่ง action มาใน URL)
    $action = $_GET['action'] ?? null;
    
    if ($action === 'create') {
        // --- (A) สร้างส่วนลดใหม่ ---
        $discountCode = strtoupper(substr(str_replace('-', '', $_POST['code']), 0, 20));
        $discountType = $_POST['type'];
        $discountValue = floatval($_POST['value']);
        $minDays = intval($_POST['min_days']) ?? 1;
        $maxDiscount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $usageLimit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        
        // ตรวจสอบ
        if (empty($discountCode) || !in_array($discountType, ['percentage', 'fixed'])) {
            $error = 'โค้ดส่วนลด หรือ ประเภทไม่ถูกต้อง';
        } elseif ($discountValue <= 0) {
            $error = 'มูลค่าส่วนลดต้องมากกว่า 0';
        } elseif (strtotime($startDate) >= strtotime($endDate)) {
            $error = 'วันที่เริ่มต้นต้องอยู่ก่อนวันที่สิ้นสุด';
        } else {
            // ตรวจสอบว่าโค้ดซ้ำหรือไม่
            $exists = false;
            foreach ($_SESSION['discounts'] as $d) {
                if ($d['code'] === $discountCode) {
                    $exists = true;
                    break;
                }
            }
            
            if ($exists) {
                $error = 'โค้ดส่วนลดนี้มีอยู่แล้ว';
            } else {
                $discount = [
                    'id' => 'DISC' . time() . random_int(100, 999),
                    'code' => $discountCode,
                    'type' => $discountType,
                    'value' => $discountValue,
                    'min_days' => $minDays,
                    'max_discount' => $maxDiscount,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'usage_limit' => $usageLimit,
                    'used_count' => 0,
                    'is_active' => true,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $_SESSION['discounts'][] = $discount;
                $success = 'สร้างโค้ดส่วนลดสำเร็จ';
            }
        }
    } elseif ($action === 'edit' && isset($_POST['discount_id'])) {
        // --- (B) แก้ไขส่วนลด ---
        $discountId = $_POST['discount_id'];
        $discountType = $_POST['type'];
        $discountValue = floatval($_POST['value']);
        $minDays = intval($_POST['min_days']) ?? 1;
        $maxDiscount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $usageLimit = !empty($_POST['usage_limit']) ? intval($_POST['usage_limit']) : null;
        
        if (strtotime($startDate) >= strtotime($endDate)) {
            $error = 'วันที่เริ่มต้นต้องอยู่ก่อนวันที่สิ้นสุด';
        } else {
            foreach ($_SESSION['discounts'] as &$d) {
                if ($d['id'] === $discountId) {
                    $d['type'] = $discountType;
                    $d['value'] = $discountValue;
                    $d['min_days'] = $minDays;
                    $d['max_discount'] = $maxDiscount;
                    $d['start_date'] = $startDate;
                    $d['end_date'] = $endDate;
                    $d['usage_limit'] = $usageLimit;
                    $success = 'อัปเดตส่วนลดสำเร็จ';
                    break;
                }
            }
        }
    } elseif ($action === 'delete' && isset($_POST['discount_id'])) {
        // --- (C) ลบส่วนลด ---
        $discountId = $_POST['discount_id'];
        $_SESSION['discounts'] = array_filter($_SESSION['discounts'], function($d) use ($discountId) {
            return $d['id'] !== $discountId;
        });
        $success = 'ลบส่วนลดสำเร็จ';
    } elseif ($action === 'toggle' && isset($_POST['discount_id'])) {
        // --- (D) เปิด/ปิดสถานะ (ถ้าจำเป็น) ---
        $discountId = $_POST['discount_id'];
        foreach ($_SESSION['discounts'] as &$d) {
            if ($d['id'] === $discountId) {
                $d['is_active'] = !$d['is_active'];
                $success = $d['is_active'] ? 'เปิดใช้งานส่วนลด' : 'ปิดใช้งานส่วนลด';
                break;
            }
        }
    }
}

// (2) Model: ดึงข้อมูลเพื่อแสดงผล

// (2A) ดึงข้อมูลสำหรับฟอร์มแก้ไข (ถ้ามี)
$editDiscount = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = $_GET['id'];
    foreach ($_SESSION['discounts'] as $d) {
        if ($d['id'] === $editId) {
            $editDiscount = $d;
            break;
        }
    }
}

// (2B) กรองข้อมูลสำหรับ Stats
$activeDiscounts = array_filter($_SESSION['discounts'], function($d) {
    return strtotime($d['start_date']) <= time() && time() <= strtotime($d['end_date']) && $d['is_active'];
});
$expiredDiscounts = array_filter($_SESSION['discounts'], function($d) {
    return time() > strtotime($d['end_date']);
});

// (2C) เรียงข้อมูล (ล่าสุดอยู่บน)
$allDiscounts = $_SESSION['discounts'];
usort($allDiscounts, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
?>

<!-- (3) View: เริ่ม HTML -->
<div class="space-y-6">
    
    <!-- Header -->
    <div class="mb-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">จัดการโค้ดส่วนลด</h1>
        <p class="text-gray-600">สร้างและจัดการโค้ดส่วนลดสำหรับการจอง</p>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i data-lucide="check-circle" class="h-5 w-5"></i>
            <span><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i data-lucide="alert-circle" class="h-5 w-5"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Form Section -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-8">
                <h2 class="text-xl font-bold mb-4">
                    <?php echo $editDiscount ? 'แก้ไขส่วนลด' : 'สร้างส่วนลดใหม่'; ?>
                </h2>

                <form method="POST" action="index.php?page=admin&section=discounts&action=<?php echo $editDiscount ? 'edit' : 'create'; ?>">
                    
                    <?php if ($editDiscount): ?>
                        <input type="hidden" name="discount_id" value="<?php echo $editDiscount['id']; ?>">
                    <?php endif; ?>

                    <!-- Code -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">โค้ดส่วนลด</label>
                        <input type="text" name="code" 
                            <?php echo $editDiscount ? 'readonly' : ''; // ห้ามแก้ไขโค้ด (key) ?>
                            value="<?php echo htmlspecialchars($editDiscount['code'] ?? ''); ?>"
                            placeholder="เช่น SUMMER50" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo $editDiscount ? 'bg-gray-100 cursor-not-allowed' : ''; ?>"
                            required>
                        <p class="text-xs text-gray-500 mt-1">ตัวอักษรและตัวเลขเท่านั้น, สูงสุด 20 ตัว</p>
                    </div>

                    <!-- Type -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภทส่วนลด</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            <option value="">เลือกประเภท...</option>
                            <option value="percentage" <?php echo ($editDiscount['type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>เปอร์เซ็นต์ (%)</option>
                            <option value="fixed" <?php echo ($editDiscount['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>จำนวนคงที่ (฿)</option>
                        </select>
                    </div>

                    <!-- Value -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">มูลค่าส่วนลด</label>
                        <input type="number" name="value" step="0.01" min="0" 
                            value="<?php echo htmlspecialchars($editDiscount['value'] ?? ''); ?>"
                            placeholder="เช่น 50 หรือ 500" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Min Days -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">จำนวนวันเช่าขั้นต่ำ</label>
                        <input type="number" name="min_days" min="1" 
                            value="<?php echo htmlspecialchars($editDiscount['min_days'] ?? '1'); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Max Discount -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ส่วนลดสูงสุด (฿)</label>
                        <input type="number" name="max_discount" step="0.01" min="0" 
                            value="<?php echo htmlspecialchars($editDiscount['max_discount'] ?? ''); ?>"
                            placeholder="ไม่บังคับ - เว้นว่างไว้หากไม่จำกัด"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Start Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">วันที่เริ่มต้น</label>
                        <input type="date" name="start_date" 
                            value="<?php echo htmlspecialchars($editDiscount['start_date'] ?? date('Y-m-d')); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- End Date -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">วันที่สิ้นสุด</label>
                        <input type="date" name="end_date" 
                            value="<?php echo htmlspecialchars($editDiscount['end_date'] ?? date('Y-m-d', strtotime('+30 days'))); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Usage Limit -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">จำกัดการใช้งาน</label>
                        <input type="number" name="usage_limit" min="1" 
                            value="<?php echo htmlspecialchars($editDiscount['usage_limit'] ?? ''); ?>"
                            placeholder="ไม่บังคับ - เว้นว่างไว้หากไม่จำกัด"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            <?php echo $editDiscount ? 'อัปเดต' : 'สร้าง'; ?>
                        </button>
                        <?php if ($editDiscount): ?>
                            <!-- (สำคัญ) แก้ไขลิงก์ Cancel -->
                            <a href="index.php?page=admin&section=discounts" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-900 font-semibold py-2 px-4 rounded-lg text-center transition">
                                ยกเลิก
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- List Section -->
        <div class="lg:col-span-2">
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">โค้ดทั้งหมด</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo count($allDiscounts); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">ใช้งานได้</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo count($activeDiscounts); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">หมดอายุ</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo count($expiredDiscounts); ?></p>
                </div>
            </div>

            <!-- Discounts Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">โค้ด</th>
                                <th class="px-4 py-3 text-left font-semibold">ส่วนลด</th>
                                <th class="px-4 py-3 text-left font-semibold">ช่วงเวลาใช้งาน</th>
                                <th class="px-4 py-3 text-left font-semibold">การใช้งาน</th>
                                <th class="px-4 py-3 text-left font-semibold">สถานะ</th>
                                <th class="px-4 py-3 text-left font-semibold">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($allDiscounts as $discount): ?>
                                <?php
                                $isActive = strtotime($discount['start_date']) <= time() && time() <= strtotime($discount['end_date']) && $discount['is_active'];
                                $isExpired = time() > strtotime($discount['end_date']);
                                $isUpcoming = strtotime($discount['start_date']) > time();
                                $usagePercent = $discount['usage_limit'] ? ($discount['used_count'] / $discount['usage_limit'] * 100) : 0;
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono font-bold text-blue-600">
                                        <?php echo htmlspecialchars($discount['code']); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php 
                                        if ($discount['type'] === 'percentage') {
                                            echo $discount['value'] . '%';
                                        } else {
                                            echo '฿' . number_format($discount['value'], 0);
                                        }
                                        if ($discount['max_discount']) {
                                            echo ' (สูงสุด ฿' . number_format($discount['max_discount'], 0) . ')';
                                        }
                                        ?>
                                    </td>
                                    <td class="px-4 py-3 text-xs">
                                        <div><?php echo date('d/m/Y', strtotime($discount['start_date'])); ?></div>
                                        <div class="text-gray-500">ถึง</div>
                                        <div><?php echo date('d/m/Y', strtotime($discount['end_date'])); ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm"><?php echo $discount['used_count']; ?>/<?php echo $discount['usage_limit'] ?? 'ไม่จำกัด'; ?></div>
                                        <?php if ($discount['usage_limit']): ?>
                                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min($usagePercent, 100); ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php if ($isActive): ?>
                                            <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">ใช้งานได้</span>
                                        <?php elseif ($isExpired): ?>
                                            <span class="inline-block px-3 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full">หมดอายุ</span>
                                        <?php elseif ($isUpcoming): ?>
                                            <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">เร็วๆ นี้</span>
                                        <?php else: // Not active
                                             ?>
                                            <span class="inline-block px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">ปิดใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <!-- (สำคัญ) แก้ไขลิงก์ Edit -->
                                            <a href="index.php?page=admin&section=discounts&action=edit&id=<?php echo $discount['id']; ?>" 
                                               class="px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold">
                                                แก้ไข
                                            </a>
                                            <!-- (สำคัญ) แก้ไขฟอร์ม Delete -->
                                            <form method="POST" action="index.php?page=admin&section=discounts&action=delete" style="display:inline;">
                                                <input type="hidden" name="discount_id" value="<?php echo $discount['id']; ?>">
                                                <button type
="submit" onclick="return confirm('ลบส่วนลดนี้หรือไม่?')" 
                                                        class="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs font-semibold">
                                                    ลบ
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (empty($allDiscounts)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>ยังไม่มีโค้ดส่วนลด</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>