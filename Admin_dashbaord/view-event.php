<?php
session_start();
include 'connection.php';

// Check if user is logged in
if(empty($_SESSION['id'])) {
    header("Location: ../index.php");
    exit();
}

// Get event id from URL
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// Fetch event details
$event_name = "Unknown Event";
if ($event_id > 0) {
    $res = mysqli_query($conn, "SELECT event_name FROM tbl_events WHERE event_id = $event_id");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $event_name = $row['event_name'];
    }
}

// Fetch registrations for this event
$registrations = [];
if ($event_id > 0) {
    // Pagination setup
    $per_page = 20;
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $per_page;
    
    // Secure query with prepared statement
    $stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS *
                          FROM event_registrations
                          WHERE event_id = ?
                          ORDER BY created_at DESC
                          LIMIT ?, ?");
    $stmt->bind_param("iii", $event_id, $offset, $per_page);
    $stmt->execute();
    $reg_query = $stmt->get_result();
    
    // Get total results
    $total_rows = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
    $total_pages = ceil($total_rows / $per_page);
    if ($reg_query) {
        while ($row = mysqli_fetch_assoc($reg_query)) {
            $registrations[] = $row;
        }
    }
}

$breadcrumbs = [
    ['url' => 'admin_dashboard.php', 'text' => 'Dashboard'],
    ['text' => $event_name . ' Registrations']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($event_name); ?> - Event Registrations</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
  <?php include '../include/header.php'; ?>
  <?php include '../include/top-bar.php'; ?>

  <div class="container-fluid">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div class="title-row">
        <span class="chip"><i class="fa-solid fa-calendar-day text-primary"></i> Event</span>
        <h2>📋 <?php echo htmlspecialchars($event_name); ?> Registrations</h2>
        <span class="badge">Live Data</span>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-primary" onclick="location.reload()">
          <i class="fa-solid fa-arrow-rotate-right me-2"></i>Refresh
        </button>
      </div>
    </div>

    <!-- Registrations Table -->
    <div class="card-lite">
      <div class="card-head">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-users text-primary"></i>
          <span class="fw-semibold">Registration List</span>
        </div>
        <span class="text-muted">All registrations for <?php echo htmlspecialchars($event_name); ?></span>
      </div>
      <div class="card-body">
        <?php if (empty($registrations)): ?>
          <div class="text-center py-4">
            <i class="fa-solid fa-users-slash text-muted fs-1 mb-3"></i>
            <p class="text-muted">No registrations found for this event.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>S.No.</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Event Date</th>
                  <th>Registration Date</th>
                </tr>
              </thead>
              <tbody>
                <?php $sn = 1; ?>
                <?php foreach ($registrations as $reg): ?>
                  <tr>
                    <td><?php echo $sn; ?></td>
                    <td><?php echo htmlspecialchars($reg['name']); ?></td>
                    <td><?php echo htmlspecialchars($reg['email']); ?></td>
                    <td><?php echo !empty($reg['event_date']) ? date('M d, Y', strtotime($reg['event_date'])) : 'N/A'; ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($reg['created_at'])); ?></td>
                  </tr>
                  <?php $sn++; ?>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-3">
            <span class="muted">Showing <?php echo count($registrations); ?> of <?php echo $total_rows; ?> registration(s)</span>
            <div class="d-flex gap-2 align-items-center">
                <?php if($page > 1): ?>
                    <a href="?event_id=<?php echo $event_id; ?>&page=<?php echo $page-1; ?>"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <span class="mx-2">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                
                <?php if($page < $total_pages): ?>
                    <a href="?event_id=<?php echo $event_id; ?>&page=<?php echo $page+1; ?>"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php include '../include/footer.php'; ?>
</body>
</html>
