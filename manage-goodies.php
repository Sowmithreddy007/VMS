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

$breadcrumbs = [
    ['url' => 'admin_dashboard.php', 'text' => 'Dashboard'],
    ['text' => 'Manage Goodies']
];
?>
<?php include('include/header.php'); ?>
<?php include('include/top-bar.php'); ?>

<!-- Content -->
<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="title-row">
      <span class="chip"><i class="fa-solid fa-truck text-primary"></i> Goodies</span>
      <h2>🎁 Manage Goodies Distribution</h2>
      <span class="badge">Live Data</span>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" onclick="location.href='add_goodie.php'"><i class="fa-solid fa-plus me-2"></i>Add Distribution</button>
      <button class="btn btn-outline-primary" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-2"></i>Refresh</button>
    </div>
  </div>

  <!-- Goodies Table -->
  <div class="card-lite">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-gift text-primary"></i>
        <span class="fw-semibold">Goodies Distribution List</span>
      </div>
      <span class="text-muted">All Goodies Distribution Records</span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>S.No.</th>
              <th>Visitor Name</th>
              <th>Goodie Type</th>
              <th>Quantity</th>
              <th>Distributed By</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if(isset($_GET['ids'])){
              $id = $_GET['ids'];
              // Validate CSRF token
              if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                  die("Invalid CSRF token");
              }
              
              $stmt = $conn->prepare("DELETE FROM tbl_goodies_distribution WHERE id = ?");
              $stmt->bind_param("i", $id);
              $delete_query = $stmt->execute();
              if($delete_query) {
                echo "<script>alert('Goodie distribution record deleted successfully');</script>";
              }
            }
            
            // Secure goodies listing query
            $sql = "SELECT gd.id, gd.visitor_id, gd.goodie_name, gd.quantity, "
                 . "gd.distributed_by, gd.created_at, v.name AS visitor_name "
                 . "FROM tbl_goodies_distribution gd "
                 . "LEFT JOIN tbl_visitors v ON gd.visitor_id = v.id "
                 . "ORDER BY gd.created_at DESC";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Database error: " . htmlspecialchars($conn->error));
            }
            
            if (!$stmt->execute()) {
                die("Execution failed: " . htmlspecialchars($stmt->error));
            }
            
            $select_query = $stmt->get_result();
            $stmt->close();
            $stmt->execute();
            $select_query = $stmt->get_result();
                                               FROM tbl_goodies_distribution gd 
                                               LEFT JOIN tbl_visitors v ON gd.visitor_id = v.id 
                                               ORDER BY gd.created_at DESC");
            $sn = 1;
            while($row = mysqli_fetch_array($select_query))
            {
            ?>
            <tr>
              <td><?php echo $sn; ?></td>
              <td><?php echo htmlspecialchars($row['visitor_name']); ?></td>
              <td><?php echo htmlspecialchars($row['goodie_name']); ?></td>
              <td><?php echo htmlspecialchars($row['quantity']); ?></td>
              <td><?php echo htmlspecialchars($row['distributed_by']); ?></td>
              <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
              <td>
                <div class="d-flex gap-2">
                  <a href="edit-goodie.php?id=<?php echo $row['id'];?>" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-pencil me-1"></i>Edit
                  </a>
                  <a href="manage-goodies.php?ids=<?php echo $row['id'];?>" class="btn btn-sm btn-outline-danger" onclick="return confirmDelete()">
                    <i class="fa-solid fa-trash me-1"></i>Delete
                  </a>
                </div>
              </td>
            </tr>
            <?php $sn++; } ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="muted">Showing all goodies distribution records</span>
        <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download me-1"></i>Export</button>
      </div>
    </div>
  </div>
</div>

<?php include('include/footer.php'); ?>
<script>
function confirmDelete(){
    return confirm('Are you sure want to delete this goodie distribution record?');
}
</script>