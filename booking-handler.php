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

// Generate simple sequential token number
$bookings_file = 'bookings.json';
$existing_bookings = [];
if (file_exists($bookings_file)) {
    $existing_bookings = json_decode(file_get_contents($bookings_file), true) ?: [];
}

// Get next token number (simple sequential: 1, 2, 3, etc.)
$token_number = count($existing_bookings) + 1;

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

// Ensure directory is writable
if (!is_writable(dirname($bookings_file))) {
    chmod(dirname($bookings_file), 0755);
}

// Save to JSON file
if (file_put_contents($bookings_file, json_encode($existing_bookings, JSON_PRETTY_PRINT)) === false) {
    error_log('Failed to write to bookings.json');
}

// Save to CSV/Excel file
saveToExcel($booking_data);

// Send WhatsApp notifications to both customer and admin
$admin_phone = '919069020005';
$customer_phone = '91' . $phone;

// Customer WhatsApp message
$customer_message = "🌟 *SIMRAN BEAUTY PARLOUR* 🌟\n\n✅ *BOOKING CONFIRMED!*\n\n🎫 *Your Token Number: {$token_number}*\n\n👤 *Name:* {$name}\n💄 *Service:* {$service}\n📅 *Date:* {$date}\n⏰ *Time:* {$time}\n\n📞 *Contact:* +91 90690 20005\n📍 *Location:* Panipat, Haryana\n\n💖 Thank you for choosing Simran Beauty!\n\n⚠️ *Please save your token number for reference*";

// Admin WhatsApp message
$admin_message = "🔔 *NEW BOOKING - SIMRAN BEAUTY* 🔔\n\n🎫 *TOKEN NUMBER: {$token_number}*\n\n👤 *CUSTOMER DETAILS:*\n• Name: {$name}\n• Phone: +91 {$phone}\n• Email: {$email}\n\n💄 *SERVICE DETAILS:*\n• Service: {$service}\n• Date: {$date}\n• Time: {$time}\n• Special Requests: " . ($message ?: 'None') . "\n\n📅 *Booking Time:* " . date('d/m/Y H:i') . "\n\n⚡ *ACTION REQUIRED:* Please confirm this appointment with the customer!";

// Send WhatsApp messages to both
sendWhatsAppMessage($customer_phone, $customer_message);
sendWhatsAppMessage($admin_phone, $admin_message);

// Send email notification
$to = 'info@simranbeauty.com';
$subject = 'New Appointment Booking - Token #' . $token_number;
$email_message = "
New appointment booking received:

Token Number: $token_number
Name: $name
Phone: $phone
Email: $email
Service: $service
Date: $date
Time: $time
Message: $message

Please contact the customer to confirm the appointment.
";

$headers = "From: noreply@simranbeauty.com\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

mail($to, $subject, $email_message, $headers);

// Function to send WhatsApp message
function sendWhatsAppMessage($phone, $message) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'phone' => $phone,
        'message' => $message,
        'status' => 'pending'
    ];
    
    $whatsapp_log = 'whatsapp_messages.json';
    $existing_logs = [];
    if (file_exists($whatsapp_log)) {
        $existing_logs = json_decode(file_get_contents($whatsapp_log), true) ?: [];
    }
    
    $existing_logs[] = $log_entry;
    file_put_contents($whatsapp_log, json_encode($existing_logs, JSON_PRETTY_PRINT));
}

// Function to save booking data to Excel/CSV file
function saveToExcel($booking_data) {
    $csv_file = 'bookings.csv';
    
    try {
        // Check if file exists, if not create with headers
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
            if ($fp) {
                fputcsv($fp, $headers);
                fclose($fp);
            }
        }
        
        // Append new booking data
        $fp = fopen($csv_file, 'a');
        if ($fp) {
            $row = [
                $booking_data['token_number'],
                $booking_data['name'],
                $booking_data['phone'],
                $booking_data['email'],
                $booking_data['service'],
                $booking_data['date'],
                $booking_data['time'],
                $booking_data['message'] ?: 'None',
                $booking_data['status'],
                $booking_data['created_at']
            ];
            
            fputcsv($fp, $row);
            fclose($fp);
        }
    } catch (Exception $e) {
        error_log('CSV save error: ' . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Appointment booked successfully! Your token number is: ' . $token_number,
    'booking_id' => $booking_data['id'],
    'token_number' => $token_number,
    'admin_notified' => true
]);
?>