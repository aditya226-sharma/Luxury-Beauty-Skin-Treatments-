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

// Validate required fields
$required_fields = ['name', 'phone', 'email', 'service', 'date', 'time'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

// Sanitize input
$name = htmlspecialchars(trim($input['name']));
$phone = htmlspecialchars(trim($input['phone']));
$email = filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL);
$service = htmlspecialchars(trim($input['service']));
$date = htmlspecialchars(trim($input['date']));
$time = htmlspecialchars(trim($input['time']));
$message = htmlspecialchars(trim($input['message'] ?? ''));

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Validate phone (Indian format)
if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

// Validate date (not in past)
$appointment_date = new DateTime($date);
$today = new DateTime();
$today->setTime(0, 0, 0);

if ($appointment_date < $today) {
    echo json_encode(['success' => false, 'message' => 'Please select a future date']);
    exit;
}

// Generate unique token number
$token_number = 'GB' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// Ensure token is unique
$bookings_file = 'bookings.json';
$existing_bookings = [];
if (file_exists($bookings_file)) {
    $existing_bookings = json_decode(file_get_contents($bookings_file), true) ?: [];
}

while (array_search($token_number, array_column($existing_bookings, 'token_number')) !== false) {
    $token_number = 'GB' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Save to file (in production, use database)
$booking_data = [
    'id' => uniqid(),
    'token_number' => $token_number,
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'service' => $service,
    'date' => $date,
    'time' => $time,
    'message' => $message,
    'created_at' => date('Y-m-d H:i:s'),
    'status' => 'pending'
];

$existing_bookings[] = $booking_data;
file_put_contents($bookings_file, json_encode($existing_bookings, JSON_PRETTY_PRINT));

// Send email notification (configure SMTP settings)
$to = 'info@gurubakshbeauty.com';
$subject = 'New Appointment Booking - Gurubaksh Beauty';
$email_message = "
New appointment booking received:

Name: $name
Phone: $phone
Email: $email
Service: $service
Date: $date
Time: $time
Message: $message

Please contact the customer to confirm the appointment.
";

$headers = "From: noreply@gurubakshbeauty.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mail($to, $subject, $email_message, $headers);

echo json_encode([
    'success' => true,
    'message' => 'Appointment booked successfully! Your token number is: ' . $token_number,
    'booking_id' => $booking_data['id'],
    'token_number' => $token_number
]);
?>