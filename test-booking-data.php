<?php
// Create test booking data to verify admin display
$test_booking = [
    'id' => 'test_' . uniqid(),
    'token_number' => '20241201-001',
    'name' => 'Priya Sharma',
    'phone' => '9876543210',
    'email' => 'priya.sharma@email.com',
    'service' => 'Bridal Makeup',
    'date' => '2024-12-05',
    'time' => '10:00',
    'message' => 'Need makeup for wedding ceremony. Please use waterproof products.',
    'created_at' => '2024-12-01 14:30:00',
    'status' => 'pending'
];

$bookings_file = 'bookings.json';
$existing_bookings = [];

if (file_exists($bookings_file)) {
    $existing_bookings = json_decode(file_get_contents($bookings_file), true) ?: [];
}

$existing_bookings[] = $test_booking;

file_put_contents($bookings_file, json_encode($existing_bookings, JSON_PRETTY_PRINT));

echo "Test booking created successfully!";
?>