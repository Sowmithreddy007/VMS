<?php
session_start();
include ('connection.php');
$name = $_SESSION['name'];
$id = $_SESSION['id'];

if(empty($id)) {
    header("Location: index.php"); 
    exit();
}

$id = $_GET['id'];
$fetch_query = mysqli_query($conn, "SELECT * FROM tbl_visitors WHERE id='$id'");
$row = mysqli_fetch_assoc($fetch_query);

$popup_message = '';
$popup_type = '';

// Handle Save / Update Visitor
if(isset($_POST['sv-vstr'])) {
    $fullname   = $_POST['fullname'];
    $email    = $_POST['email'];
    $mobile     = $_POST['mobile'];
    $address    = $_POST['address'];
    $department = $_POST['department'];
    $gender     = $_POST['gender'];
    $year       = $_POST['year_of_graduation'];
    $status     = $_POST['status'];

    $sql = "UPDATE tbl_visitors
            SET name=?, email=?, mobile=?, address=?, department=?, gender=?, year_of_graduation=?, status=?";
    $params = "sssssssi";
    $values = [$fullname, $email, $mobile, $address, $department, $gender, $year, $status];

    if($status == 0) {
        $sql .= ", out_time=NOW()";
    }
    $sql .= " WHERE id=?";
    $params .= "i";
    $values[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($params, ...$values);
    
    if($stmt->execute()) {
        $popup_message = "Visitor updated successfully!";
        $popup_type = "success";
    } else {
        $popup_message = "Error updating visitor: " . $stmt->error;
        $popup_type = "danger";
    }
    $stmt->close();
}
?>

<?php include('include/header.php'); ?>
<div id="wrapper">
<?php include('include/side-bar.php'); ?>

<div id="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs -->
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Edit Visitor</a></li>
        </ol>

        <div class="card mb-3">
            <div class="card-header"><i class="fa fa-info-circle"></i> Edit Details</div>
            <form method="post" class="form-valide">
                <div class="card-body">
                    <!-- Name -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Name</label>
                        <div class="col-lg-6">
                            <input type="text" name="fullname" class="form-control"
                                value="<?php echo htmlspecialchars($row['name']); ?>"
                                pattern="[A-Za-z ]{3,50}"
                                title="3-50 alphabetic characters" required>
                            <div class="invalid-feedback">Please enter a valid name (3-50 letters)</div>
                        </div>
                    </div>
                    <!-- Email -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Email</label>
                        <div class="col-lg-6">
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email']); ?>" required>
                        </div>
                    </div>
                    <!-- Mobile -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Mobile</label>
                        <div class="col-lg-6">
                            <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($row['mobile']); ?>" required>
                        </div>
                    </div>
                    <!-- Address -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Address</label>
                        <div class="col-lg-6">
                            <textarea name="address" class="form-control" required><?php echo htmlspecialchars($row['address']); ?></textarea>
                        </div>
                    </div>
                    <!-- Department -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Department</label>
                        <div class="col-lg-6">
                            <select name="department" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php
                                $select_department = mysqli_query($conn,"SELECT * FROM tbl_department WHERE status=1 ORDER BY department ASC");
                                while($dept = mysqli_fetch_assoc($select_department)){
                                    $selected = ($row['department'] == $dept['department']) ? 'selected' : '';
                                    echo "<option value='".$dept['department']."' ".$selected.">".$dept['department']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <!-- Gender -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Gender</label>
                        <div class="col-lg-6">
                            <select name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($row['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($row['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($row['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <!-- Year of Graduation -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Year of Graduation</label>
                        <div class="col-lg-6">
                            <select name="year_of_graduation" class="form-control" required>
                                <option value="">Select Year</option>
                                <?php for($y = 2007; $y <= date("Y"); $y++){
                                    $selected = ($row['year_of_graduation'] == $y) ? 'selected' : '';
                                    echo "<option value='$y' ".$selected.">$y</option>";
                                } ?>
                            </select>
                        </div>
                    </div>
                    <!-- In Time -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">In Time</label>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['in_time']); ?>" readonly>
                        </div>
                    </div>
                    <!-- Out Time -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Out Time</label>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['out_time'] ?: 'N/A'); ?>" readonly>
                        </div>
                    </div>
                    <!-- Status -->
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label">Status</label>
                        <div class="col-lg-6">
                            <select name="status" class="form-control" required>
                                <option value="1" <?php echo ($row['status'] == 1) ? 'selected' : ''; ?>>In</option>
                                <option value="0" <?php echo ($row['status'] == 0) ? 'selected' : ''; ?>>Out</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-lg-8 ml-auto">
                            <button type="submit" name="sv-vstr" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Popup -->
<?php if($popup_message != '') { ?>
<div class="modal fade" id="visitorPopup" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-<?php echo $popup_type; ?>">
      <div class="modal-body text-<?php echo $popup_type; ?>">
        <?php echo $popup_message; ?>
      </div>
      <div class="modal-footer">
        <a href="manage-visitors.php" class="btn btn-<?php echo $popup_type; ?>">OK</a>
      </div>
    </div>
  </div>
</div>
<script>
    var popup = new bootstrap.Modal(document.getElementById('visitorPopup'));
    popup.show();
</script>
<?php } ?>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>
<?php include('include/footer.php'); ?>
