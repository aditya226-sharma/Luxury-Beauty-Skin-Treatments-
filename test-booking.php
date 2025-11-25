<?php
// Test booking system to ensure data is being saved properly

// Create a test booking
$test_booking = [
    'id' => uniqid(),
    'token_number' => 1,
    'name' => 'Test Customer',
    'phone' => '9876543210',
    'email' => 'test@example.com',
    'service' => 'Facial Treatment',
    'date' => '2024-12-25',
    'time' => '10:00 AM',
    'message' => 'Test booking for system verification',
    'created_at' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

// Read existing bookings
$bookings_file = 'bookings.json';
$existing_bookings = [];
if (file_exists($bookings_file)) {
    $existing_bookings = json_decode(file_get_contents($bookings_file), true) ?: [];
}

// Add test booking
$existing_bookings[] = $test_booking;

// Save to JSON file
file_put_contents($bookings_file, json_encode($existing_bookings, JSON_PRETTY_PRINT));

// Also save to CSV
$csv_file = 'bookings.csv';
if (!file_exists($csv_file)) {
    $headers = [
        'Token Number',
        'Name',
        'Phone',
        'Email',
        'Service',
        'Date',
        'Time',
        'Message',
        'Status',
        'Booking Date/Time'
    ];
    
    $fp = fopen($csv_file, 'w');
    fputcsv($fp, $headers);
    fclose($fp);
}

$fp = fopen($csv_file, 'a');
$row = [
    $test_booking['token_number'],
    $test_booking['name'],
    $test_booking['phone'],
    $test_booking['email'],
    $test_booking['service'],
    $test_booking['date'],
    $test_booking['time'],
    $test_booking['message'],
    $test_booking['status'],
    $test_booking['created_at']
];
fputcsv($fp, $row);
fclose($fp);

echo "<h2>✅ Test Booking Created Successfully!</h2>";
echo "<p><strong>Token Number:</strong> " . $test_booking['token_number'] . "</p>";
echo "<p><strong>Files Updated:</strong></p>";
echo "<ul>";
echo "<li>✅ bookings.json - " . (file_exists('bookings.json') ? 'Created/Updated' : 'Failed') . "</li>";
echo "<li>✅ bookings.csv - " . (file_exists('bookings.csv') ? 'Created/Updated' : 'Failed') . "</li>";
echo "</ul>";
echo "<p><strong>Total Bookings:</strong> " . count($existing_bookings) . "</p>";
echo "<hr>";
echo "<p><a href='admin.html'>📊 View in Admin Panel</a> | <a href='secure-export.php?auth_token=simran_admin_2024'>📥 Download Excel</a></p>";
?>