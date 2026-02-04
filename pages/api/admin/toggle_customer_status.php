<?php
// pages/api/admin/toggle_customer_status.php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../service/Admin/AdminService.php';

header('Content-Type: application/json');

// ตรวจสอบสิทธิ์
if (! isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

// อ่านข้อมูลจาก POST
$data       = json_decode(file_get_contents('php://input'), true);
$customerId = $data['customerId'] ?? '';
$action     = $data['action'] ?? '';

if (empty($customerId) || ! in_array($action, ['ban', 'unban'])) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $db = Database::connect();

    // กำหนดค่า isActive ตาม action
    $isActive = $action === 'unban' ? 1 : 0;

    // อัปเดตสถานะลูกค้า
    $stmt = $db->prepare("UPDATE customers SET is_active = ? WHERE customer_id = ?");
    $stmt->execute([$isActive, $customerId]);

    // บันทึก log (ถ้ามีตาราง logs)
    $logMessage = $action === 'ban'
        ? "แบนลูกค้า ID: $customerId โดย " . $_SESSION['user']['email']
        : "ยกเลิกการแบนลูกค้า ID: $customerId โดย " . $_SESSION['user']['email'];

    error_log($logMessage);

    echo json_encode([
        'success' => true,
        'message' => $action === 'ban' ? 'แบนลูกค้าเรียบร้อยแล้ว' : 'ยกเลิกการแบนลูกค้าเรียบร้อยแล้ว',
    ]);

} catch (Exception $e) {
    error_log("Toggle customer status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
