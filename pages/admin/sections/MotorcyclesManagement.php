<?php
// pages/admin/sections/MotorcyclesManagement.php
// จัดการรถเช่า - ใช้ข้อมูลจริงจาก API

require_once 'api/admin_motorcycles.php';

// ดึงข้อมูลรถทั้งหมดจาก API
$motorcycles = AdminMotorcycleService::getAllMotorcycles();

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? null;
    
    if ($action === 'create') {
        $motorcycleData = [
            'motorcycleId' => $_POST['motorcycle_id'],
            'brand' => $_POST['brand'],
            'model' => $_POST['model'],
            'year' => intval($_POST['year']),
            'licensePlate' => $_POST['license_plate'],
            'color' => $_POST['color'],
            'pricePerDay' => floatval($_POST['price_per_day']),
            'description' => $_POST['description'] ?? '',
            'isAvailable' => isset($_POST['is_available']),
            'maintenanceStatus' => $_POST['maintenance_status'] ?? 'READY'
        ];
        
        $success = AdminMotorcycleService::createMotorcycle($motorcycleData);
        if ($success) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'สร้างรถเช่าสำเร็จ'];
            header('Location: index.php?page=admin&section=motorcycles');
            exit;
        } else {
            $error = 'ไม่สามารถสร้างรถเช่าได้';
        }
        
    } elseif ($action === 'edit' && isset($_POST['motorcycle_id'])) {
        $motorcycleId = $_POST['motorcycle_id'];
        $motorcycleData = [
            'brand' => $_POST['brand'],
            'model' => $_POST['model'],
            'year' => intval($_POST['year']),
            'licensePlate' => $_POST['license_plate'],
            'color' => $_POST['color'],
            'pricePerDay' => floatval($_POST['price_per_day']),
            'description' => $_POST['description'] ?? '',
            'isAvailable' => isset($_POST['is_available']),
            'maintenanceStatus' => $_POST['maintenance_status'] ?? 'READY'
        ];
        
        $success = AdminMotorcycleService::updateMotorcycle($motorcycleId, $motorcycleData);
        if ($success) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'อัปเดตรถเช่าสำเร็จ'];
            header('Location: index.php?page=admin&section=motorcycles');
            exit;
        } else {
            $error = 'ไม่สามารถอัปเดตรถเช่าได้';
        }
        
    } elseif ($action === 'delete' && isset($_POST['motorcycle_id'])) {
        $motorcycleId = $_POST['motorcycle_id'];
        $success = AdminMotorcycleService::deleteMotorcycle($motorcycleId);
        if ($success) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'ลบรถเช่าสำเร็จ'];
            header('Location: index.php?page=admin&section=motorcycles');
            exit;
        } else {
            $error = 'ไม่สามารถลบรถเช่าได้';
        }
    }
}

// ดึงข้อมูลสำหรับฟอร์มแก้ไข
$editMotorcycle = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editId = $_GET['id'];
    foreach ($motorcycles as $m) {
        if ($m['motorcycleId'] === $editId || $m['id'] === $editId) {
            $editMotorcycle = $m;
            break;
        }
    }
}

// ฟังก์ชันช่วยในการแสดงสถานะ
function getStatusBadge($isAvailable, $maintenanceStatus) {
    if (!$isAvailable) {
        return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">ไม่ว่าง</span>';
    }
    
    $statusMap = [
        'READY' => ['text' => 'พร้อมให้บริการ', 'class' => 'bg-green-100 text-green-800'],
        'MAINTENANCE' => ['text' => 'ซ่อมบำรุง', 'class' => 'bg-yellow-100 text-yellow-800'],
        'CLEANING' => ['text' => 'ทำความสะอาด', 'class' => 'bg-blue-100 text-blue-800'],
        'UNAVAILABLE' => ['text' => 'ไม่พร้อมให้บริการ', 'class' => 'bg-red-100 text-red-800']
    ];
    
    $statusInfo = $statusMap[$maintenanceStatus] ?? ['text' => $maintenanceStatus, 'class' => 'bg-gray-100 text-gray-800'];
    return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $statusInfo['class'] . '">' . $statusInfo['text'] . '</span>';
}
?>

<div class="space-y-6">
    
    <!-- Header -->
    <div class="mb-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">จัดการรถเช่า</h1>
        <p class="text-gray-600">เพิ่ม แก้ไข และจัดการข้อมูลรถเช่าทั้งหมด</p>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i data-lucide="check-circle" class="h-5 w-5"></i>
            <span><?php echo $_SESSION['flash_message']['message']; ?></span>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
            <i data-lucide="alert-circle" class="h-5 w-5"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Form Section -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 sticky top-8">
                <h2 class="text-xl font-bold mb-4">
                    <?php echo $editMotorcycle ? 'แก้ไขรถเช่า' : 'เพิ่มรถเช่าใหม่'; ?>
                </h2>

                <form method="POST" action="index.php?page=admin&section=motorcycles&action=<?php echo $editMotorcycle ? 'edit' : 'create'; ?>">
                    
                    <?php if ($editMotorcycle): ?>
                        <input type="hidden" name="motorcycle_id" value="<?php echo htmlspecialchars($editMotorcycle['motorcycleId'] ?? $editMotorcycle['id']); ?>">
                    <?php else: ?>
                        <!-- Motorcycle ID (สำหรับการสร้างใหม่) -->
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">รหัสรถเช่า</label>
                            <input type="text" name="motorcycle_id" 
                                value="<?php echo htmlspecialchars($editMotorcycle['motorcycleId'] ?? $editMotorcycle['id'] ?? ''); ?>"
                                placeholder="เช่น MOTO001" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                    <?php endif; ?>

                    <!-- Brand -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ยี่ห้อ</label>
                        <input type="text" name="brand" 
                            value="<?php echo htmlspecialchars($editMotorcycle['brand'] ?? ''); ?>"
                            placeholder="เช่น Honda, Yamaha" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Model -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">รุ่น</label>
                        <input type="text" name="model" 
                            value="<?php echo htmlspecialchars($editMotorcycle['model'] ?? ''); ?>"
                            placeholder="เช่น Click 160, PCX" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Year -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ปี</label>
                        <input type="number" name="year" min="2000" max="2030"
                            value="<?php echo htmlspecialchars($editMotorcycle['year'] ?? date('Y')); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- License Plate -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ทะเบียน</label>
                        <input type="text" name="license_plate" 
                            value="<?php echo htmlspecialchars($editMotorcycle['licensePlate'] ?? $editMotorcycle['license_plate'] ?? ''); ?>"
                            placeholder="เช่น กข1234" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Color -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">สี</label>
                        <input type="text" name="color" 
                            value="<?php echo htmlspecialchars($editMotorcycle['color'] ?? ''); ?>"
                            placeholder="เช่น ดำ, แดง, ขาว" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Price Per Day -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">ราคาต่อวัน (฿)</label>
                        <input type="number" name="price_per_day" step="0.01" min="0"
                            value="<?php echo htmlspecialchars($editMotorcycle['pricePerDay'] ?? $editMotorcycle['price_per_day'] ?? ''); ?>"
                            placeholder="เช่น 500.00" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>

                    <!-- Description -->
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">รายละเอียด</label>
                        <textarea name="description" 
                            placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับรถ..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                            rows="3"><?php echo htmlspecialchars($editMotorcycle['description'] ?? ''); ?></textarea>
                    </div>

                    <!-- Availability -->
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_available" 
                                <?php echo ($editMotorcycle['isAvailable'] ?? $editMotorcycle['is_available'] ?? true) ? 'checked' : ''; ?>
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">พร้อมให้บริการ</span>
                        </label>
                    </div>

                    <!-- Maintenance Status -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">สถานะการบำรุงรักษา</label>
                        <select name="maintenance_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="READY" <?php echo ($editMotorcycle['maintenanceStatus'] ?? $editMotorcycle['maintenance_status'] ?? '') === 'READY' ? 'selected' : ''; ?>>พร้อมให้บริการ</option>
                            <option value="MAINTENANCE" <?php echo ($editMotorcycle['maintenanceStatus'] ?? $editMotorcycle['maintenance_status'] ?? '') === 'MAINTENANCE' ? 'selected' : ''; ?>>ซ่อมบำรุง</option>
                            <option value="CLEANING" <?php echo ($editMotorcycle['maintenanceStatus'] ?? $editMotorcycle['maintenance_status'] ?? '') === 'CLEANING' ? 'selected' : ''; ?>>ทำความสะอาด</option>
                            <option value="UNAVAILABLE" <?php echo ($editMotorcycle['maintenanceStatus'] ?? $editMotorcycle['maintenance_status'] ?? '') === 'UNAVAILABLE' ? 'selected' : ''; ?>>ไม่พร้อมให้บริการ</option>
                        </select>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            <?php echo $editMotorcycle ? 'อัปเดต' : 'สร้าง'; ?>
                        </button>
                        <?php if ($editMotorcycle): ?>
                            <a href="index.php?page=admin&section=motorcycles" class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-900 font-semibold py-2 px-4 rounded-lg text-center transition">
                                ยกเลิก
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- List Section -->
        <div class="lg:col-span-3">
            <!-- Stats -->
            <div class="grid grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">รถทั้งหมด</p>
                    <p class="text-2xl font-bold text-blue-600"><?php echo count($motorcycles); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">พร้อมให้บริการ</p>
                    <p class="text-2xl font-bold text-green-600">
                        <?php 
                        $availableCount = array_filter($motorcycles, function($m) {
                            return $m['isAvailable'] ?? $m['is_available'] ?? false;
                        });
                        echo count($availableCount);
                        ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">กำลังซ่อมบำรุง</p>
                    <p class="text-2xl font-bold text-yellow-600">
                        <?php 
                        $maintenanceCount = array_filter($motorcycles, function($m) {
                            $status = $m['maintenanceStatus'] ?? $m['maintenance_status'] ?? '';
                            return $status === 'MAINTENANCE';
                        });
                        echo count($maintenanceCount);
                        ?>
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">ไม่พร้อมให้บริการ</p>
                    <p class="text-2xl font-bold text-red-600">
                        <?php 
                        $unavailableCount = array_filter($motorcycles, function($m) {
                            return !($m['isAvailable'] ?? $m['is_available'] ?? false);
                        });
                        echo count($unavailableCount);
                        ?>
                    </p>
                </div>
            </div>

            <!-- Motorcycles Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold">รหัสรถ</th>
                                <th class="px-4 py-3 text-left font-semibold">ยี่ห้อ/รุ่น</th>
                                <th class="px-4 py-3 text-left font-semibold">ทะเบียน</th>
                                <th class="px-4 py-3 text-left font-semibold">ปี/สี</th>
                                <th class="px-4 py-3 text-left font-semibold">ราคาต่อวัน</th>
                                <th class="px-4 py-3 text-left font-semibold">สถานะ</th>
                                <th class="px-4 py-3 text-left font-semibold">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($motorcycles as $motorcycle): ?>
                                <?php
                                $motorcycleId = $motorcycle['motorcycleId'] ?? $motorcycle['id'];
                                $brand = $motorcycle['brand'] ?? '';
                                $model = $motorcycle['model'] ?? '';
                                $year = $motorcycle['year'] ?? '';
                                $licensePlate = $motorcycle['licensePlate'] ?? $motorcycle['license_plate'] ?? '';
                                $color = $motorcycle['color'] ?? '';
                                $pricePerDay = $motorcycle['pricePerDay'] ?? $motorcycle['price_per_day'] ?? 0;
                                $isAvailable = $motorcycle['isAvailable'] ?? $motorcycle['is_available'] ?? false;
                                $maintenanceStatus = $motorcycle['maintenanceStatus'] ?? $motorcycle['maintenance_status'] ?? 'READY';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-mono text-sm font-bold text-blue-600">
                                        <?php echo htmlspecialchars($motorcycleId); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold"><?php echo htmlspecialchars($brand); ?></div>
                                        <div class="text-gray-600 text-sm"><?php echo htmlspecialchars($model); ?></div>
                                    </td>
                                    <td class="px-4 py-3 font-mono">
                                        <?php echo htmlspecialchars($licensePlate); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm"><?php echo htmlspecialchars($year); ?></div>
                                        <div class="text-gray-600 text-sm"><?php echo htmlspecialchars($color); ?></div>
                                    </td>
                                    <td class="px-4 py-3 font-semibold">
                                        ฿<?php echo number_format($pricePerDay, 2); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php echo getStatusBadge($isAvailable, $maintenanceStatus); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <a href="index.php?page=admin&section=motorcycles&action=edit&id=<?php echo $motorcycleId; ?>" 
                                               class="px-2 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded text-xs font-semibold">
                                                แก้ไข
                                            </a>
                                            <form method="POST" action="index.php?page=admin&section=motorcycles&action=delete" style="display:inline;">
                                                <input type="hidden" name="motorcycle_id" value="<?php echo $motorcycleId; ?>">
                                                <button type="submit" onclick="return confirm('ลบรถเช่านี้หรือไม่?')" 
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
                    <?php if (empty($motorcycles)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>ยังไม่มีข้อมูลรถเช่า</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>