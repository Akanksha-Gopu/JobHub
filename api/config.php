<?php
// Centralized Database & Application Configurations

// Database connection details
define("DB_HOST", getenv("DB_HOST") ?: "localhost");
define("DB_PORT", getenv("DB_PORT") ?: "5432");
define("DB_NAME", getenv("DB_NAME") ?: "job_board");
define("DB_USERNAME", getenv("DB_USERNAME") ?: "your_username");
define("DB_PASSWORD", getenv("DB_PASSWORD") ?: "your_password");

// File upload configuration
define("UPLOAD_DIR", __DIR__ . "/../assets/uploads/resumes/");
define("MAX_FILE_SIZE", 5 * 1024 * 1024); // 5MB limit
define("ALLOWED_FILE_TYPES", ["application/pdf"]);

// Session settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
