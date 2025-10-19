<?php
session_start();
include('connection.php');

// Initialize variables
$popup_message = '';
$popup_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sbt-vstr'])) {
    
    // Validate CSRF token first
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $popup_message = 'Invalid CSRF token. Please refresh the page and try again.';
        $popup_type = 'danger';
    } else {
        // Retrieve and sanitize form data
        $fullname = htmlspecialchars($_POST['fullname']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $mobile = htmlspecialchars($_POST['mobile']);
        $address = htmlspecialchars($_POST['address']);
        $department = htmlspecialchars($_POST['department']);
        $gender = htmlspecialchars($_POST['gender']);
        $year = htmlspecialchars($_POST['year_of_graduation']);
        $event_id = (int)$_POST['event_id'];
        
        // Validate required fields
        if (empty($fullname) || empty($email) || empty($mobile)) {
            $popup_message = 'Please fill in all required fields.';
            $popup_type = 'warning';
        } else {
            // Insert into database using prepared statements
            $stmt = $conn->prepare("INSERT INTO tbl_visitors (event_id, name, email, mobile, address, department, gender, year_of_graduation, in_time, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
            
            if ($stmt) {
                $stmt->bind_param("isssssssis", $event_id, $fullname, $email, $mobile, $address, $department, $gender, $year, $_SESSION['id']);
                
                if ($stmt->execute()) {
                    $popup_message = "Visitor registered successfully!";
                    $popup_type = "success";
                    // Clear form on success
                    $_POST = array();
                } else {
                    $popup_message = "Error saving visitor: " . $stmt->error;
                    $popup_type = "danger";
                }
                $stmt->close();
            }
        }
    }
}
?>

<!-- HTML Form -->
<div class="container">
    <!-- Add CSS link here -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/sb-admin.min.css">
    
    <div class="row g-4">
        <div class="col-12">
            <h2 class="text-primary mb-4">Visitor Registration</h2>
            
            <?php if ($popup_message): ?>
                <div class="alert alert-<?php echo $popup_type; ?> mt-3">
                    <?php echo $popup_message; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <form method="post" class="needs-validation" novalidate>
        <!-- Hidden CSRF token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <!-- Form Fields -->
        <div class="row g-4">
            <!-- Full Name -->
            <div class="col-12 col-md-6">
                <div class="form-floating">
                    <input type="text" name="fullname" class="form-control" required>
                    <label>Full Name <span class="text-danger">*</span></label>
                </div>
            </div>
            
            <!-- Email -->
            <div class="col-12 col-md-6">
                <div class="form-floating">
                    <input type="email" name="email" class="form-control" required>
                    <label>Email <span class="text-danger">*</span></label>
                </div>
            </div>
            
            <!-- Mobile -->
            <div class="col-12 col-md-6">
                <div class="form-floating">
                    <input type="tel" name="mobile" class="form-control" placeholder="+91 9876543210" required>
                    <label>Mobile <span class="text-danger">*</span></label>
                </div>
            </div>
            
            <!-- Address -->
            <div class="col-12">
                <div class="form-floating">
                    <textarea name="address" class="form-control" required></textarea>
                    <label>Address <span class="text-danger">*</span></label>
                </div>
            </div>
            
            <!-- Department -->
            <div class="col-12 col-md-6">
                <div class="form-floating">
                    <select name="department" class="form-control" required>
                        <?php // Department options looping code here ?>
                    </select>
                    <label>Department <span class="text-danger">*</span></label>
                </div>
            </div>
            
            <!-- Event -->
            <div class="col-12 col-md-6">
                <div class="form-floating">
                    <select name="event_id" class="form-control" required>
                        <?php // Event options looping code here ?>
                    </select>
                    <label>Event <span class="text-danger">*</span></label>
                </div>
            </div>
            
            <!-- Gender -->
            <div class="col-12 col-md-6">
                <div class="form-floating">
                    <select name="gender" class="form-control" required>
                        <?php // Gender options here ?>
                    </select>
                    <label>Gender <span class="text-danger">*</span></label>
                </div>
            </div>
            
            <!-- Year of Graduation -->
            <div class="col-12 col-md-6">
                <div class="form-floating">
                    <select name="year_of_graduation" class="form-control" required>
                        <?php // Year options here ?>
                    </select>
                    <label>Year of Graduation <span class="text-danger">*</span></label>
                </div>
            </div>
        </div>
        
        <!-- Submit Buttons -->
        <div class="col-12">
            <div class="d-flex justify-content-end gap-2">
                <button type="submit" name="sbt-vstr" class="btn btn-primary">Register Visitor</button>
                <button type="reset" class="btn btn-outline-secondary me-2">Clear Form</button>
            </div>
        </div>
    </form>
</div>
