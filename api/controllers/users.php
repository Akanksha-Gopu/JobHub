<?php
// Users Profile API Controller

// Always load config first
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../utils/db.php";
require_once __DIR__ . "/../utils/functions.php";
require_once __DIR__ . "/../utils/auth.php";

$db = new DB();

// Auth Guard: All actions require authentication
$currentUser = requireAuth();

$userId = $currentUser['id'];
$userRole = $currentUser['role'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Retrieve current profile
    $db->query("
        SELECT u.id, u.email, u.role, p.full_name, p.phone, p.bio, p.company_name, p.company_website, p.resume_path, p.skills
        FROM users u
        LEFT JOIN profiles p ON u.id = p.user_id
        WHERE u.id = :user_id
    ");
    $profile = $db->first(['user_id' => $userId]);

    if ($profile) {
        sendResponse("success", "Profile retrieved.", $profile);
    } else {
        sendResponse("error", "Profile not found.");
    }
}

if ($method === 'POST') {
    // Update user profile details
    $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    
    // --- SERVER-SIDE VALIDATION ---
    if (empty($fullName)) {
        sendResponse("error", "Full Name is required.");
    }

    try {
        if ($userRole === 'seeker') {
            $skills = isset($_POST['skills']) ? trim($_POST['skills']) : '';
            $resumePath = null;

            // Handle PDF Resume Upload if provided
            if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['resume'];

                // Validate Mime type with fallback if fileinfo extension is disabled
                $mimeType = '';
                if (class_exists('finfo')) {
                    $finfo    = new finfo(FILEINFO_MIME_TYPE);
                    $mimeType = $finfo->file($file['tmp_name']);
                } else {
                    $mimeType = $file['type'];
                }

                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($mimeType, ALLOWED_FILE_TYPES) || $fileExt !== 'pdf') {
                    sendResponse("error", "Invalid file type. Only PDF resumes are accepted.");
                }

                // Validate File Size
                if ($file['size'] > MAX_FILE_SIZE) {
                    sendResponse("error", "File size exceeds the 5MB limit.");
                }

                // Verify upload folder
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                $fileExt     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newFileName = 'resume_' . $userId . '_' . time() . '.' . $fileExt;
                $destination = UPLOAD_DIR . $newFileName;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $resumePath = 'assets/uploads/resumes/' . $newFileName;
                }
            }

            // Update SQL Command
            if ($resumePath) {
                // If new resume uploaded, update resume_path too
                $db->query("
                    UPDATE profiles 
                    SET full_name = :full_name, phone = :phone, bio = :bio, skills = :skills, resume_path = :resume_path
                    WHERE user_id = :user_id
                ");
                $db->update([
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'bio' => $bio,
                    'skills' => $skills,
                    'resume_path' => $resumePath,
                    'user_id' => $userId
                ]);
            } else {
                // Keep existing resume
                $db->query("
                    UPDATE profiles 
                    SET full_name = :full_name, phone = :phone, bio = :bio, skills = :skills
                    WHERE user_id = :user_id
                ");
                $db->update([
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'bio' => $bio,
                    'skills' => $skills,
                    'user_id' => $userId
                ]);
            }

            sendResponse("success", "Profile updated successfully!");
        }

        if ($userRole === 'employer') {
            $companyName = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
            $companyWebsite = isset($_POST['company_website']) ? trim($_POST['company_website']) : '';

            // Validation
            if (empty($companyName)) {
                sendResponse("error", "Company Name is required.");
            }

            $db->query("
                UPDATE profiles 
                SET full_name = :full_name, phone = :phone, bio = :bio, company_name = :company_name, company_website = :company_website
                WHERE user_id = :user_id
            ");
            $db->update([
                'full_name' => $fullName,
                'phone' => $phone,
                'bio' => $bio,
                'company_name' => $companyName,
                'company_website' => $companyWebsite,
                'user_id' => $userId
            ]);

            sendResponse("success", "Company profile updated successfully!");
        }
    } catch (Exception $e) {
        sendResponse("error", "Failed to update profile: " . $e->getMessage());
    }
}

sendResponse("error", "Invalid method requested.");
