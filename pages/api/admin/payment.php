<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../service/Admin/AdminService.php';

use Service\Admin\AdminService;

header('Content-Type: application/json');

try {
    // ✅ FIX: อนุญาต owner + staff
    if (
        ! isset($_SESSION['user']) ||
        ! in_array($_SESSION['user']['role'] ?? '', ['owner', 'staff'], true)
    ) {
        throw new Exception('Unauthorized');
    }

    $action        = $_POST['action'] ?? $_GET['action'] ?? '';
    $reservationId = $_POST['reservation_id'] ?? $_GET['reservation_id'] ?? '';

    if (empty($reservationId)) {
        throw new Exception('Missing reservation ID');
    }

    switch ($action) {

        case 'verify':
            if (! AdminService::verifyPayment($reservationId)) {
                throw new Exception('ไม่สามารถยืนยันการชำระเงินได้');
            }
            echo json_encode([
                'success' => true,
                'message' => 'ยืนยันการชำระเงินสำเร็จ',
            ]);
            break;

        case 'reject':
            $reason = $_POST['reason'] ?? '';
            if (! AdminService::rejectPayment($reservationId, $reason)) {
                throw new Exception('ไม่สามารถปฏิเสธการชำระเงินได้');
            }
            echo json_encode([
                'success' => true,
                'message' => 'ปฏิเสธการชำระเงินสำเร็จ',
            ]);
            break;

        case 'complete':
            if (! AdminService::completeBooking($reservationId)) {
                throw new Exception('ไม่สามารถอัปเดตสถานะได้');
            }
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตสถานะเสร็จสิ้นสำเร็จ',
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}
