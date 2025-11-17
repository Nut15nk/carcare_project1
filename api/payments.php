<?php
require_once 'config.php';

class PaymentService
{

    /**
     * สร้างการชำระเงิน
     */
    public static function createPayment($paymentData)
    {
        try {
            $token   = $_SESSION['user']['token'] ?? '';
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            error_log("Creating payment with data: " . print_r($paymentData, true));

            $response = ApiConfig::makeApiCall('/payments', 'POST', $paymentData, null, $headers);

            error_log("Payment API Response - Status: " . $response['status']);
            error_log("Payment API Response - Data: " . print_r($response['data'], true));

            // ✅ แก้ไขตรงนี้: ตรวจสอบ response structure ให้ถูกต้อง
            if ($response['status'] === 200 || $response['status'] === 201) {
                // ตรวจสอบ structure ของ response
                if (isset($response['data']['success']) && $response['data']['success'] === true) {
                    return $response['data']['data'] ?? $response['data'];
                } else {
                    error_log("Payment API Error - Success false: " . ($response['data']['message'] ?? 'Unknown error'));
                    return null;
                }
            } else {
                error_log("Payment API Error - HTTP Status: " . $response['status']);
                error_log("Payment API Error - Message: " . ($response['data']['message'] ?? 'Unknown error'));
                return null;
            }
        } catch (Exception $e) {
            error_log("PaymentService Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงข้อมูลการชำระเงินตาม reservationId
     */
    public static function getPaymentByReservation($reservationId)
    {
        try {
            $token   = $_SESSION['user']['token'] ?? '';
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $response = ApiConfig::makeApiCall("/payments/reservation/{$reservationId}", 'GET', null, null, $headers);

            if ($response['status'] === 200) {
                return $response['data']['data'] ?? null;
            } else {
                error_log("Get Payment API Error: " . $response['status']);
                return null;
            }
        } catch (Exception $e) {
            error_log("PaymentService Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงประวัติการชำระเงินของลูกค้า
     */
    public static function getCustomerPayments($customerId)
    {
        try {
            $token   = $_SESSION['user']['token'] ?? '';
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $response = ApiConfig::makeApiCall("/payments/customer/{$customerId}", 'GET', null, null, $headers);

            if ($response['status'] === 200) {
                return $response['data']['data'] ?? [];
            } else {
                error_log("Get Customer Payments API Error: " . $response['status']);
                return [];
            }
        } catch (Exception $e) {
            error_log("PaymentService Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ยืนยันการชำระเงิน (สำหรับ admin/employee)
     */
    public static function confirmPayment($paymentId, $confirmationData)
    {
        try {
            $token   = $_SESSION['user']['token'] ?? '';
            $headers = [];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $response = ApiConfig::makeApiCall("/payments/{$paymentId}/confirm", 'PUT', $confirmationData, null, $headers);

            if ($response['status'] === 200) {
                return $response['data']['data'] ?? $response['data'];
            } else {
                error_log("Confirm Payment API Error: " . $response['status']);
                return null;
            }
        } catch (Exception $e) {
            error_log("PaymentService Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ดึงข้อมูลธนาคารสำหรับโอนเงิน
     */
    public static function getBankAccounts()
    {
        try {
            $response = ApiConfig::makeApiCall('/payments/bank-accounts', 'GET');

            if ($response['status'] === 200) {
                return $response['data']['data'] ?? [];
            } else {
                // Fallback bank accounts
                return [
                    [
                        'bankName'      => 'ธนาคารกรุงไทย',
                        'accountNumber' => '123-4-56789-0',
                        'accountName'   => 'บริษัท เทมป์เทชัน จำกัด',
                        'branch'        => 'สาขาหาดใหญ่',
                    ],
                    [
                        'bankName'      => 'ธนาคารกสิกรไทย',
                        'accountNumber' => '987-6-54321-0',
                        'accountName'   => 'บริษัท เทมป์เทชัน จำกัด',
                        'branch'        => 'สาขาหาดใหญ่',
                    ],
                ];
            }
        } catch (Exception $e) {
            error_log("PaymentService Error: " . $e->getMessage());
            // Fallback bank accounts
            return [
                [
                    'bankName'      => 'ธนาคารกรุงไทย',
                    'accountNumber' => '123-4-56789-0',
                    'accountName'   => 'บริษัท เทมป์เทชัน จำกัด',
                    'branch'        => 'สาขาหาดใหญ่',
                ],
            ];
        }
    }

    /**
     * อัพโหลดสลิปการโอน
     */
    public static function uploadPaymentSlip($paymentId, $fileData)
    {
        try {
            $token   = $_SESSION['user']['token'] ?? '';
            $headers = [
                'Content-Type' => 'multipart/form-data',
            ];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $response = ApiConfig::makeApiCall("/payments/{$paymentId}/slip", 'POST', $fileData, null, $headers);

            if ($response['status'] === 200) {
                return $response['data']['data'] ?? $response['data'];
            } else {
                error_log("Upload Slip API Error: " . $response['status']);
                return null;
            }
        } catch (Exception $e) {
            error_log("PaymentService Error: " . $e->getMessage());
            return null;
        }
    }
}
