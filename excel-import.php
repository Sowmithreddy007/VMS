<?php
session_start();
include 'connection.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$name = $_SESSION['name'];
$id = $_SESSION['id'];
if(empty($id)) {
    header("Location: index.php");
    exit();
}

$message = '';
$message_type = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $event_id = intval($_POST['event_id']);
    $file = $_FILES['excel_file'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['xlsx', 'xls', 'csv'];
        
        if (in_array($file_ext, $allowed_ext)) {
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();
                
                $imported_count = 0;
                $failed_count = 0;
                
                // Skip header row (assuming first row is headers)
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    
                    // Map columns (adjust based on your Excel structure)
                    $name = $row[0] ?? '';
                    $email = $row[1] ?? '';
                    $mobile = $row[2] ?? '';
                    $address = $row[3] ?? '';
                    $department = $row[4] ?? '';
                    $gender = $row[5] ?? '';
                    $year_of_graduation = $row[6] ?? '';
                    
                    if (!empty($name)) {
                        // Insert into excel imports table
                        $sql = "INSERT INTO tbl_excel_imports (event_id, name, email, mobile, address, department, gender, year_of_graduation, excel_file_name, import_status) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("issssssss", $event_id, $name, $email, $mobile, $address, $department, $gender, $year_of_graduation, $file['name']);
                        
                        if ($stmt->execute()) {
                            $imported_count++;
                        } else {
                            $failed_count++;
                        }
                        $stmt->close();
                    }
                }
                
                $message = "Excel file imported successfully. Imported: $imported_count, Failed: $failed_count";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "Error processing Excel file: " . $e->getMessage();
                $message_type = 'error';
            }
        } else {
            $message = "Invalid file type. Please upload Excel or CSV files.";
            $message_type = 'error';
        }
    } else {
        $message = "File upload error.";
        $message_type = 'error';
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
            <span class="chip"><i class="fa-solid fa-file-excel text-primary"></i> Excel Import</span>
            <h2>📊 Import Visitors from Excel</h2>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" onclick="location.href='manage-visitors.php'">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Visitors
            </button>
        </div>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type === 'error' ? 'danger' : 'success'; ?> mb-3">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="card-lite mb-4">
        <div class="card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-upload text-primary"></i>
                <span class="fw-semibold">Upload Excel File</span>
            </div>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Select Event</label>
                    <select class="form-control" name="event_id" required>
                        <option value="">Select Event</option>
                        <?php while($event = mysqli_fetch_assoc($events)): ?>
                        <option value="<?php echo $event['event_id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Excel File</label>
                    <input type="file" class="form-control" name="excel_file" accept=".xlsx,.xls,.csv" required>
                    <div class="form-text">Supported formats: .xlsx, .xls, .csv</div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-upload me-2"></i>Upload and Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Excel Format Guide -->
    <div class="card-lite">
        <div class="card-head">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-info-circle text-info"></i>
                <span class="fw-semibold">Excel Format Guide</span>
            </div>
        </div>
        <div class="card-body">
            <p>Your Excel file should have the following columns in order:</p>
            <ol>
                <li>Name (Required)</li>
                <li>Email</li>
                <li>Mobile</li>
                <li>Address</li>
                <li>Department</li>
                <li>Gender (Male/Female/Other)</li>
                <li>Year of Graduation</li>
            </ol>
            <p class="text-muted">The first row should be headers. Empty rows will be skipped.</p>
        </div>
    </div>
</div>

<?php include('include/footer.php'); ?>