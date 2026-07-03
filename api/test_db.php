<?php
require_once __DIR__ . '/utils/db.php';
$db = new DB();
try {
    $db->query('SELECT * FROM user_tokens');
    print_r($db->get());
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
