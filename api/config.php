<?php
/**
 * Divine Cleaning Pros — Configuration
 * =====================================
 * SETUP INSTRUCTIONS:
 * 1. Change ADMIN_PASSWORD_HASH below by running this in your browser:
 *    Visit: yourdomain.com/api/config.php?generate_hash=YOUR_PASSWORD
 *    Copy the output and paste it as the ADMIN_PASSWORD_HASH value.
 * 
 * 2. Set ADMIN_EMAIL to the email address that should receive contact form submissions.
 * 
 * 3. Ensure the /data/ directory is writable by the web server (chmod 755 on Hostinger).
 */

// ─── Admin Settings ──────────────────────────────────────────
// Default password is "DivineAdmin2026!" — CHANGE THIS before going live!
define('ADMIN_PASSWORD_HASH', password_hash('DivineAdmin2026!', PASSWORD_BCRYPT));
define('ADMIN_EMAIL', 'info@divinecleaningpros.com');
define('SITE_NAME', 'Divine Cleaning Pros LLC');

// ─── Rate Limiting ───────────────────────────────────────────
define('MAX_REVIEWS_PER_HOUR', 3);
define('MAX_CONTACTS_PER_HOUR', 5);

// ─── Paths ───────────────────────────────────────────────────
define('DATA_DIR', __DIR__ . '/../data/');
define('REVIEWS_APPROVED_FILE', DATA_DIR . 'reviews_approved.json');
define('REVIEWS_PENDING_FILE', DATA_DIR . 'reviews_pending.json');
define('RATE_LIMIT_FILE', DATA_DIR . 'rate_limits.json');

// ─── Session & Security ─────────────────────────────────────
define('CSRF_TOKEN_NAME', 'divine_csrf_token');

// ─── Timezone ────────────────────────────────────────────────
date_default_timezone_set('America/New_York');

// ─── CORS Headers (allow same-origin by default) ────────────
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// ─── Helper: Password hash generator (visit ?generate_hash=yourpassword) ──
if (isset($_GET['generate_hash']) && php_sapi_name() !== 'cli') {
    $hash = password_hash($_GET['generate_hash'], PASSWORD_BCRYPT);
    echo "<pre>Your password hash:\n\n$hash\n\nReplace ADMIN_PASSWORD_HASH in config.php with:\ndefine('ADMIN_PASSWORD_HASH', '$hash');</pre>";
    exit;
}

// ─── Helper Functions ────────────────────────────────────────

/**
 * Read a JSON file, return array. Returns empty array if file doesn't exist.
 */
function readJsonFile(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Write array to JSON file atomically.
 */
function writeJsonFile(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($path, $json, LOCK_EX) !== false;
}

/**
 * Sanitize user input string.
 */
function sanitizeInput(string $input): string {
    $input = trim($input);
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Check rate limit for an IP address.
 * Returns true if within limit, false if exceeded.
 */
function checkRateLimit(string $type, int $maxPerHour): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $limits = readJsonFile(RATE_LIMIT_FILE);
    $now = time();
    $hourAgo = $now - 3600;
    
    // Clean up old entries
    if (isset($limits[$type])) {
        foreach ($limits[$type] as $addr => $timestamps) {
            $limits[$type][$addr] = array_filter($timestamps, function($t) use ($hourAgo) {
                return $t > $hourAgo;
            });
            if (empty($limits[$type][$addr])) {
                unset($limits[$type][$addr]);
            }
        }
    }
    
    // Check current IP
    $count = count($limits[$type][$ip] ?? []);
    if ($count >= $maxPerHour) {
        return false;
    }
    
    // Record this request
    $limits[$type][$ip][] = $now;
    writeJsonFile(RATE_LIMIT_FILE, $limits);
    
    return true;
}

/**
 * Generate a CSRF token and store in session.
 */
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION[CSRF_TOKEN_NAME] = $token;
    return $token;
}

/**
 * Validate a CSRF token.
 */
function validateCsrfToken(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    $valid = hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    // Regenerate after use
    unset($_SESSION[CSRF_TOKEN_NAME]);
    return $valid;
}

/**
 * Send a JSON response and exit.
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Generate a unique ID for reviews.
 */
function generateId(): string {
    return bin2hex(random_bytes(8));
}
