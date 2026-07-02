<?php
// Database PDO Connection Helper

require_once __DIR__ . "/../config.php";

function getPDO() {
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // Safe database error presentation for API clients
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error",
            "message" => "Database connection failed. Please ensure database exists and credentials are correct."
        ]);
        exit();
    }
}