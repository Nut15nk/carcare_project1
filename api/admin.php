<?php
/**
 * Admin API
 * Handles admin-related API calls (statistics, reports, management)
 */

require_once __DIR__ . '/config.php';

class AdminAPI {
    
    /**
     * Get dashboard statistics
     */
    public static function getDashboardStats() {
        $stats = [
            'total_bookings' => 0,
            'active_bookings' => 0,
            'total_customers' => 0,
            'total_revenue' => 0,
            'motorcycles_available' => 0,
            'motorcycles_total' => 0
        ];
        
        // Count bookings
        if (isset($_SESSION['mock_bookings'])) {
            $stats['total_bookings'] = count($_SESSION['mock_bookings']);
            
            foreach ($_SESSION['mock_bookings'] as $booking) {
                if ($booking['status'] === 'active') {
                    $stats['active_bookings']++;
                }
                $stats['total_revenue'] += $booking['totalPrice'];
            }
        }
        
        return [
            'success' => true,
            'data' => $stats
        ];
    }
    
    /**
     * Get booking reports with filters
     */
    public static function getBookingReport($filters = []) {
        $bookings = [];
        
        if (isset($_SESSION['mock_bookings'])) {
            $bookings = $_SESSION['mock_bookings'];
            
            // Apply status filter
            if (isset($filters['status'])) {
                $bookings = array_filter($bookings, function($b) use ($filters) {
                    return $b['status'] === $filters['status'];
                });
            }
            
            // Apply date range filter
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                $startDate = strtotime($filters['start_date']);
                $endDate = strtotime($filters['end_date']);
                
                $bookings = array_filter($bookings, function($b) use ($startDate, $endDate) {
                    $bookingDate = strtotime($b['createdAt']);
                    return $bookingDate >= $startDate && $bookingDate <= $endDate;
                });
            }
            
            // Apply motorcycle filter
            if (isset($filters['motorcycleId'])) {
                $bookings = array_filter($bookings, function($b) use ($filters) {
                    return $b['motorcycleId'] === $filters['motorcycleId'];
                });
            }
        }
        
        // Calculate summary
        $totalRevenue = 0;
        $totalDiscount = 0;
        foreach ($bookings as $booking) {
            $totalRevenue += $booking['totalPrice'];
            $totalDiscount += $booking['discount'];
        }
        
        return [
            'success' => true,
            'data' => array_values($bookings),
            'summary' => [
                'count' => count($bookings),
                'total_revenue' => $totalRevenue,
                'total_discount' => $totalDiscount,
                'average_price' => count($bookings) > 0 ? $totalRevenue / count($bookings) : 0
            ]
        ];
    }
    
    /**
     * Get revenue report
     */
    public static function getRevenueReport($groupBy = 'day') {
        $revenue = [];
        
        if (isset($_SESSION['mock_bookings'])) {
            foreach ($_SESSION['mock_bookings'] as $booking) {
                $date = date('Y-m-d', strtotime($booking['createdAt']));
                
                if (!isset($revenue[$date])) {
                    $revenue[$date] = [
                        'date' => $date,
                        'total' => 0,
                        'count' => 0,
                        'discount' => 0
                    ];
                }
                
                $revenue[$date]['total'] += $booking['totalPrice'];
                $revenue[$date]['count']++;
                $revenue[$date]['discount'] += $booking['discount'];
            }
        }
        
        return [
            'success' => true,
            'data' => $revenue,
            'total_revenue' => array_sum(array_column($revenue, 'total'))
        ];
    }
    
    /**
     * Get motorcycle performance
     */
    public static function getMotorcyclePerformance() {
        $performance = [];
        
        if (isset($_SESSION['mock_bookings'])) {
            foreach ($_SESSION['mock_bookings'] as $booking) {
                $motoId = $booking['motorcycleId'];
                
                if (!isset($performance[$motoId])) {
                    $performance[$motoId] = [
                        'motorcycleId' => $motoId,
                        'motorcycleName' => $booking['motorcycleName'],
                        'total_bookings' => 0,
                        'total_revenue' => 0,
                        'total_days_booked' => 0
                    ];
                }
                
                $performance[$motoId]['total_bookings']++;
                $performance[$motoId]['total_revenue'] += $booking['totalPrice'];
                $performance[$motoId]['total_days_booked'] += $booking['totalDays'];
            }
        }
        
        // Sort by revenue
        usort($performance, function($a, $b) {
            return $b['total_revenue'] - $a['total_revenue'];
        });
        
        return [
            'success' => true,
            'data' => $performance
        ];
    }
    
    /**
     * Get customer statistics
     */
    public static function getCustomerStats() {
        $customers = [];
        
        if (isset($_SESSION['mock_bookings'])) {
            foreach ($_SESSION['mock_bookings'] as $booking) {
                $email = $booking['userEmail'];
                
                if (!isset($customers[$email])) {
                    $customers[$email] = [
                        'email' => $email,
                        'name' => $booking['userName'],
                        'total_bookings' => 0,
                        'total_spent' => 0,
                        'last_booking' => null
                    ];
                }
                
                $customers[$email]['total_bookings']++;
                $customers[$email]['total_spent'] += $booking['totalPrice'];
                
                $bookingDate = strtotime($booking['createdAt']);
                $lastDate = $customers[$email]['last_booking'] ? strtotime($customers[$email]['last_booking']) : 0;
                
                if ($bookingDate > $lastDate) {
                    $customers[$email]['last_booking'] = $booking['createdAt'];
                }
            }
        }
        
        return [
            'success' => true,
            'data' => array_values($customers),
            'total_customers' => count($customers)
        ];
    }
    
    /**
     * Get monthly summary
     */
    public static function getMonthlySummary($year = null, $month = null) {
        if ($year === null) $year = date('Y');
        if ($month === null) $month = date('m');
        
        $summary = [
            'year' => $year,
            'month' => $month,
            'total_bookings' => 0,
            'total_revenue' => 0,
            'total_discount' => 0,
            'new_customers' => 0
        ];
        
        if (isset($_SESSION['mock_bookings'])) {
            foreach ($_SESSION['mock_bookings'] as $booking) {
                $bookingYear = date('Y', strtotime($booking['createdAt']));
                $bookingMonth = date('m', strtotime($booking['createdAt']));
                
                if ($bookingYear == $year && $bookingMonth == $month) {
                    $summary['total_bookings']++;
                    $summary['total_revenue'] += $booking['totalPrice'];
                    $summary['total_discount'] += $booking['discount'];
                }
            }
        }
        
        return [
            'success' => true,
            'data' => $summary
        ];
    }
}

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'getDashboardStats':
                $result = AdminAPI::getDashboardStats();
                jsonResponse($result);
                break;
                
            case 'getBookingReport':
                $filters = [];
                if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
                if (isset($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
                if (isset($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
                if (isset($_GET['motorcycleId'])) $filters['motorcycleId'] = $_GET['motorcycleId'];
                
                $result = AdminAPI::getBookingReport($filters);
                jsonResponse($result);
                break;
                
            case 'getRevenueReport':
                $groupBy = $_GET['groupBy'] ?? 'day';
                $result = AdminAPI::getRevenueReport($groupBy);
                jsonResponse($result);
                break;
                
            case 'getMotorcyclePerformance':
                $result = AdminAPI::getMotorcyclePerformance();
                jsonResponse($result);
                break;
                
            case 'getCustomerStats':
                $result = AdminAPI::getCustomerStats();
                jsonResponse($result);
                break;
                
            case 'getMonthlySummary':
                $year = $_GET['year'] ?? null;
                $month = $_GET['month'] ?? null;
                $result = AdminAPI::getMonthlySummary($year, $month);
                jsonResponse($result);
                break;
                
            default:
                jsonResponse(['success' => false, 'error' => 'Unknown action'], 404);
        }
    }
}
?>
