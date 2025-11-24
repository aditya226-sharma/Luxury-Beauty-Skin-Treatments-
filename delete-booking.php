<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID is required']);
    exit;
}

$bookings_file = 'bookings.json';

if (!file_exists($bookings_file)) {
    echo json_encode(['success' => false, 'message' => 'No bookings found']);
    exit;
}

$bookings = json_decode(file_get_contents($bookings_file), true) ?: [];

$original_count = count($bookings);
$bookings = array_filter($bookings, function($booking) use ($input) {
    return $booking['id'] !== $input['id'];
});

if (count($bookings) < $original_count) {
    file_put_contents($bookings_file, json_encode(array_values($bookings), JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'message' => 'Booking deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}
?>