<?php
/**
 * Vista Roofing Contact Form Handler
 * Handles form submissions and sends emails
 */

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Sanitize and validate input data
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($cleaned) >= 10;
}

// Get and sanitize form data
$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$service = sanitizeInput($_POST['service'] ?? '');
$location = sanitizeInput($_POST['location'] ?? '');
$message = sanitizeInput($_POST['message'] ?? '');
$consent = isset($_POST['consent']) ? true : false;

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!validateEmail($email)) {
    $errors[] = 'Please enter a valid email address';
}

if (!empty($phone) && !validatePhone($phone)) {
    $errors[] = 'Please enter a valid phone number';
}

if (empty($message)) {
    $errors[] = 'Project details are required';
}

if (!$consent) {
    $errors[] = 'You must agree to be contacted';
}

// Return validation errors
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Email configuration
$to = 'mkearl@gmail.com'; // Change this to your email
$subject = 'New Contact Form Submission - Vista Roofing';
$timestamp = date('Y-m-d H:i:s');

// Create HTML email content
$emailContent = "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>New Contact Form Submission</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #f8fafc; border-radius: 8px; overflow: hidden; }
        .header { background: linear-gradient(135deg, #1e40af, #3b82f6); color: white; padding: 30px; text-align: center; }
        .content { padding: 30px; background: white; }
        .field { margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 6px; border-left: 4px solid #3b82f6; }
        .label { font-weight: bold; color: #1e40af; margin-bottom: 5px; display: block; }
        .value { color: #374151; }
        .footer { background: #1f2937; color: #d1d5db; padding: 20px; text-align: center; font-size: 12px; }
        .highlight { background: #fef3c7; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1 style='margin: 0; font-size: 24px;'>New Contact Form Submission</h1>
            <p style='margin: 10px 0 0 0; opacity: 0.9;'>Vista Roofing Website</p>
        </div>
        
        <div class='content'>
            <div class='field'>
                <span class='label'>Name:</span>
                <span class='value'>" . htmlspecialchars($name) . "</span>
            </div>
            
            <div class='field'>
                <span class='label'>Email:</span>
                <span class='value'><a href='mailto:" . htmlspecialchars($email) . "' style='color: #3b82f6; text-decoration: none;'>" . htmlspecialchars($email) . "</a></span>
            </div>
            
            <div class='field'>
                <span class='label'>Phone:</span>
                <span class='value'>" . ($phone ? "<a href='tel:" . htmlspecialchars($phone) . "' style='color: #3b82f6; text-decoration: none;'>" . htmlspecialchars($phone) . "</a>" : 'Not provided') . "</span>
            </div>
            
            <div class='field'>
                <span class='label'>Service Needed:</span>
                <span class='value'>" . ($service ? htmlspecialchars($service) : 'Not specified') . "</span>
            </div>
            
            <div class='field'>
                <span class='label'>Property Location:</span>
                <span class='value'>" . ($location ? htmlspecialchars($location) : 'Not provided') . "</span>
            </div>
            
            <div class='field'>
                <span class='label'>Project Details:</span>
                <span class='value' style='white-space: pre-wrap;'>" . htmlspecialchars($message) . "</span>
            </div>
            
            <div class='field'>
                <span class='label'>Submission Time:</span>
                <span class='value highlight'>" . htmlspecialchars($timestamp) . "</span>
            </div>
        </div>
        
        <div class='footer'>
            <p style='margin: 0;'>This email was sent from the Vista Roofing contact form on " . htmlspecialchars($timestamp) . "</p>
        </div>
    </div>
</body>
</html>
";

// Email headers
$headers = [
    'MIME-Version: 1.0',
    'Content-type: text/html; charset=UTF-8',
    'From: Vista Roofing Contact Form <noreply@vistaroofing.org>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
    'X-Priority: 3'
];

// Send email
$mailSent = mail($to, $subject, $emailContent, implode("\r\n", $headers));

// Log the submission (optional)
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logEntry = [
    'timestamp' => $timestamp,
    'name' => $name,
    'email' => $email,
    'phone' => $phone ?: 'Not provided',
    'service' => $service ?: 'Not specified',
    'location' => $location ?: 'Not provided',
    'message' => $message,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
    'email_sent' => $mailSent ? 'Yes' : 'No'
];

$logFile = $logDir . '/contact_submissions.json';
$existingLogs = [];
if (file_exists($logFile)) {
    $existingLogs = json_decode(file_get_contents($logFile), true) ?: [];
}

$existingLogs[] = $logEntry;
file_put_contents($logFile, json_encode($existingLogs, JSON_PRETTY_PRINT));

// Return response
if ($mailSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you within 24 hours.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, there was an error sending your message. Please try calling us directly at (435) 216-8746.'
    ]);
}
?>
