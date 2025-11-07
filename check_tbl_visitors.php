<?php
// Include the connection file
include 'connection.php';

try {
    // Execute the DESCRIBE command
    $result = $conn->query("DESCRIBE tbl_visitors");

    // Fetch and display the results
    while ($row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . "\n";
        echo "Type: " . $row['Type'] . "\n";
        echo "Null: " . $row['Null'] . "\n";
        echo "Key: " . $row['Key'] . "\n";
        echo "Default: " . $row['Default'] . "\n";
        echo "Extra: " . $row['Extra'] . "\n";
        echo "--------------------------\n";
    }
} catch (mysqli_sql_exception $e) {
    error_log("Error describing tbl_visitors: " . $e->getMessage());
    die("Error describing tbl_visitors. Please try again later.");
} finally {
    if (isset($result)) {
        $result->close();
    }
    $conn->close();
}
?>