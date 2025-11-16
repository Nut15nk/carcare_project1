<?php
require_once 'config.php';

class ReservationService {
    public static function createReservation($reservationData) {
        $token = AuthService::getToken();
        $userId = AuthService::getUserId();
        
        $headers = ['X-User-ID: ' . $userId];
        $response = ApiConfig::makeApiCall('/reservations', 'POST', $reservationData, $token, $headers);
        
        return $response['status'] === 200 ? $response['data']['data'] : null;
    }
    
    public static function getCustomerReservations($customerId) {
        $token = AuthService::getToken();
        $response = ApiConfig::makeApiCall("/reservations/customer/{$customerId}", 'GET', null, $token);
        
        return $response['status'] === 200 ? $response['data']['data'] : [];
    }
    
    public static function calculatePrice($startDate, $endDate, $pricePerDay, $discountCode = null) {
        $params = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'pricePerDay' => $pricePerDay
        ];
        
        if ($discountCode) {
            $params['discountCode'] = $discountCode;
        }
        
        $queryString = http_build_query($params);
        $response = ApiConfig::makeApiCall("/reservations/calculate-price?{$queryString}");
        
        return $response['status'] === 200 ? $response['data']['data']['finalPrice'] : 0;
    }
}
?>