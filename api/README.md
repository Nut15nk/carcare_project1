# CarCare API Documentation

This directory contains organized API files for the CarCare motorcycle rental system. Each file handles a specific domain of functionality.

## Directory Structure

```
api/
├── config.php          # Shared configuration and helper functions
├── motorcycles.php     # Motorcycle management APIs
├── bookings.php        # Booking management APIs
├── users.php           # User authentication and profile APIs
├── admin.php           # Admin dashboard and reporting APIs
└── README.md           # This file
```

## Files Overview

### 1. **config.php**
Shared configuration and utility functions used by all API files.

**Key Functions:**
- `makeApiRequest()` - Generic function for making HTTP requests
- `jsonResponse()` - Return properly formatted JSON responses

**Usage:**
```php
require_once __DIR__ . '/config.php';
```

### 2. **motorcycles.php**
Handles all motorcycle-related operations.

**API Endpoints:**

#### GET /api/motorcycles.php
- **action=getAll** - Get all motorcycles
  ```
  GET /api/motorcycles.php?action=getAll
  ```

- **action=getById** - Get specific motorcycle
  ```
  GET /api/motorcycles.php?action=getById&id=1
  ```

- **action=checkAvailability** - Check motorcycle availability for date range
  ```
  GET /api/motorcycles.php?action=checkAvailability&id=1&start=2025-01-01&end=2025-01-05
  ```

- **action=search** - Search motorcycles
  ```
  GET /api/motorcycles.php?action=search&q=Honda
  ```

**PHP Usage:**
```php
require_once 'api/motorcycles.php';

// Get all motorcycles
$result = MotorcyclesAPI::getAll();

// Check availability
$available = MotorcyclesAPI::checkAvailability('1', '2025-01-01', '2025-01-05');

// Search
$results = MotorcyclesAPI::search('Honda Click');
```

### 3. **bookings.php**
Handles booking operations.

**API Endpoints:**

#### POST /api/bookings.php
- **action=create** - Create new booking
  ```json
  POST /api/bookings.php?action=create
  {
    "motorcycleId": "1",
    "motorcycleName": "Honda Wave 110i",
    "startDate": "2025-01-01",
    "endDate": "2025-01-05",
    "totalDays": 4,
    "pricePerDay": 250,
    "totalPrice": 1000,
    "discount": 0,
    "returnLocation": "Main Office"
  }
  ```

- **action=cancel** - Cancel a booking
  ```
  POST /api/bookings.php?action=cancel&id=BK1234567890
  ```

- **action=updateStatus** - Update booking status
  ```
  POST /api/bookings.php?action=updateStatus&id=BK1234567890&status=confirmed
  ```

#### GET /api/bookings.php
- **action=getUserBookings** - Get user's bookings
  ```
  GET /api/bookings.php?action=getUserBookings&email=user@example.com
  ```

- **action=getById** - Get specific booking
  ```
  GET /api/bookings.php?action=getById&id=BK1234567890
  ```

- **action=getAll** - Get all bookings (admin)
  ```
  GET /api/bookings.php?action=getAll&status=confirmed&motorcycleId=1
  ```

**PHP Usage:**
```php
require_once 'api/bookings.php';

// Create booking
$booking = BookingsAPI::create([
    'motorcycleId' => '1',
    'motorcycleName' => 'Honda Wave 110i',
    'startDate' => '2025-01-01',
    'endDate' => '2025-01-05',
    'totalDays' => 4,
    'pricePerDay' => 250,
    'totalPrice' => 1000,
    'discount' => 0,
    'returnLocation' => 'Main Office'
]);

// Get user's bookings
$bookings = BookingsAPI::getUserBookings();

// Cancel booking
$result = BookingsAPI::cancel('BK1234567890');
```

### 4. **users.php**
Handles user authentication and profile management.

**API Endpoints:**

#### POST /api/users.php
- **action=login** - User login
  ```json
  POST /api/users.php?action=login
  {
    "email": "user@example.com",
    "password": "password123"
  }
  ```

- **action=register** - User registration
  ```json
  POST /api/users.php?action=register
  {
    "name": "John Doe",
    "email": "user@example.com",
    "password": "password123",
    "password_confirm": "password123"
  }
  ```

- **action=updateProfile** - Update user profile
  ```json
  POST /api/users.php?action=updateProfile
  {
    "name": "John Doe",
    "phone": "0812345678",
    "address": "123 Main St",
    "line_id": "john.doe"
  }
  ```

- **action=changePassword** - Change password
  ```json
  POST /api/users.php?action=changePassword
  {
    "oldPassword": "oldpass123",
    "newPassword": "newpass123",
    "confirmPassword": "newpass123"
  }
  ```

#### GET /api/users.php
- **action=getProfile** - Get user profile
  ```
  GET /api/users.php?action=getProfile&email=user@example.com
  ```

- **action=isAuthenticated** - Check if user is authenticated
  ```
  GET /api/users.php?action=isAuthenticated
  ```

- **action=logout** - Logout user
  ```
  GET /api/users.php?action=logout
  ```

**PHP Usage:**
```php
require_once 'api/users.php';

// Get profile
$profile = UsersAPI::getProfile();

// Check authentication
$auth = UsersAPI::isAuthenticated();

// Update profile
$result = UsersAPI::updateProfile([
    'name' => 'Jane Doe',
    'phone' => '0812345678'
]);
```

### 5. **admin.php**
Handles admin dashboard, statistics, and reporting.

**API Endpoints:**

#### GET /api/admin.php
- **action=getDashboardStats** - Get dashboard statistics
  ```
  GET /api/admin.php?action=getDashboardStats
  ```

- **action=getBookingReport** - Get booking report with filters
  ```
  GET /api/admin.php?action=getBookingReport&status=confirmed&start_date=2025-01-01&end_date=2025-01-31
  ```

- **action=getRevenueReport** - Get revenue report
  ```
  GET /api/admin.php?action=getRevenueReport&groupBy=day
  ```

- **action=getMotorcyclePerformance** - Get motorcycle performance metrics
  ```
  GET /api/admin.php?action=getMotorcyclePerformance
  ```

- **action=getCustomerStats** - Get customer statistics
  ```
  GET /api/admin.php?action=getCustomerStats
  ```

- **action=getMonthlySummary** - Get monthly summary
  ```
  GET /api/admin.php?action=getMonthlySummary&year=2025&month=01
  ```

**PHP Usage:**
```php
require_once 'api/admin.php';

// Get dashboard stats
$stats = AdminAPI::getDashboardStats();

// Get booking report
$report = AdminAPI::getBookingReport([
    'status' => 'confirmed',
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31'
]);

// Get motorcycle performance
$performance = AdminAPI::getMotorcyclePerformance();

// Get monthly summary
$summary = AdminAPI::getMonthlySummary(2025, 1);
```

## Response Format

All API responses are in JSON format:

**Success Response:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error Response:**
```json
{
  "success": false,
  "error": "Error message",
  "status_code": 400
}
```

## Usage Examples

### From Within PHP Pages

```php
<?php
require_once 'api/motorcycles.php';
require_once 'api/bookings.php';

// Get all motorcycles
$allMotorcycles = MotorcyclesAPI::getAll();
if ($allMotorcycles['success']) {
    $motorcycles = $allMotorcycles['data'];
}

// Get user bookings
$userBookings = BookingsAPI::getUserBookings();
if ($userBookings['success']) {
    foreach ($userBookings['data'] as $booking) {
        echo $booking['motorcycleName'] . ': ' . $booking['totalPrice'];
    }
}
?>
```

### From JavaScript (AJAX)

```javascript
// Get motorcycles
fetch('/api/motorcycles.php?action=getAll')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Motorcycles:', data.data);
        }
    });

// Create booking
fetch('/api/bookings.php?action=create', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        motorcycleId: '1',
        motorcycleName: 'Honda Wave 110i',
        startDate: '2025-01-01',
        endDate: '2025-01-05',
        totalDays: 4,
        pricePerDay: 250,
        totalPrice: 1000,
        discount: 0,
        returnLocation: 'Main Office'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Booking created:', data.data);
    }
});
```

## API Design Principles

1. **Separation of Concerns** - Each file handles one domain (motorcycles, bookings, users, admin)
2. **Consistent Response Format** - All APIs return success/error in same format
3. **Session-Based** - Uses PHP sessions for authentication (can be replaced with JWT)
4. **Reusable Classes** - Each API is a static class with methods that can be called from anywhere
5. **Single Responsibility** - Each method does one specific task

## Future Enhancements

- [ ] Replace session-based auth with JWT tokens
- [ ] Add database integration (replace session storage)
- [ ] Add comprehensive error handling and logging
- [ ] Add request validation middleware
- [ ] Add rate limiting
- [ ] Add caching for frequently accessed data
- [ ] Add API documentation auto-generation

## Notes

- Currently uses PHP sessions for data storage (mock system)
- Should be integrated with a database for production
- Add CORS headers if API will be used from frontend domains
- Consider adding API authentication/authorization
