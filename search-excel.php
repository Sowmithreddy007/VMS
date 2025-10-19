<?php
session_start();
include 'connection.php';
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id)) {
    header("Location: index.php");
    exit();
}

$search_results = [];
$search_term = '';
$event_id = '';

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $search_term = trim($_POST['search_term']);
    $event_id = intval($_POST['event_id']);
    
    $sql = "SELECT * FROM tbl_excel_imports WHERE import_status = 'pending'";
    $params = [];
    $types = '';
    
    if (!empty($search_term)) {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR mobile LIKE ?)";
        $search_param = "%$search_term%";
        $params = array_fill(0, 3, $search_param);
        $types = 'sss';
    }
    
    if (!empty($event_id)) {
        $sql .= " AND event_id = ?";
        $params[] = $event_id;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $search_results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    $import_id = intval($_POST['import_id']);
    
    // Fetch the record from excel imports
    $stmt = $conn->prepare("SELECT * FROM tbl_excel_imports WHERE id = ?");
    $stmt->bind_param("i", $import_id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($record) {
        // Insert into main visitors table with added_by and visitor_type
        $insert_sql = "INSERT INTO tbl_visitors (event_id, name, email, mobile, address, department, gender, year_of_graduation, in_time, status, added_by, visitor_type)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1, ?, 'regular')";
        $stmt = $conn->prepare($insert_sql);
        $user_id = $_SESSION['id']; // Get the current user ID
        $stmt->bind_param("isssssssi", $record['event_id'], $record['name'], $record['email'], $record['mobile'],
                          $record['address'], $record['department'], $record['gender'], $record['year_of_graduation'], $user_id);
        
        if ($stmt->execute()) {
            // Update import status to imported
            $update_sql = "UPDATE tbl_excel_imports SET import_status = 'imported' WHERE id = ?";
            $stmt2 = $conn->prepare($update_sql);
            $stmt2->bind_param("i", $import_id);
            $stmt2->execute();
            $stmt2->close();
            
            $message = "Visitor imported successfully!";
            $message_type = 'success';
        } else {
            $message = "Error importing visitor: " . $conn->error;
            $message_type = 'error';
        }
        $stmt->close();
    }
}

// Fetch events for dropdown
$events = mysqli_query($conn, "SELECT * FROM tbl_events ORDER BY event_name");
?>
<?php include('include/header.php'); ?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
        <div class="title-row">
            <span class="chip"><i class="fa-solid fa-search text-primary"></i> Excel Search</span>
            <h2>🔍 Search Excel Imports</h2>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="location.href='manage-visitors.php'">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Visitors
            </button>
        </div>
    </div>

    <?php if (isset($message)): ?>
    <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : 'success'; ?> mb-3">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="card-lite mb-4">
        <div class="card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-filter text-primary"></i>
                <span class="fw-semibold">Search Excel Records</span>
            </div>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Select Event</label>
                    <select class="form-control" name="event_id">
                        <option value="">All Events</option>
                        <?php while($event = mysqli_fetch_assoc($events)): ?>
                        <option value="<?php echo $event['event_id']; ?>" <?php echo $event_id == $event['event_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['event_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search Term</label>
                    <input type="text" class="form-control" name="search_term" placeholder="Search by name, email, or mobile" value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" name="search" class="btn btn-primary w-100">
                        <i class="fa-solid fa-search me-2"></i>Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Results -->
    <?php if (!empty($search_results)): ?>
    <div class="card-lite">
        <div class="card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-list text-primary"></i>
                <span class="fw-semibold">Search Results</span>
            </div>
            <span class="text-muted"><?php echo count($search_results); ?> record(s) found</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Department</th>
                            <th>Event</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td>
                                <?php 
                                $event_stmt = $conn->prepare("SELECT event_name FROM tbl_events WHERE event_id = ?");
                                $event_stmt->bind_param("i", $row['event_id']);
                                $event_stmt->execute();
                                $event_result = $event_stmt->get_result();
                                $event_name = $event_result->fetch_assoc()['event_name'] ?? 'Unknown';
                                $event_stmt->close();
                                echo htmlspecialchars($event_name);
                                ?>
                            </td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="import_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="import" class="btn btn-sm btn-success" onclick="return confirm('Import this visitor to main dashboard?')">
                                        <i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Import
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <div class="alert alert-info">
        <i class="fa-solid fa-info-circle me-2"></i>No records found matching your search criteria.
    </div>
    <?php endif; ?>
</div>

<?php include('include/footer.php'); ?>