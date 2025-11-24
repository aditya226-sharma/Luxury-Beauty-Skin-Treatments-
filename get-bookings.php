<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$bookings_file = 'bookings.json';

if (!file_exists($bookings_file)) {
    echo json_encode([]);
    exit;
}

$bookings = json_decode(file_get_contents($bookings_file), true) ?: [];

// Sort by created_at descending (newest first)
usort($bookings, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

echo json_encode($bookings);
?>