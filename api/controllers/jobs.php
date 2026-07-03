<?php
// Jobs API Controller

// Always load config first to ensure session is started
require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../utils/db.php";
require_once __DIR__ . "/../utils/functions.php";


header("X-Session-ID: " . session_id());

$db = new DB();

// Get request parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$method = $_SERVER['REQUEST_METHOD'];

// --- GET REQUESTS (PUBLIC / SECURE SEARCH) ---
if ($method === 'GET') {
    
    // Action: Get Job Categories
    if ($action === 'categories') {
        $db->query("SELECT id, name FROM categories ORDER BY name ASC");
        $categories = $db->get();
        sendResponse("success", "Categories retrieved.", $categories);
    }

    // Action: Get Single Job Details
    if ($id > 0) {
        $db->query("
            SELECT j.*, c.name AS category_name, p.company_name, p.company_website
            FROM jobs j
            LEFT JOIN categories c ON j.category_id = c.id
            LEFT JOIN profiles p ON j.employer_id = p.user_id
            WHERE j.id = :id
        ");
        $job = $db->first(['id' => $id]);
        
        if ($job) {
            sendResponse("success", "Job detail retrieved.", $job);
        } else {
            sendResponse("error", "Job posting not found.");
        }
    }

    // Action: List and Filter Jobs (Default GET behavior)
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $location = isset($_GET['location']) ? trim($_GET['location']) : '';
    $job_type = isset($_GET['job_type']) ? trim($_GET['job_type']) : '';
    $employer_only = isset($_GET['employer_only']) ? intval($_GET['employer_only']) : 0;

    // Base query
    $sql = "
        SELECT j.id, j.title, j.location, j.salary_range, j.job_type, j.created_at, 
               c.name AS category_name, p.company_name
        FROM jobs j
        LEFT JOIN categories c ON j.category_id = c.id
        LEFT JOIN profiles p ON j.employer_id = p.user_id
        WHERE 1=1
    ";
    $params = [];

    // Apply Filter: Employer Owned Jobs (For Employer Dashboard)
    if ($employer_only === 1) {
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employer') {
            sendResponse("error", "Access denied. Employer account required.");
        }
        $sql .= " AND j.employer_id = :employer_id";
        $params['employer_id'] = $_SESSION['user_id'];
    }
}



    // Apply Filter: Search Query (keyword match title or description)
    // Note: Use two separate named params — PDO does not support the same
    // named placeholder used more than once in a single prepared statement.
    if (!empty($q)) {
        $sql .= " AND (j.title ILIKE :q_title OR j.description ILIKE :q_desc)";
        $params['q_title'] = '%' . $q . '%';
        $params['q_desc']  = '%' . $q . '%';
    }

    // Apply Filter: Category ID
    if ($category_id > 0) {
        $sql .= " AND j.category_id = :category_id";
        $params['category_id'] = $category_id;
    }

    // Apply Filter: Location
    if (!empty($location)) {
        $sql .= " AND j.location ILIKE :location";
        $params['location'] = '%' . $location . '%';
    }

    // Apply Filter: Job Type
    if (!empty($job_type)) {
        $sql .= " AND j.job_type = :job_type";
        $params['job_type'] = $job_type;
    }

    // Sort by newest
    $sql .= " ORDER BY j.created_at DESC";

    $db->query($sql);
    $jobs = $db->get($params);

    sendResponse("success", "Jobs retrieved successfully.", $jobs);
}

// --- POST REQUESTS (CREATION, UPDATE, DELETION) ---
if ($method === 'POST') {
    
    // Auth Guard: User must be authenticated and must be an Employer (or Admin)
    if (!isset($_SESSION['user_id'])) {
        sendResponse("error", "Authentication required to perform this action.");
    }
    
    $role = $_SESSION['role'];
    $userId = $_SESSION['user_id'];
    
    if ($role !== 'employer' && $role !== 'admin') {
        sendResponse("error", "Unauthorized access. Employer privileges required.");
    }

    // ACTION: Create Job Listing
    if ($action === 'create') {
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $requirements = isset($_POST['requirements']) ? trim($_POST['requirements']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $job_type = isset($_POST['job_type']) ? trim($_POST['job_type']) : '';
        $salary_range = isset($_POST['salary_range']) ? trim($_POST['salary_range']) : '';

        // --- SERVER-SIDE VALIDATION ---
        if (empty($title) || empty($description) || empty($location) || $category_id <= 0 || empty($job_type)) {
            sendResponse("error", "Please fill in all required fields.");
        }

        if (!in_array($job_type, ['full-time', 'part-time', 'contract', 'remote'])) {
            sendResponse("error", "Invalid job type selected.");
        }

        try {
            $db->query("
                INSERT INTO jobs (employer_id, category_id, title, description, requirements, location, salary_range, job_type) 
                VALUES (:employer_id, :category_id, :title, :description, :requirements, :location, :salary_range, :job_type)
            ");
            $db->create([
                'employer_id' => $userId,
                'category_id' => $category_id,
                'title' => $title,
                'description' => $description,
                'requirements' => $requirements,
                'location' => $location,
                'salary_range' => $salary_range,
                'job_type' => $job_type
            ]);

            sendResponse("success", "Job listing published successfully!");
        } catch (Exception $e) {
            sendResponse("error", "Failed to publish job. Please try again.");
        }
    }

    // ACTION: Update Job Listing
    if ($action === 'update') {
        if ($id <= 0) {
            sendResponse("error", "Job ID is required for editing.");
        }

        // Verify Ownership: Check if employer owns the job (Admins can bypass)
        $db->query("SELECT employer_id FROM jobs WHERE id = :id");
        $job = $db->first(['id' => $id]);
        
        if (!$job) {
            sendResponse("error", "Job listing not found.");
        }
        
        // Cast both to int — PDO returns column values as strings,
        // so strict !== comparison would always fail against a session int.
        if ((int)$job['employer_id'] !== (int)$userId && $role !== 'admin') {
            sendResponse("error", "Access denied. You cannot modify other employers' job posts.");
        }

        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $requirements = isset($_POST['requirements']) ? trim($_POST['requirements']) : '';
        $location = isset($_POST['location']) ? trim($_POST['location']) : '';
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $job_type = isset($_POST['job_type']) ? trim($_POST['job_type']) : '';
        $salary_range = isset($_POST['salary_range']) ? trim($_POST['salary_range']) : '';

        // --- SERVER-SIDE VALIDATION ---
        if (empty($title) || empty($description) || empty($location) || $category_id <= 0 || empty($job_type)) {
            sendResponse("error", "Please fill in all required fields.");
        }

        if (!in_array($job_type, ['full-time', 'part-time', 'contract', 'remote'])) {
            sendResponse("error", "Invalid job type selected.");
        }

        try {
            $db->query("
                UPDATE jobs 
                SET title = :title, description = :description, requirements = :requirements, 
                    location = :location, category_id = :category_id, job_type = :job_type, salary_range = :salary_range
                WHERE id = :id
            ");
            $db->update([
                'title' => $title,
                'description' => $description,
                'requirements' => $requirements,
                'location' => $location,
                'category_id' => $category_id,
                'job_type' => $job_type,
                'salary_range' => $salary_range,
                'id' => $id
            ]);

            sendResponse("success", "Job listing updated successfully!");
        } catch (Exception $e) {
            sendResponse("error", "Failed to update job post.");
        }
    }

    // ACTION: Delete Job Listing
    if ($action === 'delete') {
        if ($id <= 0) {
            sendResponse("error", "Job ID is required for deleting.");
        }

        // Verify Ownership: Check if employer owns the job (Admins can bypass)
        $db->query("SELECT employer_id FROM jobs WHERE id = :id");
        $job = $db->first(['id' => $id]);
        
        if (!$job) {
            sendResponse("error", "Job listing not found.");
        }
        
        if ((int)$job['employer_id'] !== (int)$userId && $role !== 'admin') {
            sendResponse("error", "Access denied. You cannot delete other employers' job posts.");
        }

        try {
            $db->query("DELETE FROM jobs WHERE id = :id");
            $db->delete(['id' => $id]);
            sendResponse("success", "Job listing deleted successfully.");
        } catch (Exception $e) {
            sendResponse("error", "Failed to delete job post.");
        }
    }
}

sendResponse("error", "Invalid request method or action.");
