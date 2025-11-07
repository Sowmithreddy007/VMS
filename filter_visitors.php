<?php
/**
 * Function to build a SQL query for filtering visitors based on multiple criteria
 * @param array $filters Associative array with filter criteria
 *   Example: ['roll_number' => 'A123', 'name' => 'John', 'year_of_graduation' => 2020, 'branch' => 'CSE', 'registration_type' => 'spot']
 * @return array [sql, params] SQL query and parameters
 */
function build_filter_query($filters) {
    $sql = "SELECT * FROM visitors";
    $params = [];
    $conditions = [];

    if (!empty($filters)) {
        foreach ($filters as $field => $value) {
            switch ($field) {
                case 'roll_number':
                    $conditions[] = "roll_number = ?";
                    $params[] = $value;
                    break;
                case 'name':
                    $conditions[] = "name LIKE ?";
                    $params[] = '%' . $value . '%';
                    break;
                case 'year_of_graduation':
                    $conditions[] = "year_of_graduation = ?";
                    $params[] = $value;
                    break;
                case 'branch':
                    $conditions[] = "branch = ?";
                    $params[] = $value;
                    break;
                case 'registration_type':
                    $conditions[] = "registration_type = ?";
                    $params[] = $value;
                    break;
            }
        }

        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    return [$sql, $params];
}

// Example usage with database connection:
// Assuming $conn is your database connection
// $filters = [
//     'roll_number' => 'A123',
//     'name' => 'John',
//     'year_of_graduation' => 2020,
//     'branch' => 'CSE',
//     'registration_type' => 'spot'
// ];

// list($sql, $params) = build_filter_query($filters);

// $stmt = $conn->prepare($sql);
// $stmt->execute($params);
// $results = $stmt->fetchAll();

// This will return all visitors matching the criteria
?>