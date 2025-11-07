<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include connection.php to establish database connection
include ('connection.php');

// Add roll_number column if it doesn't exist
$table_info = $conn->query("SHOW COLUMNS FROM visitors LIKE 'roll_number'");
$column_exists = $table_info->num_rows > 0;

if (!$column_exists) {
    $sql = "ALTER TABLE visitors ADD COLUMN roll_number VARCHAR(50) NULL";
    if ($conn->query($sql)) {
        echo "Column 'roll_number' added successfully.";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column 'roll_number' already exists.";
}
?>