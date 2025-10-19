<?php
session_start();
include 'connection.php';

header('Content-Type: application/json');

// Check if user is logged in
if (empty($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$member_id = $_SESSION['id'];
$name = $_SESSION['name'];

// Get POST data
// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF validation failed']);
    exit();
}

// Validate and sanitize inputs
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$phone = preg_replace('/[^0-9+]/', '', $_POST['phone'] ?? '');
$food_preference = htmlspecialchars($_POST['food_preference'] ?? '');
$linkedin = filter_var($_POST['linkedin'] ?? '', FILTER_SANITIZE_URL);
$twitter = filter_var($_POST['twitter'] ?? '', FILTER_SANITIZE_STRING);
$instagram = filter_var($_POST['instagram'] ?? '', FILTER_SANITIZE_STRING);

// Validate required fields
if (!$event_id || !$email) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Check if event exists and get event name
$event_check = mysqli_query($conn, "SELECT * FROM tbl_events WHERE event_id='$event_id'");
if (mysqli_num_rows($event_check) == 0) {
    echo json_encode(['success' => false, 'message' => 'Event not found']);
    exit();
}
$event_row = mysqli_fetch_assoc($event_check);
$event_name = $event_row['event_name'];

// Check if already registered
// Check existing registration using prepared statement
$stmt = $conn->prepare("SELECT id FROM event_registrations WHERE user_id = ? AND event_id = ?");
$stmt->bind_param("ii", $member_id, $event_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Already registered for this event']);
    exit();
}

// Insert registration
// Insert using prepared statement with parameter binding
$stmt = $conn->prepare("INSERT INTO event_registrations
    (user_id, name, email, phone, food_preference, linkedin, twitter, instagram,
     attendance_status, event_id, event, event_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'REGISTERED', ?, ?, ?)");
$stmt->bind_param("isssssssiss",
    $member_id,
    $name,
    $email,
    $phone,
    $food_preference,
    $linkedin,
    $twitter,
    $instagram,
    $event_id,
    $event_name,
    $event_date);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
}
?>