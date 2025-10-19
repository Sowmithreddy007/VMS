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
    ['text' => 'Manage Inventory']
];
?>
<?php include('include/header.php'); ?>
<?php include('include/top-bar.php'); ?>

<!-- Content -->
<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <div class="title-row">
      <span class="chip"><i class="fa-solid fa-warehouse text-primary"></i> Inventory</span>
      <h2>📦 Manage Inventory</h2>
      <span class="badge">Live Data</span>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" onclick="location.href='add_inventory.php'"><i class="fa-solid fa-plus me-2"></i>Add Item</button>
      <button class="btn btn-outline-primary" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-2"></i>Refresh</button>
    </div>
  </div>

  <!-- Filter Form -->
  <div class="card-lite mb-3">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-filter text-primary"></i>
        <span class="fw-semibold">Filter Inventory</span>
      </div>
      <span class="text-muted">Refine your inventory list</span>
    </div>
    <div class="card-body">
      <form method="GET" action="">
        <div class="row g-3">
          <div class="col-md-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
              <option value="">All Status</option>
              <option value="Available" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>
              <option value="Not Available" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Not Available') ? 'selected' : ''; ?>>Not Available</option>
            </select>
          </div>
          <div class="col-md-3">
            <label for="item_name" class="form-label">Item Name</label>
            <input type="text" class="form-control" id="item_name" name="item_name" placeholder="Search item name" value="<?php echo isset($_GET['item_name']) ? htmlspecialchars($_GET['item_name']) : ''; ?>">
          </div>
          <div class="col-md-3">
            <label for="stock_level" class="form-label">Stock Level</label>
            <select class="form-select" id="stock_level" name="stock_level">
              <option value="">All Stock</option>
              <option value="low" <?php echo (isset($_GET['stock_level']) && $_GET['stock_level'] == 'low') ? 'selected' : ''; ?>>Low Stock (< 10)</option>
              <option value="medium" <?php echo (isset($_GET['stock_level']) && $_GET['stock_level'] == 'medium') ? 'selected' : ''; ?>>Medium Stock (10-50)</option>
              <option value="high" <?php echo (isset($_GET['stock_level']) && $_GET['stock_level'] == 'high') ? 'selected' : ''; ?>>High Stock (> 50)</option>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary me-2"><i class="fa-solid fa-filter me-1"></i>Apply Filters</button>
            <a href="manage-inventory.php" class="btn btn-outline-secondary"><i class="fa-solid fa-times me-1"></i>Clear</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Inventory Table -->
  <div class="card-lite">
    <div class="card-head">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid fa-boxes text-primary"></i>
        <span class="fw-semibold">Inventory List</span>
      </div>
      <span class="text-muted">Filtered Inventory Items</span>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>S.No.</th>
              <th>Item Name</th>
              <th>Total Stock</th>
              <th>Used Count</th>
              <th>Status</th>
              <th>Last Updated</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
                if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                    die("CSRF token validation failed");
                }
                $id = intval($_POST['delete_id']);
                $stmt = $conn->prepare("DELETE FROM tbl_inventory WHERE id = ?");
                $stmt->bind_param("i", $id);
                if($stmt->execute()) {
                    echo "<script>alert('Inventory item deleted successfully');</script>";
                } else {
                    echo "<script>alert('Error deleting item: " . $stmt->error . "');</script>";
                }
                $stmt->close();
            }
            
            // Build the base query
            $sql = "SELECT * FROM tbl_inventory WHERE 1=1";
            $params = [];

            // Apply status filter
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $status = mysqli_real_escape_string($conn, $_GET['status']);
                $sql .= " AND status = '$status'";
            }

            // Apply item name filter
            if (isset($_GET['item_name']) && !empty($_GET['item_name'])) {
                $item_name = mysqli_real_escape_string($conn, $_GET['item_name']);
                $sql .= " AND item_name LIKE '%$item_name%'";
            }

            // Apply stock level filter
            if (isset($_GET['stock_level']) && !empty($_GET['stock_level'])) {
                $stock_level = $_GET['stock_level'];
                if ($stock_level == 'low') {
                    $sql .= " AND total_stock < 10";
                } elseif ($stock_level == 'medium') {
                    $sql .= " AND total_stock BETWEEN 10 AND 50";
                } elseif ($stock_level == 'high') {
                    $sql .= " AND total_stock > 50";
                }
            }

            $sql .= " ORDER BY created_at DESC";
            $select_query = mysqli_query($conn, $sql);
            $sn = 1;
            while($row = mysqli_fetch_array($select_query))
            {
            ?>
            <tr>
              <td><?php echo $sn; ?></td>
              <td><?php echo htmlspecialchars($row['item_name']); ?></td>
              <td><?php echo htmlspecialchars($row['total_stock']); ?></td>
              <td><?php echo htmlspecialchars($row['used_count']); ?></td>
              <td>
                <span class="badge <?php echo $row['status'] == 'Available' ? 'text-bg-success-subtle text-success border border-success' : 'text-bg-warning-subtle text-warning border border-warning'; ?>">
                  <?php echo htmlspecialchars($row['status']); ?>
                </span>
              </td>
              <td><?php echo date('M d, Y', strtotime($row['updated_at'])); ?></td>
              <td>
                <div class="d-flex gap-2">
                  <a href="edit-inventory.php?id=<?php echo $row['id'];?>" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-pencil me-1"></i>Edit
                  </a>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirmDelete()">
                      <i class="fa-solid fa-trash me-1"></i>Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php $sn++; } ?>
          </tbody>
        </table>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="muted">
          <?php
          $total_items = mysqli_num_rows($select_query);
          echo "Showing " . $total_items . " inventory item" . ($total_items != 1 ? 's' : '');
          if (isset($_GET['status']) || isset($_GET['item_name']) || isset($_GET['stock_level'])) {
              echo " (filtered)";
          }
          ?>
        </span>
        <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-download me-1"></i>Export</button>
      </div>
    </div>
  </div>
</div>

<?php include('include/footer.php'); ?>
<script>
function confirmDelete(){
    return confirm('Are you sure want to delete this inventory item?');
}
</script>