<?php
// Secure Excel Export - Admin Only Access
session_start();

// Check authentication from both POST and GET
$auth_token = $_POST['auth_token'] ?? $_GET['auth_token'] ?? '';
if ($auth_token !== 'simran_admin_2024') {
    http_response_code(401);
    die('🔒 Unauthorized Access - Admin Login Required');
}

// Get date range from both POST and GET data
$from_date = $_POST['from_date'] ?? $_GET['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? $_GET['to_date'] ?? '';

// Set default date range if not provided
if (empty($from_date)) {
    $from_date = date('Y-m-d', strtotime('-30 days'));
}
if (empty($to_date)) {
    $to_date = date('Y-m-d');
}

// Create filename with date range
$filename_suffix = '';
if ($from_date === $to_date) {
    $filename_suffix = '_' . $from_date;
} else {
    $filename_suffix = '_' . $from_date . '_to_' . $to_date;
}

$_SESSION['admin_logged_in'] = true;

// Excel Export Headers
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="simran_beauty_bookings' . $filename_suffix . '_' . date('H-i-s') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Read bookings from JSON file
$bookings_file = 'bookings.json';
$all_bookings = [];

if (file_exists($bookings_file)) {
    $all_bookings = json_decode(file_get_contents($bookings_file), true) ?: [];
}

// Filter bookings by date range
$bookings = [];
foreach ($all_bookings as $booking) {
    $booking_date = date('Y-m-d', strtotime($booking['created_at']));
    if ($booking_date >= $from_date && $booking_date <= $to_date) {
        $bookings[] = $booking;
    }
}

// Sort bookings by date (newest first)
usort($bookings, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Start Excel output with enhanced styling
echo '<html>';
echo '<head><meta charset="UTF-8"></head>';
echo '<body>';
echo '<h2 style="color: #d4af37; text-align: center;">📊 SIMRAN BEAUTY PARLOUR - BOOKING RECORDS</h2>';
echo '<p style="text-align: center; margin-bottom: 20px;">';
echo 'Date Range: ' . date('d/m/Y', strtotime($from_date)) . ' to ' . date('d/m/Y', strtotime($to_date)) . ' | ';
echo 'Generated on: ' . date('Y-m-d H:i:s') . ' | Total Records: ' . count($bookings);
echo '</p>';

echo '<table border="1" cellpadding="8" cellspacing="0" style="width: 100%; border-collapse: collapse;">';
echo '<tr style="background-color: #d4af37; color: white; font-weight: bold; text-align: center;">';
echo '<th>S.No.</th>';
echo '<th>Token Number</th>';
echo '<th>Customer Name</th>';
echo '<th>Phone Number</th>';
echo '<th>Email Address</th>';
echo '<th>Service Requested</th>';
echo '<th>Appointment Date</th>';
echo '<th>Appointment Time</th>';
echo '<th>Special Requests</th>';
echo '<th>Current Status</th>';
echo '<th>Booking Date/Time</th>';
echo '<th>Days Since Booking</th>';
echo '</tr>';

// Output booking data with alternating row colors
$row_count = 0;
foreach ($bookings as $booking) {
    $row_count++;
    $bg_color = ($row_count % 2 == 0) ? '#f8f9fa' : '#ffffff';
    $status_color = ($booking['status'] === 'confirmed') ? '#d4edda' : '#fff3cd';
    
    // Calculate days since booking
    $booking_date = new DateTime($booking['created_at']);
    $current_date = new DateTime();
    $days_diff = $current_date->diff($booking_date)->days;
    
    echo '<tr style="background-color: ' . $bg_color . ';">';
    echo '<td style="text-align: center; font-weight: bold;">' . $row_count . '</td>';
    echo '<td style="text-align: center; font-weight: bold; color: #d4af37; font-size: 14px;">' . htmlspecialchars($booking['token_number'] ?? '') . '</td>';
    echo '<td style="font-weight: bold;">' . htmlspecialchars($booking['name'] ?? '') . '</td>';
    echo '<td style="text-align: center;">' . htmlspecialchars($booking['phone'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($booking['email'] ?? '') . '</td>';
    echo '<td style="font-weight: bold; color: #0066cc;">' . htmlspecialchars($booking['service'] ?? '') . '</td>';
    echo '<td style="text-align: center; font-weight: bold;">' . htmlspecialchars($booking['date'] ?? '') . '</td>';
    echo '<td style="text-align: center; font-weight: bold;">' . htmlspecialchars($booking['time'] ?? '') . '</td>';
    echo '<td style="font-style: italic;">' . htmlspecialchars($booking['message'] ?? 'No special requests') . '</td>';
    echo '<td style="background-color: ' . $status_color . '; text-align: center; font-weight: bold;">' . 
         htmlspecialchars(strtoupper($booking['status'] ?? 'PENDING')) . '</td>';
    echo '<td style="text-align: center;">' . htmlspecialchars($booking['created_at'] ?? '') . '</td>';
    echo '<td style="text-align: center; font-weight: bold; color: ' . ($days_diff > 7 ? '#dc3545' : '#28a745') . ';">' . $days_diff . ' days</td>';
    echo '</tr>';
}

echo '</table>';

// Add comprehensive summary statistics
$total_bookings = count($bookings);
$confirmed_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'confirmed'; }));
$pending_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'pending'; }));

// Service analysis
$service_stats = [];
foreach ($bookings as $booking) {
    $service = $booking['service'] ?? 'Unknown';
    $service_stats[$service] = ($service_stats[$service] ?? 0) + 1;
}
arsort($service_stats);

// Revenue estimation
$service_rates = [
    'facial' => 1500, 'haircut' => 800, 'makeup' => 2500, 'manicure' => 600,
    'pedicure' => 800, 'threading' => 300, 'waxing' => 1200, 'massage' => 2000
];
$estimated_revenue = 0;
foreach ($bookings as $booking) {
    $service = strtolower($booking['service'] ?? '');
    $estimated_revenue += $service_rates[$service] ?? 1000;
}

echo '<br><br>';
echo '<div style="display: flex; gap: 20px;">';

// Summary table
echo '<table border="1" cellpadding="8" cellspacing="0" style="width: 40%; border-collapse: collapse;">';
echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
echo '<th colspan="2" style="text-align: center; color: #d4af37;">📈 BOOKING SUMMARY</th>';
echo '</tr>';
echo '<tr><td><strong>Total Bookings:</strong></td><td style="text-align: center; font-weight: bold;">' . $total_bookings . '</td></tr>';
echo '<tr><td><strong>Confirmed Bookings:</strong></td><td style="text-align: center; color: green; font-weight: bold;">' . $confirmed_bookings . '</td></tr>';
echo '<tr><td><strong>Pending Bookings:</strong></td><td style="text-align: center; color: orange; font-weight: bold;">' . $pending_bookings . '</td></tr>';
echo '<tr><td><strong>Estimated Revenue:</strong></td><td style="text-align: center; color: #d4af37; font-weight: bold;">₹' . number_format($estimated_revenue) . '</td></tr>';
echo '<tr><td><strong>Export Date:</strong></td><td style="text-align: center;">' . date('Y-m-d H:i:s') . '</td></tr>';
echo '<tr><td><strong>Generated By:</strong></td><td style="text-align: center;">Simran Beauty Admin</td></tr>';
echo '</table>';

// Service popularity table
echo '<table border="1" cellpadding="8" cellspacing="0" style="width: 40%; border-collapse: collapse;">';
echo '<tr style="background-color: #f8f9fa; font-weight: bold;">';
echo '<th colspan="2" style="text-align: center; color: #d4af37;">💄 SERVICE POPULARITY</th>';
echo '</tr>';
foreach ($service_stats as $service => $count) {
    $percentage = round(($count / $total_bookings) * 100, 1);
    echo '<tr><td><strong>' . htmlspecialchars($service) . ':</strong></td><td style="text-align: center;">' . $count . ' (' . $percentage . '%)</td></tr>';
}
echo '</table>';

echo '</div>';

echo '<br><p style="font-size: 12px; color: #666; text-align: center;">🔒 This file contains confidential customer information. Handle with care.</p>';
echo '</body>';
echo '</html>';
?>