<?php
// Vista Roofing Contact Form Handler
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

// Get form data
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$service = isset($_POST['service']) ? trim($_POST['service']) : '';
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$consent = isset($_POST['consent']) ? $_POST['consent'] : '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

if (empty($consent)) {
    $errors[] = 'You must agree to be contacted';
}

// If there are validation errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Sanitize data
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$service = htmlspecialchars($service, ENT_QUOTES, 'UTF-8');
$location = htmlspecialchars($location, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Email configuration
$to = 'mkearl@gmail.com'; // Change this to the desired recipient
$subject = 'New Contact Form Submission - Vista Roofing';

// Create email content
$emailContent = "
<html>
<head>
    <title>New Contact Form Submission - Vista Roofing</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f8fafc; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #2563eb; }
        .value { margin-top: 5px; }
        .footer { background-color: #1f2937; color: white; padding: 15px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Contact Form Submission</h2>
            <p>Vista Roofing Website</p>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Name:</div>
                <div class='value'>" . $name . "</div>
            </div>
            <div class='field'>
                <div class='label'>Email:</div>
                <div class='value'>" . $email . "</div>
            </div>
            <div class='field'>
                <div class='label'>Phone:</div>
                <div class='value'>" . ($phone ? $phone : 'Not provided') . "</div>
            </div>
            <div class='field'>
                <div class='label'>Service Needed:</div>
                <div class='value'>" . ($service ? $service : 'Not specified') . "</div>
            </div>
            <div class='field'>
                <div class='label'>Property Location:</div>
                <div class='value'>" . ($location ? $location : 'Not provided') . "</div>
            </div>
            <div class='field'>
                <div class='label'>Project Details:</div>
                <div class='value'>" . nl2br($message) . "</div>
            </div>
            <div class='field'>
                <div class='label'>Submission Time:</div>
                <div class='value'>" . date('Y-m-d H:i:s') . "</div>
            </div>
        </div>
        <div class='footer'>
            <p>This email was sent from the Vista Roofing contact form on " . date('Y-m-d H:i:s') . "</p>
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
    'X-Mailer: PHP/' . phpversion()
];

// Send email
$mailSent = mail($to, $subject, $emailContent, implode("\r\n", $headers));

// Log the submission (optional - create logs directory if it doesn't exist)
$logDir = '../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logEntry = date('Y-m-d H:i:s') . " - Contact Form Submission\n";
$logEntry .= "Name: " . $name . "\n";
$logEntry .= "Email: " . $email . "\n";
$logEntry .= "Phone: " . ($phone ? $phone : 'Not provided') . "\n";
$logEntry .= "Service: " . ($service ? $service : 'Not specified') . "\n";
$logEntry .= "Location: " . ($location ? $location : 'Not provided') . "\n";
$logEntry .= "Message: " . $message . "\n";
$logEntry .= "Email Sent: " . ($mailSent ? 'Yes' : 'No') . "\n";
$logEntry .= "---\n\n";

file_put_contents($logDir . '/contact_submissions.log', $logEntry, FILE_APPEND | LOCK_EX);

// Return response
if ($mailSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, there was an error sending your message. Please try again or call us directly at (435) 216-8746.'
    ]);
}
?>
