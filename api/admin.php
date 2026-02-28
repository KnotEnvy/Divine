<?php
/**
 * Divine Cleaning Pros — Admin API
 * Handles login, logout, and review moderation (approve/reject).
 * All admin endpoints require an active authenticated session.
 */

require_once __DIR__ . '/config.php';

session_start();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ─── Public endpoints ────────────────────────────────────────

// Login
if ($action === 'login' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $password = $input['password'] ?? '';
    
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time'] = time();
        jsonResponse(['success' => true, 'message' => 'Login successful.']);
    } else {
        // Brief delay to slow brute force
        sleep(1);
        jsonResponse(['success' => false, 'error' => 'Invalid password.'], 401);
    }
}

// Check auth status
if ($action === 'status') {
    $isAuth = isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
    
    // Session expires after 2 hours
    if ($isAuth && (time() - ($_SESSION['admin_login_time'] ?? 0)) > 7200) {
        session_destroy();
        $isAuth = false;
    }
    
    jsonResponse(['success' => true, 'authenticated' => $isAuth]);
}

// CSRF token for forms
if ($action === 'csrf') {
    $token = generateCsrfToken();
    jsonResponse(['success' => true, 'token' => $token]);
}

// ─── Auth check for all other actions ────────────────────────
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
}

// Check session expiry (2 hours)
if ((time() - ($_SESSION['admin_login_time'] ?? 0)) > 7200) {
    session_destroy();
    jsonResponse(['success' => false, 'error' => 'Session expired. Please log in again.'], 401);
}

// ─── Authenticated endpoints ─────────────────────────────────

// Logout
if ($action === 'logout') {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Logged out successfully.']);
}

// Get pending reviews
if ($action === 'pending' && $method === 'GET') {
    $pending = readJsonFile(REVIEWS_PENDING_FILE);
    jsonResponse(['success' => true, 'reviews' => $pending]);
}

// Get approved reviews
if ($action === 'approved' && $method === 'GET') {
    $approved = readJsonFile(REVIEWS_APPROVED_FILE);
    jsonResponse(['success' => true, 'reviews' => $approved]);
}

// Approve a review
if ($action === 'approve' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $reviewId = sanitizeInput($input['id'] ?? '');
    
    if (empty($reviewId)) {
        jsonResponse(['success' => false, 'error' => 'Review ID required.'], 400);
    }
    
    $pending = readJsonFile(REVIEWS_PENDING_FILE);
    $approved = readJsonFile(REVIEWS_APPROVED_FILE);
    
    $found = false;
    $newPending = [];
    
    foreach ($pending as $review) {
        if ($review['id'] === $reviewId) {
            // Remove IP before making public
            unset($review['ip']);
            $review['approved_at'] = date('Y-m-d H:i:s');
            $approved[] = $review;
            $found = true;
        } else {
            $newPending[] = $review;
        }
    }
    
    if (!$found) {
        jsonResponse(['success' => false, 'error' => 'Review not found.'], 404);
    }
    
    writeJsonFile(REVIEWS_PENDING_FILE, $newPending);
    writeJsonFile(REVIEWS_APPROVED_FILE, $approved);
    
    jsonResponse(['success' => true, 'message' => 'Review approved and published.']);
}

// Reject (delete) a review
if ($action === 'reject' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $reviewId = sanitizeInput($input['id'] ?? '');
    
    if (empty($reviewId)) {
        jsonResponse(['success' => false, 'error' => 'Review ID required.'], 400);
    }
    
    $pending = readJsonFile(REVIEWS_PENDING_FILE);
    
    $found = false;
    $newPending = [];
    
    foreach ($pending as $review) {
        if ($review['id'] === $reviewId) {
            $found = true;
        } else {
            $newPending[] = $review;
        }
    }
    
    if (!$found) {
        jsonResponse(['success' => false, 'error' => 'Review not found.'], 404);
    }
    
    writeJsonFile(REVIEWS_PENDING_FILE, $newPending);
    
    jsonResponse(['success' => true, 'message' => 'Review rejected and removed.']);
}

// Delete an approved review
if ($action === 'delete' && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    
    $reviewId = sanitizeInput($input['id'] ?? '');
    
    if (empty($reviewId)) {
        jsonResponse(['success' => false, 'error' => 'Review ID required.'], 400);
    }
    
    $approved = readJsonFile(REVIEWS_APPROVED_FILE);
    
    $found = false;
    $newApproved = [];
    
    foreach ($approved as $review) {
        if ($review['id'] === $reviewId) {
            $found = true;
        } else {
            $newApproved[] = $review;
        }
    }
    
    if (!$found) {
        jsonResponse(['success' => false, 'error' => 'Review not found.'], 404);
    }
    
    writeJsonFile(REVIEWS_APPROVED_FILE, $newApproved);
    
    jsonResponse(['success' => true, 'message' => 'Review deleted.']);
}

// Unknown action
jsonResponse(['success' => false, 'error' => 'Unknown action.'], 400);
