<?php
/**
 * Functions for Member-side API for visitor management.
 */

/**
 * Function to create a new visitor (spot registered)
 * @param string $name Visitor's name
 * @param string $roll_number Visitor's roll number
 * @param int $year_of_graduation Visitor's year of graduation
 * @param string $branch Visitor's branch
 * @return bool|int Result of insertion or false on failure
 */
function create_visitor($name, $roll_number, $year_of_graduation, $branch) {
    // Include database connection
    include_once 'connection.php';
    
    // Get current member ID from session
    session_start();
    if (!isset($_SESSION['member_id'])) {
        return false;
    }
    $current_member_id = $_SESSION['member_id'];
    
    // Set registration_type to 'spot' for spot registrations
    $registration_type = 'spot';
    
    // Prepare data array
    $data = array(
        'event_id' => 1, // Example, should be parameterized
        'name' => $name,
        'roll_number' => $roll_number,
        'year_of_graduation' => $year_of_graduation,
        'branch' => $branch,
        'added_by' => $current_member_id,
        'registration_type' => $registration_type,
        // Add other fields as needed from the table definition
    );
    
    try {
        // Prepare and execute the SQL statement
        $stmt = $conn->prepare("INSERT INTO visitors (event_id, name, roll_number, year_of_graduation, branch, added_by, registration_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissis", $data['event_id'], $data['name'], $data['roll_number'], $data['year_of_graduation'], $data['branch'], $data['added_by'], $data['registration_type']);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Error creating visitor: " . $e->getMessage());
        return false;
    }
}

/**
 * Function to update an existing visitor
 * @param int $visitor_id ID of the visitor to update
 * @param array $updated_data Associative array of fields to update
 * @return bool Result of update or false on failure
 */
function update_visitor($visitor_id, $updated_data) {
    // Include database connection
    include_once 'connection.php';
    
    // First, check if the current user can edit this visitor (only if added by them)
    session_start();
    if (!isset($_SESSION['member_id'])) {
        return false;
    }
    $current_member_id = $_SESSION['member_id'];
    
    // Fetch visitor details to verify ownership
    $stmt = $conn->prepare("SELECT added_by FROM visitors WHERE id = ?");
    $stmt->bind_param("i", $visitor_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($added_by);
    $stmt->fetch();
    $stmt->close();
    
    if (empty($added_by) || $added_by != $current_member_id) {
        // Visitor not found or not added by current member
        return false;
    }
    
    try {
        // Prepare the update statement
        $set_parts = array();
        $types = '';
        $values = array();
        
        foreach ($updated_data as $field => $value) {
            if (in_array($field, ['event_id', 'name', 'roll_number', 'year_of_graduation', 'branch', 'added_by', 'registration_type'])) {
                $types .= get_type_for_bind_param($value);
                $values[] = $value;
                $set_parts[] = $field . " = ?";
            }
        }
        
        if (empty($set_parts)) {
            return true; // Nothing to update
        }
        
        $sql = "UPDATE visitors SET " . implode(", ", $set_parts) . " WHERE id = ?";
        $types .= 'i';
        $values[] = $visitor_id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Error updating visitor: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to determine bind parameter type
 * @param mixed $value
 * @return string
 */
function get_type_for_bind_param($value) {
    if (is_int($value)) {
        return 'i';
    } elseif (is_float($value)) {
        return 'd';
    } elseif (is_string($value)) {
        return 's';
    } else {
        return 'b';
    }
}