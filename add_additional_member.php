<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'connection.php';

// Check if request is AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Check if user is logged in
if (empty($_SESSION['id'])) {
    if ($is_ajax) {
        echo json_encode(['redirect' => 'index.php']);
        exit();
    } else {
        header("Location: index.php");
        exit();
    }
}

$user_id = $_SESSION['id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $event_id = intval($_POST['event_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $gender = in_array(strtolower($_POST['gender'] ?? 'Male'), ['male', 'female', 'other'])
              ? ucfirst(strtolower($_POST['gender']))
              : 'Other';
    $relation = trim($_POST['relation'] ?? '');

    // Validate required fields
    if (empty($event_id) || empty($name)) {
        $response['message'] = 'Event and Name are required fields.';
        echo json_encode($response);
        exit();
    }

    // Insert into tbl_visitors with relation info
    $sql = "INSERT INTO tbl_visitors (event_id, name, email, mobile, address, department, gender, year_of_graduation, in_time, status, added_by, relation) 
            VALUES (?, ?, ?, ?, '', ?, ?, '', NOW(), 1, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $response['message'] = 'Invalid security token';
        echo json_encode($response);
        exit();
    }

    $stmt->bind_param("isssssis", $event_id, $name, $email, $mobile, $department, $gender, $user_id, $relation);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Additional member added successfully.';
    } else {
        $response['message'] = 'Failed to save member details. Please try again.';
        error_log("Database error: " . $conn->error);
    }
    
    $stmt->close();
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>