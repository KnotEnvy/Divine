<?php
/**
 * Divine Cleaning Pros — Reviews API
 * GET  → Fetch approved reviews
 * POST → Submit a new review (goes to pending queue)
 */

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET: Return approved reviews ────────────────────────────
if ($method === 'GET') {
    $reviews = readJsonFile(REVIEWS_APPROVED_FILE);
    jsonResponse(['success' => true, 'reviews' => $reviews]);
}

// ─── POST: Submit a new review ───────────────────────────────
if ($method === 'POST') {
    // Parse JSON body
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        // Fallback to form data
        $input = $_POST;
    }

    // Honeypot check — if the hidden "website" field is filled, it's a bot
    if (!empty($input['website'] ?? '')) {
        // Silently accept but discard (don't tip off the bot)
        jsonResponse(['success' => true, 'message' => 'Thank you for your review! It will appear after approval.']);
    }

    // CSRF validation
    $csrfToken = $input['csrf_token'] ?? '';
    if (!validateCsrfToken($csrfToken)) {
        jsonResponse(['success' => false, 'error' => 'Invalid security token. Please refresh the page and try again.'], 403);
    }

    // Rate limiting
    if (!checkRateLimit('reviews', MAX_REVIEWS_PER_HOUR)) {
        jsonResponse(['success' => false, 'error' => 'Too many submissions. Please try again later.'], 429);
    }

    // Validate required fields
    $name     = sanitizeInput($input['name'] ?? '');
    $location = sanitizeInput($input['location'] ?? '');
    $rating   = intval($input['rating'] ?? 0);
    $message  = sanitizeInput($input['message'] ?? '');

    $errors = [];
    if (strlen($name) < 2 || strlen($name) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters.';
    }
    if (strlen($location) < 2 || strlen($location) > 100) {
        $errors[] = 'Location must be between 2 and 100 characters.';
    }
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating must be between 1 and 5 stars.';
    }
    if (strlen($message) < 10 || strlen($message) > 1000) {
        $errors[] = 'Review must be between 10 and 1000 characters.';
    }

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'error' => implode(' ', $errors)], 400);
    }

    // Create review object
    $review = [
        'id'        => generateId(),
        'name'      => $name,
        'location'  => $location,
        'rating'    => $rating,
        'message'   => $message,
        'submitted' => date('Y-m-d H:i:s'),
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];

    // Add to pending queue
    $pending = readJsonFile(REVIEWS_PENDING_FILE);
    $pending[] = $review;
    writeJsonFile(REVIEWS_PENDING_FILE, $pending);

    jsonResponse([
        'success' => true,
        'message' => 'Thank you for your review! It will appear on our site after approval.'
    ]);
}

// ─── Other methods not allowed ───────────────────────────────
http_response_code(405);
jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
