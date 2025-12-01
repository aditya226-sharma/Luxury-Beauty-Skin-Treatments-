<?php
// Test script to verify all booking details are captured

// Simulate a complete booking submission
$test_booking = [
    'name' => 'Test Customer',
    'phone' => '9876543210',
    'email' => 'test@example.com',
    'service' => 'facial',
    'service_name' => 'Premium Facial Treatment',
    'service_price' => '₹1,500',
    'date' => '2024-12-15',
    'time' => '14:00',
    'time_display' => '2:00 PM',
    'message' => 'Please use organic products only. I have sensitive skin.',
    'customer_source' => 'website_booking',
    'login_name' => 'Test User Login',
    'login_phone' => '9876543210'
];

// Send POST request to booking handler
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/gurubaksh/booking-handler.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_booking));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'User-Agent: Test-Browser/1.0',
    'Referer: http://localhost/gurubaksh/booking.html'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Booking Test Results</h2>";
echo "<p><strong>HTTP Status:</strong> $http_code</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Check if booking was saved
if (file_exists('bookings.json')) {
    $bookings = json_decode(file_get_contents('bookings.json'), true);
    $latest_booking = end($bookings);
    
    echo "<h3>Latest Booking in Database:</h3>";
    echo "<pre>" . json_encode($latest_booking, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h3>Fields Verification:</h3>";
    $required_fields = [
        'id', 'token_number', 'name', 'phone', 'email', 'service', 'service_name', 
        'service_price', 'date', 'time', 'time_display', 'message', 'customer_source',
        'login_name', 'login_phone', 'booking_ip', 'user_agent', 'referrer', 
        'session_id', 'booking_method', 'payment_status', 'estimated_duration',
        'created_at', 'updated_at', 'status'
    ];
    
    echo "<ul>";
    foreach ($required_fields as $field) {
        $status = isset($latest_booking[$field]) ? '✅' : '❌';
        $value = isset($latest_booking[$field]) ? $latest_booking[$field] : 'Missing';
        echo "<li>$status <strong>$field:</strong> $value</li>";
    }
    echo "</ul>";
    
} else {
    echo "<p style='color: red;'>❌ bookings.json file not found!</p>";
}

// Test admin panel data retrieval
echo "<h3>Admin Panel Data Test:</h3>";
if (file_exists('get-bookings.php')) {
    $admin_data = file_get_contents('http://localhost/gurubaksh/get-bookings.php');
    echo "<p><strong>Admin API Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($admin_data) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ get-bookings.php not found!</p>";
}
?>