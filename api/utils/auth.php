<?php
// Reusable Authentication Helper for Stateless Bearer Token Auth

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/functions.php";

/**
 * Extract the Authorization header from various server parameters.
 * Supports getallheaders(), apache_request_headers(), and $_SERVER fallbacks.
 *
 * @return string|null
 */
function getAuthorizationHeader() {
    $headers = null;
    
    // 1. Try getallheaders() if available
    if (function_exists('getallheaders')) {
        $requestHeaders = getallheaders();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } elseif (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        } else {
            $requestHeadersLower = array_change_key_case($requestHeaders, CASE_LOWER);
            if (isset($requestHeadersLower['authorization'])) {
                $headers = trim($requestHeadersLower['authorization']);
            }
        }
    }
    
    // 2. Try apache_request_headers() if available
    if (empty($headers) && function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        } elseif (isset($requestHeaders['authorization'])) {
            $headers = trim($requestHeaders['authorization']);
        } else {
            $requestHeadersLower = array_change_key_case($requestHeaders, CASE_LOWER);
            if (isset($requestHeadersLower['authorization'])) {
                $headers = trim($requestHeadersLower['authorization']);
            }
        }
    }
    
    // 3. Try $_SERVER['HTTP_AUTHORIZATION']
    if (empty($headers) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    }
    
    // 4. Try $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
    if (empty($headers) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
    }
    
    return $headers;
}

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
    
    // Set expiry to 7 days from now (in UTC explicitly)
    $expiresAt = gmdate('Y-m-d H:i:s', time() + 7 * 24 * 60 * 60);
    
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
    $headers = getAuthorizationHeader();
    if (empty($headers)) {
        return null;
    }
    
    // Parse Bearer <token> (case-insensitive and support multiple spaces)
    $rawToken = null;
    if (preg_match('/Bearer\s+(\S+)/i', $headers, $matches)) {
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
        // Expiry check (timezone safe comparison using UTC explicitly)
        $expiresTimestamp = strtotime($record['expires_at'] . ' UTC');
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
        sendResponse("error", "Unauthenticated. Please log in.");
    }
    return $user;
}

/**
 * Log out user by deleting their token from the database.
 *
 * @return bool
 */
function logout() {
    $headers = getAuthorizationHeader();
    if (empty($headers)) {
        return false;
    }
    
    $rawToken = null;
    if (preg_match('/Bearer\s+(\S+)/i', $headers, $matches)) {
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
