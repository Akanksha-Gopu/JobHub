<?php
// Reusable Authentication Helper for Stateless Bearer Token Auth

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/functions.php";

/**
 * Generate a cryptographically secure random token and store its SHA-256 hash in PostgreSQL.
 * Returns the raw token to be sent to the frontend.
 *
 * @param int $userId
 * @return string Raw token
 */
function generateToken($userId) {
    // Generate secure random bytes and convert to hex
    $rawToken = bin2hex(random_bytes(32));
    
    // Hash using SHA-256
    $tokenHash = hash('sha256', $rawToken);
    
    // Set expiry to 7 days from now
    $expiresAt = date('Y-m-d H:i:s', time() + 7 * 24 * 60 * 60);
    
    $db = new DB();
    $db->query("INSERT INTO user_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)");
    $db->create([
        'user_id'    => $userId,
        'token_hash' => $tokenHash,
        'expires_at' => $expiresAt
    ]);
    
    return $rawToken;
}

/**
 * Extract and validate the Bearer token from the Request headers.
 * Returns user info if valid, or null.
 *
 * @return array|null
 */
function validateToken() {
    $headers = null;
    
    // Extract Authorization header (handles various PHP environments)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('getallheaders')) {
        $requestHeaders = getallheaders();
        $requestHeaders = array_change_key_case($requestHeaders, CASE_LOWER);
        if (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    
    if (empty($headers)) {
        return null;
    }
    
    // Parse Bearer <token>
    $rawToken = null;
    if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        $rawToken = $matches[1];
    }
    
    if (!$rawToken) {
        return null;
    }
    
    // Hash token to compare
    $tokenHash = hash('sha256', $rawToken);
    
    $db = new DB();
    // Fetch matching token and associated user details
    $db->query("
        SELECT ut.user_id, ut.token_hash, ut.expires_at, u.role, u.email 
        FROM user_tokens ut
        JOIN users u ON ut.user_id = u.id
        WHERE ut.token_hash = :token_hash
    ");
    $record = $db->first(['token_hash' => $tokenHash]);
    
    if ($record) {
        // Expiry check
        $expiresTimestamp = strtotime($record['expires_at']);
        if ($expiresTimestamp > time()) {
            // Timing-attack safe comparison
            if (hash_equals($record['token_hash'], $tokenHash)) {
                return [
                    "id"    => (int)$record['user_id'],
                    "role"  => $record['role'],
                    "email" => $record['email']
                ];
            }
        }
    }
    
    return null;
}

/**
 * Protect endpoints by requiring authentication.
 * Terminates execution with an error response if unauthenticated.
 *
 * @return array Authenticated user details
 */
function requireAuth() {
    $user = validateToken();
    if (!$user) {
        // Safe database / system presentation for API clients
        sendResponse("error", "Authentication required.");
    }
    return $user;
}

/**
 * Log out user by deleting their token from the database.
 *
 * @return bool
 */
function logout() {
    $headers = null;
    
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    } elseif (function_exists('getallheaders')) {
        $requestHeaders = getallheaders();
        $requestHeaders = array_change_key_case($requestHeaders, CASE_LOWER);
        if (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        }
    }
    
    if (empty($headers)) {
        return false;
    }
    
    $rawToken = null;
    if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        $rawToken = $matches[1];
    }
    
    if (!$rawToken) {
        return false;
    }
    
    $tokenHash = hash('sha256', $rawToken);
    
    $db = new DB();
    $db->query("DELETE FROM user_tokens WHERE token_hash = :token_hash");
    return $db->delete(['token_hash' => $tokenHash]);
}
