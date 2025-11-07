<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/error.log');
// Generate CSRF token only if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$server="localhost";
$username="root";
$password="Sowmith@0707";
$databasename="vms_db";

// Create database connection with error handling
// Configure mysqli to throw exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($server, $username, $password, $databasename);
    $conn->set_charset("utf8mb4");
    
    // Set strict SQL mode
    $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES'");
    
    // Set timezone
    $conn->query("SET time_zone = '+05:30'");
    
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}


// Verify database connection

?>