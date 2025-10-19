<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
include 'connection.php';

// Check if user is logged in
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid CSRF token.';
        echo json_encode($response);
        exit();
    }
    // Get form data
    $event_id = intval($_POST['event_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $gender = substr(trim($_POST['gender'] ?? 'M'), 0, 1); // Ensure single character

    // Validate required fields
    if (empty($event_id) || empty($name)) {
        $response['message'] = 'Event and Name are required fields.';
        echo json_encode($response);
        exit();
    }

    // Insert into tbl_visitors as spot entry
    $sql = "INSERT INTO tbl_visitors
            (event_id, name, email, mobile, department, gender, added_by, visitor_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'spot_entry')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssi", $event_id, $name, $email, $mobile, $department, $gender, $year, $user_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Spot entry added successfully.';
    } else {
        $response['message'] = 'Error adding spot entry: ' . $conn->error;
    }
    
    $stmt->close();
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>