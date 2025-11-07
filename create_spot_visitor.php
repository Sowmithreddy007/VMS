<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include connection.php to establish database connection
include ('connection.php');

// Function to create a new visitor (spot registered)
function create_visitor($name, $roll_number, $year_of_graduation, $branch) {
    // Get current member ID from session
    if (!isset($_SESSION['member_id'])) {
        return false;
    }
    $current_member_id = $_SESSION['member_id'];
    
    // Set registration_type to 'spot'
    $registration_type = 'spot';
    
    // Prepare data array
    $data = array(
        'event_id' => 1, // Example; this should be parameterized based on actual logic
        'name' => $name,
        'roll_number' => $roll_number,
        'year_of_graduation' => $year_of_graduation,
        'branch' => $branch,
        'added_by' => $current_member_id,
        'registration_type' => $registration_type
    );
    
    try {
        // Use the global connection variable
        $stmt = $conn->prepare("INSERT INTO tbl_visitors (event_id, name, roll_number, year_of_graduation, branch, added_by, registration_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissis", $data['event_id'], $data['name'], $data['roll_number'], $data['year_of_graduation'], $data['branch'], $data['added_by'], $data['registration_type']);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Error creating visitor: " . $e->getMessage());
        return false;
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $name = $_POST['name'] ?? '';
    $roll_number = $_POST['roll_number'] ?? '';
    $year = $_POST['year_of_graduation'] ?? '';
    $branch = $_POST['branch'] ?? '';
    
    // Validate input
    if (empty($name) || empty($roll_number) || empty($year) || empty($branch)) {
        die(json_encode(['error' => 'Missing required fields']));
    }
    
    // Create the visitor
    $result = create_visitor($name, $roll_number, $year, $branch);
    
    if ($result) {
        die(json_encode(['success' => true, 'message' => 'Visitor created successfully']));
    } else {
        die(json_encode(['success' => false, 'message' => 'Error creating visitor']));
    }
} else {
    die(json_encode(['error' => 'Invalid request method']));
}
?>