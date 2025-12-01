<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Function to get estimated service duration
function getServiceDuration($service) {
    $durations = [
        'facial' => '90 minutes',
        'hair' => '120 minutes', 
        'bridal' => '240 minutes',
        'spa' => '150 minutes',
        'manicure' => '60 minutes',
        'massage' => '90 minutes'
    ];
    return $durations[strtolower($service)] ?? '60 minutes';
}

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

// Generate token number with date prefix
$bookings_file = 'bookings.json';
$existing_bookings = [];
if (file_exists($bookings_file)) {
    $existing_bookings = json_decode(file_get_contents($bookings_file), true) ?: [];
}

// Get today's date for token prefix
$today = date('Ymd'); // Format: 20241201
$today_bookings = array_filter($existing_bookings, function($booking) use ($today) {
    return isset($booking['created_at']) && date('Ymd', strtotime($booking['created_at'])) === $today;
});

// Generate token number: YYYYMMDD-001, YYYYMMDD-002, etc.
$daily_count = count($today_bookings) + 1;
$token_number = $today . '-' . str_pad($daily_count, 3, '0', STR_PAD_LEFT);

// Save to file (in production, use database)
$booking_data = [
    'id' => uniqid(),
    'token_number' => $token_number,
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'service' => $service,
    'service_name' => htmlspecialchars(trim($input['service_name'] ?? $service)),
    'service_price' => htmlspecialchars(trim($input['service_price'] ?? '₹0')),
    'date' => $date,
    'time' => $time,
    'time_display' => htmlspecialchars(trim($input['time_display'] ?? $time)),
    'message' => $message,
    'customer_source' => htmlspecialchars(trim($input['customer_source'] ?? 'direct')),
    'login_name' => htmlspecialchars(trim($input['login_name'] ?? '')),
    'login_phone' => htmlspecialchars(trim($input['login_phone'] ?? '')),
    'booking_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
    'user_agent' => htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),
    'referrer' => htmlspecialchars($_SERVER['HTTP_REFERER'] ?? 'direct'),
    'session_id' => session_id() ?: uniqid('sess_'),
    'booking_method' => 'online_form',
    'payment_status' => 'pending',
    'estimated_duration' => getServiceDuration($service),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
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
saveToCSV($booking_data);

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

// Function to save to comprehensive CSV with analytics
function saveToCSV($booking_data) {
    $csv_file = 'complete_bookings.csv';
    
    try {
        // Service rates for cost estimation
        $service_rates = [
            'facial' => 1500, 'haircut' => 800, 'makeup' => 2500, 'manicure' => 600,
            'pedicure' => 800, 'threading' => 300, 'waxing' => 1200, 'massage' => 2000
        ];
        
        // Check if file exists, if not create with headers
        if (!file_exists($csv_file)) {
            $headers = [
                'Token Number', 'Customer Name', 'Phone Number', 'Email Address',
                'Service Requested', 'Appointment Date', 'Appointment Time',
                'Special Requests', 'Current Status', 'Booking Date/Time',
                'Estimated Cost', 'Customer Type'
            ];
            
            $fp = fopen($csv_file, 'w');
            if ($fp) {
                fputcsv($fp, $headers);
                fclose($fp);
            }
        }
        
        // Calculate estimated cost
        $service = strtolower($booking_data['service'] ?? '');
        $estimated_cost = $service_rates[$service] ?? 1000;
        
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
                $booking_data['message'] ?: 'No special requests',
                strtoupper($booking_data['status']),
                $booking_data['created_at'],
                '₹' . $estimated_cost,
                'New Customer' // Will be updated in analytics
            ];
            
            fputcsv($fp, $row);
            fclose($fp);
        }
    } catch (Exception $e) {
        error_log('Complete CSV save error: ' . $e->getMessage());
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Appointment booked successfully! Your token number is: ' . $token_number,
    'booking_id' => $booking_data['id'],
    'token_number' => $token_number,
    'admin_notified' => true,
    'booking_details' => [
        'service' => $booking_data['service_name'],
        'date' => $booking_data['date'],
        'time' => $booking_data['time_display'],
        'price' => $booking_data['service_price'],
        'duration' => $booking_data['estimated_duration']
    ]
]);
?>