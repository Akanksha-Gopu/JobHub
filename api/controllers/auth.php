<?php
// Authentication API Controller

// Always load config first
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../utils/db.php";
require_once __DIR__ . "/../utils/functions.php";
require_once __DIR__ . "/../utils/auth.php";

$db = new DB();

// Get the requested action from URL parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

// =============================================
// Handle AJAX POST actions
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($action === 'register') {
        // Collect & Sanitise Input
        $email            = isset($_POST['email'])            ? trim($_POST['email'])            : '';
        $password         = isset($_POST['password'])         ? $_POST['password']               : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password']       : '';
        $role             = isset($_POST['role'])             ? $_POST['role']                   : '';
        $fullName         = isset($_POST['full_name'])        ? trim($_POST['full_name'])        : '';
        $companyName      = isset($_POST['company_name'])     ? trim($_POST['company_name'])     : null;
        $companyWebsite   = isset($_POST['company_website'])  ? trim($_POST['company_website'])  : null;

        // --- SERVER-SIDE VALIDATIONS ---

        // 1. Presence Checks
        if (empty($email) || empty($password) || empty($role) || empty($fullName)) {
            sendResponse("error", "Please fill in all required fields.");
        }

        // 2. Email Format Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse("error", "Please enter a valid email address.");
        }

        // 3. Password Length Check
        if (strlen($password) < 6) {
            sendResponse("error", "Password must be at least 6 characters long.");
        }

        // 4. Password Match Check
        if ($password !== $confirm_password) {
            sendResponse("error", "Passwords do not match.");
        }

        // 5. Valid Role Selection
        if (!in_array($role, ['seeker', 'employer'])) {
            sendResponse("error", "Invalid account type selected.");
        }

        // 6. Employer-Specific Field Validation
        if ($role === 'employer' && empty($companyName)) {
            sendResponse("error", "Company Name is required for Employer accounts.");
        }

        // 7. Check if Email Already Exists
        $db->query("SELECT id FROM users WHERE email = :email");
        $existing = $db->first(['email' => $email]);
        if ($existing) {
            sendResponse("error", "An account with this email already exists.");
        }

        // --- DATABASE INSERTION ---
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Use RETURNING id — lastInsertId() is unreliable in PostgreSQL
            $db->query("INSERT INTO users (email, password_hash, role) VALUES (:email, :password_hash, :role) RETURNING id");
            $newUser = $db->first([
                'email'         => $email,
                'password_hash' => $passwordHash,
                'role'          => $role
            ]);

            if (!$newUser || !isset($newUser['id'])) {
                sendResponse("error", "Registration failed. Could not create user account.");
            }

            $userId = (int)$newUser['id'];

            // Insert matching profile record
            $db->query("INSERT INTO profiles (user_id, full_name, company_name, company_website) VALUES (:user_id, :full_name, :company_name, :company_website)");
            $db->create([
                'user_id'          => $userId,
                'full_name'        => $fullName,
                'company_name'     => $companyName,
                'company_website'  => $companyWebsite
            ]);

            sendResponse("success", "Registration successful! You can now log in.");
        } catch (Exception $e) {
            sendResponse("error", "An error occurred during registration. Please try again. " . $e->getMessage());
        }

    } elseif ($action === 'login') {
        $email    = isset($_POST['email'])    ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password']   : '';

        // --- SERVER-SIDE VALIDATIONS ---
        if (empty($email) || empty($password)) {
            sendResponse("error", "Please fill in all fields.");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse("error", "Please enter a valid email address.");
        }

        // Query user credentials
        $db->query("SELECT id, email, password_hash, role FROM users WHERE email = :email");
        $user = $db->first(['email' => $email]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            sendResponse("error", "Invalid email or password.");
        }

        // Fetch User profile info for display name
        $db->query("SELECT full_name, company_name FROM profiles WHERE user_id = :user_id");
        $profile = $db->first(['user_id' => $user['id']]);

        // Generate stateless token
        $token = generateToken($user['id']);

        header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "token"  => $token,
            "user"   => [
                "id"        => (int)$user['id'],
                "email"     => $user['email'],
                "role"      => $user['role'],
                "full_name" => $profile ? $profile['full_name'] : ''
            ]
        ]);
        exit();

    } elseif ($action === 'logout') {
        logout();
        sendResponse("success", "Logged out successfully.");
    }
}

// =============================================
// Handle GET actions (session check)
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'me') {
        $currentUser = validateToken();
        if ($currentUser) {
            $db->query("
                SELECT u.id, u.email, u.role, p.full_name, p.phone, p.bio,
                       p.company_name, p.company_website, p.resume_path, p.skills
                FROM users u
                LEFT JOIN profiles p ON u.id = p.user_id
                WHERE u.id = :user_id
            ");
            $user = $db->first(['user_id' => $currentUser['id']]);

            if ($user) {
                sendResponse("success", "Session active.", $user);
            } else {
                // Session exists but user/profile not found in DB
                sendResponse("error", "User account not found. Please log in again.");
            }
        }
        // No active session
        sendResponse("error", "Unauthenticated. Please log in.");
    }
}

// Invalid action or method
sendResponse("error", "Invalid action requested.");
