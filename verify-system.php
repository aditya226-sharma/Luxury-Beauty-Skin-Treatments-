<?php
// System Verification Script - Check if booking data flows correctly

echo "<h2>🔍 Simran Beauty Booking System Verification</h2>";

// Check if files exist
$files_to_check = [
    'bookings.json' => 'Booking Data (JSON)',
    'bookings.csv' => 'Excel Export Data (CSV)',
    'get-bookings.php' => 'Admin Panel Data Source',
    'secure-export.php' => 'Excel Export Handler'
];

echo "<h3>📁 File Status Check:</h3>";
foreach ($files_to_check as $file => $description) {
    $exists = file_exists($file);
    $status = $exists ? '✅ EXISTS' : '❌ MISSING';
    $size = $exists ? ' (' . filesize($file) . ' bytes)' : '';
    echo "<p><strong>{$description}:</strong> {$status}{$size}</p>";
}

// Check booking data
echo "<hr><h3>📊 Booking Data Analysis:</h3>";

if (file_exists('bookings.json')) {
    $bookings = json_decode(file_get_contents('bookings.json'), true) ?: [];
    echo "<p><strong>Total Bookings:</strong> " . count($bookings) . "</p>";
    
    if (count($bookings) > 0) {
        $latest = end($bookings);
        echo "<p><strong>Latest Booking:</strong></p>";
        echo "<ul>";
        echo "<li>Token: #{$latest['token_number']}</li>";
        echo "<li>Name: {$latest['name']}</li>";
        echo "<li>Phone: +91 {$latest['phone']}</li>";
        echo "<li>Service: {$latest['service']}</li>";
        echo "<li>Date: {$latest['date']} at {$latest['time']}</li>";
        echo "<li>Status: {$latest['status']}</li>";
        echo "</ul>";
    }
} else {
    echo "<p>❌ No booking data found</p>";
}

// Test admin panel data source
echo "<hr><h3>🔧 Admin Panel Test:</h3>";
if (file_exists('get-bookings.php')) {
    echo "<p>✅ Admin data source available</p>";
    echo "<p><a href='get-bookings.php' target='_blank'>📋 View Raw Admin Data</a></p>";
} else {
    echo "<p>❌ Admin data source missing</p>";
}

// Test Excel export
echo "<hr><h3>📥 Excel Export Test:</h3>";
if (file_exists('secure-export.php')) {
    echo "<p>✅ Excel export handler available</p>";
    echo "<p><a href='secure-export.php?auth_token=simran_admin_2024' target='_blank'>📊 Test Excel Download</a></p>";
} else {
    echo "<p>❌ Excel export handler missing</p>";
}

// Create test booking if no data exists
if (!file_exists('bookings.json') || count(json_decode(file_get_contents('bookings.json'), true) ?: []) == 0) {
    echo "<hr><h3>🧪 Creating Test Booking:</h3>";
    
    $test_data = [
        'name' => 'Test Customer',
        'phone' => '9876543210',
        'email' => 'test@example.com',
        'service' => 'Facial Treatment',
        'date' => date('Y-m-d', strtotime('+1 day')),
        'time' => '10:00 AM',
        'message' => 'Test booking for system verification'
    ];
    
    // Simulate booking creation
    $booking_data = [
        'id' => uniqid(),
        'token_number' => 1,
        'name' => $test_data['name'],
        'phone' => $test_data['phone'],
        'email' => $test_data['email'],
        'service' => $test_data['service'],
        'date' => $test_data['date'],
        'time' => $test_data['time'],
        'message' => $test_data['message'],
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ];
    
    // Save to JSON
    file_put_contents('bookings.json', json_encode([$booking_data], JSON_PRETTY_PRINT));
    
    // Save to CSV
    $csv_file = 'bookings.csv';
    $fp = fopen($csv_file, 'w');
    fputcsv($fp, ['Token Number', 'Name', 'Phone', 'Email', 'Service', 'Date', 'Time', 'Message', 'Status', 'Booking Date/Time']);
    fputcsv($fp, [
        $booking_data['token_number'],
        $booking_data['name'],
        $booking_data['phone'],
        $booking_data['email'],
        $booking_data['service'],
        $booking_data['date'],
        $booking_data['time'],
        $booking_data['message'],
        $booking_data['status'],
        $booking_data['created_at']
    ]);
    fclose($fp);
    
    echo "<p>✅ Test booking created successfully!</p>";
    echo "<p><strong>Token:</strong> #{$booking_data['token_number']}</p>";
}

echo "<hr><h3>🎯 Quick Access Links:</h3>";
echo "<p><a href='admin.html' style='background: #d4af37; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔐 Admin Panel</a></p>";
echo "<p><a href='booking.html' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📅 Book Appointment</a></p>";

echo "<hr><p><small>System verification completed at " . date('Y-m-d H:i:s') . "</small></p>";
?>