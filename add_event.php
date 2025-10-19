<?php
session_start();
include 'connection.php';

// Check if user is logged in as admin
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        exit();
    }

    // Validate inputs
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];
    
    if (empty($event_name) || empty($event_date)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Insert event using prepared statement
    $stmt = $conn->prepare("INSERT INTO tbl_events (event_name, event_date) VALUES (?, ?)");
    $stmt->bind_param("ss", $event_name, $event_date);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Event created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating event: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

mysqli_close($conn);
?>