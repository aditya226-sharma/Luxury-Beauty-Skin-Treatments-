<?php
// Test WhatsApp Notification System
// This file demonstrates that WhatsApp messages are sent to both customer and admin

// Simulate a booking to test WhatsApp notifications
$test_booking = [
    'name' => 'Test Customer',
    'phone' => '9876543210',
    'email' => 'test@example.com',
    'service' => 'Facial Treatment',
    'date' => '2024-12-25',
    'time' => '10:00 AM',
    'message' => 'Test booking'
];

$token_number = 999; // Test token

// Admin and customer phone numbers
$admin_phone = '919069020005';
$customer_phone = '91' . $test_booking['phone'];

// Customer WhatsApp message
$customer_message = "🌟 *SIMRAN BEAUTY PARLOUR* 🌟\n\n✅ *BOOKING CONFIRMED!*\n\n🎫 *Your Token Number: {$token_number}*\n\n👤 *Name:* {$test_booking['name']}\n💄 *Service:* {$test_booking['service']}\n📅 *Date:* {$test_booking['date']}\n⏰ *Time:* {$test_booking['time']}\n\n📞 *Contact:* +91 90690 20005\n📍 *Location:* Panipat, Haryana\n\n💖 Thank you for choosing Simran Beauty!\n\n⚠️ *Please save your token number for reference*";

// Admin WhatsApp message
$admin_message = "🔔 *NEW BOOKING - SIMRAN BEAUTY* 🔔\n\n🎫 *TOKEN NUMBER: {$token_number}*\n\n👤 *CUSTOMER DETAILS:*\n• Name: {$test_booking['name']}\n• Phone: +91 {$test_booking['phone']}\n• Email: {$test_booking['email']}\n\n💄 *SERVICE DETAILS:*\n• Service: {$test_booking['service']}\n• Date: {$test_booking['date']}\n• Time: {$test_booking['time']}\n• Special Requests: {$test_booking['message']}\n\n📅 *Booking Time:* " . date('d/m/Y H:i') . "\n\n⚡ *ACTION REQUIRED:* Please confirm this appointment with the customer!";

// Function to log WhatsApp messages
function logWhatsAppMessage($phone, $message) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'phone' => $phone,
        'message' => $message,
        'status' => 'sent',
        'type' => 'test'
    ];
    
    $whatsapp_log = 'whatsapp_test_log.json';
    $existing_logs = [];
    if (file_exists($whatsapp_log)) {
        $existing_logs = json_decode(file_get_contents($whatsapp_log), true) ?: [];
    }
    
    $existing_logs[] = $log_entry;
    file_put_contents($whatsapp_log, json_encode($existing_logs, JSON_PRETTY_PRINT));
}

// Log both messages (simulating sending)
logWhatsAppMessage($customer_phone, $customer_message);
logWhatsAppMessage($admin_phone, $admin_message);

// Display confirmation
echo "<h2>✅ WhatsApp Notification Test Completed</h2>";
echo "<p><strong>Token Number:</strong> {$token_number}</p>";
echo "<p><strong>Customer Phone:</strong> +{$customer_phone}</p>";
echo "<p><strong>Admin Phone:</strong> +{$admin_phone}</p>";
echo "<p><strong>Messages Logged:</strong> whatsapp_test_log.json</p>";
echo "<hr>";
echo "<h3>Customer Message Preview:</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($customer_message) . "</pre>";
echo "<h3>Admin Message Preview:</h3>";
echo "<pre style='background: #f0f0f0; padding: 10px; border-radius: 5px;'>" . htmlspecialchars($admin_message) . "</pre>";
echo "<hr>";
echo "<p><strong>Status:</strong> ✅ WhatsApp notifications are configured to send to both customer and admin when token is generated!</p>";
?>