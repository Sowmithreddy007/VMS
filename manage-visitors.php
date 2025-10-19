<?php
session_start();
include ('connection.php');
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id))
{
    header("Location: index.php");
    exit();
}
?>
<?php include('include/header.php'); ?>

<!-- Content -->
<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="title-row">
      <span class="chip"><i class="fa-solid fa-users-gear text-primary"></i> Visitors</span>
      <h2>👥 Manage Visitors</h2>
      <span class="badge">Live Data</span>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" onclick="location.href='new-visitor.php'"><i class="fa-solid fa-plus me-2"></i>Add Visitor</button>
      <button class="btn btn-success" onclick="location.href='excel-import.php'"><i class="fa-solid fa-file-excel me-2"></i>Import Excel</button>
      <button class="btn btn-info" onclick="location.href='search-excel.php'"><i class="fa-solid fa-search me-2"></i>Search Excel</button>
      <button class="btn btn-outline-primary" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-2"></i>Refresh</button>
    </div>
  </div>

  <!-- Search Form -->
  <div class="card-lite mb-3">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-filter text-primary"></i>
        <span class="fw-semibold">Filter Visitors</span>
      </div>
    </div>
    <div class="card-body">
      <form method="post" class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
          <label class="form-label">From Date</label>
          <input type="date" class="form-control" id="from_date" name="from_date" required>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">To Date</label>
          <input type="date" class="form-control" id="to_date" name="to_date" required>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Department</label>
          <select class="form-control" id="department" name="department">
            <option value="">All Departments</option>
            <?php
            $fetch_department = mysqli_query($conn, "select * from tbl_department");
            while($row = mysqli_fetch_array($fetch_department)){
            ?>
            <option value="<?php echo $row['department']; ?>"><?php echo $row['department']; ?></option>
            <?php } ?>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <button type="submit" name="srh-btn" class="btn btn-primary w-100"><i class="fa-solid fa-search me-2"></i>Search</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Visitors Table -->
  <div class="card-lite">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-users text-primary"></i>
        <span class="fw-semibold">Visitors List</span>
      </div>
      <span class="text-muted"><?php echo isset($search_query) ? 'Filtered Results' : 'All Visitors'; ?></span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>S.No.</th>
              <th>Name</th>
              <th>Email</th>
              <th>Mobile</th>
              <th>Department</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if(isset($_REQUEST['srh-btn']))
            {
              $from_date = $_POST['from_date'];
              $to_date = $_POST['to_date'];
              $dept = $_POST['department'];
              $from_date = date('Y-m-d', strtotime($from_date));
              $to_date = date('Y-m-d', strtotime($to_date));

              $sql = "SELECT * FROM tbl_visitors WHERE DATE(in_time) BETWEEN ? AND ?";
              $params = [$from_date, $to_date];
              $types = "ss";

              if (!empty($dept)) {
                  $sql .= " AND department=?";
                  $params[] = $dept;
                  $types .= "s";
              }
              
              $stmt = $conn->prepare($sql);
              $stmt->bind_param($types, ...$params);
              $stmt->execute();
              $search_query = $stmt->get_result();

              $sn = 1;
              while($row = mysqli_fetch_array($search_query))
            { ?>
              <tr>
                <td><?php echo $sn; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                <td><?php echo htmlspecialchars($row['department']); ?></td>
                <td>
                  <span class="badge <?php echo $row['status']==1 ? 'text-bg-success-subtle text-success border border-success' : 'text-bg-danger-subtle text-danger border border-danger'; ?>">
                    <?php echo $row['status']==1 ? 'In' : 'Out'; ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <a href="edit-visitor.php?id=<?php echo $row['id'];?>" class="btn btn-sm btn-outline-primary">
                      <i class="fa-solid fa-pencil me-1"></i><?php echo $row['status']==1 ? 'Edit' : 'View'; ?>
                    </a>
                    <form method="POST" action="manage-visitors.php" onsubmit="return confirmDelete()">
                      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="delete_visitor_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fa-solid fa-trash me-1"></i>Delete
                        </button>
                    </form>
                      <?php
                      // Handle visitor deletion
                      if(isset($_POST['delete_visitor_id'])) {
                          // Validate CSRF token first
                          if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                              die("Invalid CSRF token");
                          }
                          
                          $id = intval($_POST['delete_visitor_id']);
                          $stmt = $conn->prepare("DELETE FROM tbl_visitors WHERE id = ?");
                          $stmt->bind_param("i", $id);
                          if($stmt->execute()) {
                              echo "<script>alert('Visitor deleted successfully'); window.location.reload();</script>";
                          } else {
                              echo "<script>alert('Error deleting visitor: " . $stmt->error . "');</script>";
                          }
                          $stmt->close();
                      }
                      ?>
                  </div>
                </td>
              </tr>
              <?php $sn++; }
              $stmt->close();
            } else {
              if(isset($_POST['delete_visitor_id'])){
                $id_to_delete = $_POST['delete_visitor_id'];
                $stmt = $conn->prepare("DELETE FROM tbl_visitors WHERE id=?");
                $stmt->bind_param("i", $id_to_delete);
                if ($stmt->execute()) {
                    echo "<script>alert('Visitor deleted successfully');</script>";
                    echo "<script>window.location.href='manage-visitors.php';</script>";
                } else {
                    echo "<script>alert('Error deleting visitor: " . $stmt->error . "');</script>";
                }
                $stmt->close();
              }
              $select_query = mysqli_query($conn, "select * from tbl_visitors ORDER BY created_at DESC");
              $sn = 1;
              while($row = mysqli_fetch_array($select_query))
              {
            ?>
            <tr>
              <td><?php echo $sn; ?></td>
              <td><?php echo htmlspecialchars($row['name']); ?></td>
              <td><?php echo htmlspecialchars($row['email']); ?></td>
              <td><?php echo htmlspecialchars($row['mobile']); ?></td>
              <td><?php echo htmlspecialchars($row['department']); ?></td>
              <td>
                <span class="badge <?php echo $row['status']==1 ? 'text-bg-success-subtle text-success border border-success' : 'text-bg-danger-subtle text-danger border border-danger'; ?>">
                  <?php echo $row['status']==1 ? 'In' : 'Out'; ?>
                </span>
              </td>
              <td>
                <div class="d-flex gap-2">
                  <a href="edit-visitor.php?id=<?php echo $row['id'];?>" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-pencil me-1"></i><?php echo $row['status']==1 ? 'Edit' : 'View'; ?>
                  </a>
                  <form method="POST" action="manage-visitors.php" onsubmit="return confirmDelete()">
                      <input type="hidden" name="delete_visitor_id" value="<?php echo $row['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="fa-solid fa-trash me-1"></i>Delete
                      </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php $sn++; } } ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="muted">Showing <?php echo isset($search_query) ? 'filtered' : 'all'; ?> visitors</span>
        <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download me-1"></i>Export</button>
      </div>
    </div>
  </div>

  <!-- Event Registrations Table -->
  <div class="card-lite mt-4">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-calendar-check text-primary"></i>
        <span class="fw-semibold">Event Registrations</span>
      </div>
      <span class="text-muted">All Event Registrations</span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>S.No.</th>
              <th>Name</th>
              <th>Email</th>
              <th>Event</th>
              <th>Event Date</th>
              <th>Registration Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $select_registrations = mysqli_query($conn, "SELECT * FROM event_registrations ORDER BY created_at DESC");
            $sn_reg = 1;
            while($row_reg = mysqli_fetch_array($select_registrations))
            {
            ?>
            <tr>
              <td><?php echo $sn_reg; ?></td>
              <td><?php echo htmlspecialchars($row_reg['name']); ?></td>
              <td><?php echo htmlspecialchars($row_reg['email']); ?></td>
              <td><?php echo htmlspecialchars($row_reg['event']); ?></td>
              <td><?php echo !empty($row_reg['event_date']) ? date('M j, Y', strtotime($row_reg['event_date'])) : 'N/A'; ?></td>
              <td><?php echo date('M j, Y H:i', strtotime($row_reg['created_at'])); ?></td>
              <td>
                <div class="d-flex gap-2">
                  <form method="POST" action="manage-visitors.php" onsubmit="return confirmDeleteRegistration()">
                      <input type="hidden" name="delete_reg_id" value="<?php echo $row_reg['id']; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="fa-solid fa-trash me-1"></i>Delete
                      </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php $sn_reg++; } ?>
            <?php if($sn_reg == 1): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                <i class="fa-solid fa-calendar-plus fa-2x mb-2"></i>
                <p>No event registrations found.</p>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php
// Handle registration deletion
if(isset($_POST['delete_reg_id'])){
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    $reg_id = filter_input(INPUT_POST, 'delete_reg_id', FILTER_VALIDATE_INT);
    if (!$reg_id) {
        die("Invalid registration ID");
    }
    $stmt = $conn->prepare("DELETE FROM event_registrations WHERE id=?");
    $stmt->bind_param("i", $reg_id);
    if($stmt->execute()){
        echo "<script>alert('Registration deleted successfully');</script>";
        echo "<script>window.location.href='manage-visitors.php';</script>";
    } else {
        echo "<script>alert('Error deleting registration: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}
?>

<?php include('include/footer.php'); ?>
<script>
function confirmDelete(){
    return confirm('Are you sure want to delete this Visitor?');
}
function confirmDeleteRegistration(){
    return confirm('Are you sure want to delete this event registration?');
}
</script>