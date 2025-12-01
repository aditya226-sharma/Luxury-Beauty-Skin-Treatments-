<?php
// Create a test booking to verify the system
$test_booking = [
    'id' => 'test_' . uniqid(),
    'token_number' => 1,
    'name' => 'Test Customer',
    'phone' => '9876543210',
    'email' => 'test@example.com',
    'service' => 'Facial Treatment',
    'date' => date('Y-m-d', strtotime('+1 day')),
    'time' => '10:00 AM',
    'message' => 'This is a test booking',
    'created_at' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

$bookings_file = 'bookings.json';
$existing_bookings = [];

if (file_exists($bookings_file)) {
    $existing_bookings = json_decode(file_get_contents($bookings_file), true) ?: [];
}

$existing_bookings[] = $test_booking;

if (file_put_contents($bookings_file, json_encode($existing_bookings, JSON_PRETTY_PRINT))) {
    echo "Test booking created successfully!<br>";
    echo "Token Number: " . $test_booking['token_number'] . "<br>";
    echo "Name: " . $test_booking['name'] . "<br>";
    echo "<a href='admin.html'>View in Admin Panel</a>";
} else {
    echo "Error creating test booking!";
}
?>