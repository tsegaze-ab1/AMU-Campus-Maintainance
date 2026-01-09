<?php
// includes/db.php
// Creates a reusable MySQLi connection in $conn

$DB_HOST = '127.0.0.1';
$DB_NAME = 'campus_maintenance';
$DB_USER = 'root';
$DB_PASS = '';
$DB_PORT = 3306;

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    // In production you would log $e->getMessage() instead of showing it.
    http_response_code(500);
    exit('Database connection failed: ' . $e->getMessage());
}
