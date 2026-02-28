<?php
/**
 * Divine Cleaning Pros — Contact Form API
 * POST → Validates form data and sends email to the business owner
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
}

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Honeypot check
if (!empty($input['website'] ?? '')) {
    jsonResponse(['success' => true, 'message' => 'Thank you! We\'ll be in touch soon.']);
}

// CSRF validation
$csrfToken = $input['csrf_token'] ?? '';
if (!validateCsrfToken($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Invalid security token. Please refresh the page and try again.'], 403);
}

// Rate limiting
if (!checkRateLimit('contacts', MAX_CONTACTS_PER_HOUR)) {
    jsonResponse(['success' => false, 'error' => 'Too many submissions. Please try again later.'], 429);
}

// Validate required fields
$name    = sanitizeInput($input['name'] ?? '');
$email   = filter_var(trim($input['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone   = sanitizeInput($input['phone'] ?? '');
$service = sanitizeInput($input['service'] ?? '');
$message = sanitizeInput($input['message'] ?? '');

$errors = [];
if (strlen($name) < 2 || strlen($name) > 100) {
    $errors[] = 'Please enter a valid name.';
}
if (!$email) {
    $errors[] = 'Please enter a valid email address.';
}
if (strlen($phone) < 7 || strlen($phone) > 20) {
    $errors[] = 'Please enter a valid phone number.';
}
if (empty($service)) {
    $errors[] = 'Please select a service.';
}

if (!empty($errors)) {
    jsonResponse(['success' => false, 'error' => implode(' ', $errors)], 400);
}

// Service label mapping
$serviceLabels = [
    'deep'       => 'Luxury Deep Cleaning',
    'recurring'  => 'Recurring Maintenance',
    'vacation'   => 'Vacation Rentals'
];
$serviceLabel = $serviceLabels[$service] ?? $service;

// Build email
$to = ADMIN_EMAIL;
$subject = "New Quote Request from " . SITE_NAME;

$body = "
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  NEW QUOTE REQUEST — " . SITE_NAME . "
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Name:     $name
Email:    $email
Phone:    $phone
Service:  $serviceLabel

" . ($message ? "Message:\n$message\n" : "") . "
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Submitted: " . date('F j, Y \a\t g:i A') . "
IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
";

// Email headers
$headers = [
    'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'divinecleaningpros.com'),
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
    'Content-Type: text/plain; charset=UTF-8'
];

// Send email
$sent = mail($to, $subject, $body, implode("\r\n", $headers));

if ($sent) {
    jsonResponse([
        'success' => true,
        'message' => 'Thank you! Your quote request has been sent. We\'ll get back to you within 24 hours.'
    ]);
} else {
    // Log the failure for debugging
    error_log("Failed to send contact email to $to from $email");
    jsonResponse([
        'success' => false,
        'error' => 'We couldn\'t send your request right now. Please call us at (386) 675-8206 instead.'
    ], 500);
}
