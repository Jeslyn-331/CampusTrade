<?php
/**
 * CampusTrade — database connection.
 * Every page starts with: require_once __DIR__ . '/includes/db.php';
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die('Database connection failed. Please check that MySQL is running and database.sql has been imported.');
}
