<?php
// Centralized Database & Application Configurations

// Database connection details
define("DB_HOST", getenv("DB_HOST") ?: "localhost");
define("DB_PORT", getenv("DB_PORT") ?: "5432");
define("DB_NAME", getenv("DB_NAME") ?: "job_board");
define("DB_USERNAME", getenv("DB_USERNAME") ?: "your_username");
define("DB_PASSWORD", getenv("DB_PASSWORD") ?: "your_password");

// File upload configuration
$uploadDir = __DIR__ . "/../assets/uploads/resumes/";
// If on Vercel or target path is read-only, fallback to /tmp/
if (getenv("VERCEL") || (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) || !is_writable($uploadDir)) {
    $uploadDir = rtrim(sys_get_temp_dir(), '/\\') . '/';
}
define("UPLOAD_DIR", $uploadDir);
define("MAX_FILE_SIZE", 5 * 1024 * 1024); // 5MB limit
define("ALLOWED_FILE_TYPES", ["application/pdf"]);

