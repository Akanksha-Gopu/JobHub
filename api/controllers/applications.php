<?php
// Applications API Controller

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

// --- GET REQUESTS: VIEW APPLICATIONS ---
if ($method === 'GET') {
    
    if ($userRole === 'seeker') {
        // Fetch Seeker's own applications list
        $db->query("
            SELECT a.id, a.job_id, a.resume_path, a.cover_letter, a.status, a.created_at,
                   j.title AS job_title, j.location AS job_location, j.salary_range, j.job_type,
                   p.company_name
            FROM applications a
            LEFT JOIN jobs j ON a.job_id = j.id
            LEFT JOIN profiles p ON j.employer_id = p.user_id
            WHERE a.seeker_id = :seeker_id
            ORDER BY a.created_at DESC
        ");
        $apps = $db->get(['seeker_id' => $userId]);
        sendResponse("success", "Applications retrieved.", $apps);
    }
    
    if ($userRole === 'employer') {
        // Employers can view applicants for their jobs
        $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
        
        if ($jobId <= 0) {
            // Retrieve all applicants across all posted jobs
            $db->query("
                SELECT a.id, a.job_id, a.resume_path, a.cover_letter, a.status, a.created_at,
                       j.title AS job_title, p.full_name AS applicant_name, p.phone AS applicant_phone,
                       u.email AS applicant_email
                FROM applications a
                LEFT JOIN jobs j ON a.job_id = j.id
                LEFT JOIN profiles p ON a.seeker_id = p.user_id
                LEFT JOIN users u ON a.seeker_id = u.id
                WHERE j.employer_id = :employer_id
                ORDER BY a.created_at DESC
            ");
            $apps = $db->get(['employer_id' => $userId]);
            sendResponse("success", "Applicants retrieved.", $apps);
        } else {
            // Verify Job Ownership
            $db->query("SELECT id, employer_id FROM jobs WHERE id = :job_id");
            $job = $db->first(['job_id' => $jobId]);
            
            if (!$job) {
                sendResponse("error", "Job listing not found.");
            }
            // Cast both to int — PDO returns strings; strict !== would always fail
            if ((int)$job['employer_id'] !== (int)$userId) {
                sendResponse("error", "Access denied. You do not own this job listing.");
            }

            // Retrieve applicants for this specific job
            $db->query("
                SELECT a.id, a.job_id, a.resume_path, a.cover_letter, a.status, a.created_at,
                       p.full_name AS applicant_name, p.phone AS applicant_phone, p.bio AS applicant_bio,
                       p.skills AS applicant_skills, u.email AS applicant_email
                FROM applications a
                LEFT JOIN profiles p ON a.seeker_id = p.user_id
                LEFT JOIN users u ON a.seeker_id = u.id
                WHERE a.job_id = :job_id
                ORDER BY a.created_at DESC
            ");
            $apps = $db->get(['job_id' => $jobId]);
            sendResponse("success", "Applicants for job retrieved.", $apps);
        }
    }
}

// --- POST REQUESTS: SUBMIT APPLICATION / UPDATE STATUS ---
if ($method === 'POST') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // ACTION: Seeker Submits Job Application
    if ($action === '' || $action === 'apply') {
        if ($userRole !== 'seeker') {
            sendResponse("error", "Unauthorized. Only Job Seekers can apply to jobs.");
        }

        $jobId = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
        $coverLetter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
        $useProfileResume = isset($_POST['use_profile_resume']) ? $_POST['use_profile_resume'] : 'false';

        // --- SERVER-SIDE VALIDATIONS ---
        
        if ($jobId <= 0) {
            sendResponse("error", "Invalid Job ID.");
        }

        // Verify Job exists and is active
        $db->query("SELECT id FROM jobs WHERE id = :job_id");
        $jobExists = $db->first(['job_id' => $jobId]);
        if (!$jobExists) {
            sendResponse("error", "Job posting does not exist.");
        }

        // Prevent double applications
        $db->query("SELECT id FROM applications WHERE job_id = :job_id AND seeker_id = :seeker_id");
        $alreadyApplied = $db->first(['job_id' => $jobId, 'seeker_id' => $userId]);
        if ($alreadyApplied) {
            sendResponse("error", "You have already applied to this job listing.");
        }

        $resumePath = '';

        // Handle Resume File Source
        if ($useProfileResume === 'true') {
            // Fetch Seeker's Profile Resume
            $db->query("SELECT resume_path FROM profiles WHERE user_id = :user_id");
            $profile = $db->first(['user_id' => $userId]);
            
            if (!$profile || empty($profile['resume_path'])) {
                sendResponse("error", "No resume found in your profile. Please upload one first.");
            }
            $resumePath = $profile['resume_path'];
        } else {
            // Handle Custom Resume Upload
            if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
                sendResponse("error", "Please upload a valid PDF resume.");
            }

            $file = $_FILES['resume'];

            // Validate File Mime Type & Extension
            $mimeType = '';
            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
            } else {
                $mimeType = $file['type'];
            }

            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($mimeType, ALLOWED_FILE_TYPES) || $fileExtension !== 'pdf') {
                sendResponse("error", "Invalid file type. Only PDF resumes are accepted.");
            }

            // Validate File Size
            if ($file['size'] > MAX_FILE_SIZE) {
                sendResponse("error", "File size exceeds the 5MB limit.");
            }

            // Double check upload directory exists
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0755, true);
            }

            // $fileExtension already assigned (with strtolower) at validation step above.
            // Re-assigning here without strtolower would create uppercase extensions in filenames.
            $newFileName = 'resume_' . $userId . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $destination = UPLOAD_DIR . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                // Store relative path in database
                $resumePath = 'assets/uploads/resumes/' . $newFileName;
            } else {
                sendResponse("error", "Failed to upload resume to server storage.");
            }
        }

        // Insert Application Into DB
        try {
            $db->query("
                INSERT INTO applications (job_id, seeker_id, resume_path, cover_letter) 
                VALUES (:job_id, :seeker_id, :resume_path, :cover_letter)
            ");
            $db->create([
                'job_id' => $jobId,
                'seeker_id' => $userId,
                'resume_path' => $resumePath,
                'cover_letter' => $coverLetter
            ]);

            sendResponse("success", "Application submitted successfully!");
        } catch (Exception $e) {
            sendResponse("error", "Failed to submit application: " . $e->getMessage());
        }
    }

    // ACTION: Employer Updates Application Status
    if ($action === 'update_status') {
        if ($userRole !== 'employer' && $userRole !== 'admin') {
            sendResponse("error", "Unauthorized action. Employer access required.");
        }

        $appId = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';

        // --- SERVER-SIDE VALIDATIONS ---
        
        if ($appId <= 0 || empty($status)) {
            sendResponse("error", "Missing application parameters.");
        }

        if (!in_array($status, ['applied', 'reviewing', 'offered', 'rejected'])) {
            sendResponse("error", "Invalid status state selection.");
        }

        // Verify Job Ownership
        $db->query("
            SELECT a.id, j.employer_id 
            FROM applications a
            LEFT JOIN jobs j ON a.job_id = j.id
            WHERE a.id = :app_id
        ");
        $app = $db->first(['app_id' => $appId]);

        if (!$app) {
            sendResponse("error", "Application record not found.");
        }

        // Cast both to int — PDO returns strings; strict !== would always fail
        if ((int)$app['employer_id'] !== (int)$userId && $userRole !== 'admin') {
            sendResponse("error", "Access denied. You do not own the job listing associated with this application.");
        }

        // Update Status
        try {
            $db->query("UPDATE applications SET status = :status WHERE id = :id");
            $db->update([
                'status' => $status,
                'id' => $appId
            ]);

            sendResponse("success", "Application status updated successfully.", ["status" => $status]);
        } catch (Exception $e) {
            sendResponse("error", "Failed to update status: " . $e->getMessage());
        }
    }
}

sendResponse("error", "Invalid request method or operation.");
