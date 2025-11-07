<?php
session_start();
include 'connection.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$message_type = '';

// Fetch events for dropdown
$events_query = "SELECT event_id, event_name, event_date FROM tbl_events ORDER BY event_date DESC";
$events_result = mysqli_query($conn, $events_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['excel_file']) && isset($_POST['event_id'])) {
        $event_id = intval($_POST['event_id']);
        
        // Validate event_id
        $event_check = mysqli_query($conn, "SELECT event_id FROM tbl_events WHERE event_id = $event_id");
        if (mysqli_num_rows($event_check) === 0) {
            $message = "Invalid event selected.";
            $message_type = 'danger';
        } else {
            $file = $_FILES['excel_file'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_ext, ['xlsx', 'xls'])) {
                try {
                    $spreadsheet = IOFactory::load($file['tmp_name']);
                    $worksheet = $spreadsheet->getActiveSheet();
                    $rows = $worksheet->toArray();
                    
                    // Remove header row
                    array_shift($rows);
                    
                    $success_count = 0;
                    $error_count = 0;
                    $errors = [];
                    
                    foreach ($rows as $index => $row) {
                        // Skip empty rows
                        if (empty(array_filter($row))) continue;
                        
                        $name = trim($row[0] ?? '');
                        $email = trim($row[1] ?? '');
                        $mobile = trim($row[2] ?? '');
                        $address = trim($row[3] ?? '');
                        $department = trim($row[4] ?? '');
                        $gender = trim($row[5] ?? '');
                        $year = trim($row[6] ?? '');
                        
                        // Validate required fields
                        if (empty($name) || empty($email) || empty($mobile)) {
                            $error_count++;
                            $errors[] = "Row " . ($index + 2) . ": Missing required fields (name, email, or mobile)";
                            continue;
                        }
                        
                        // Validate email format
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $error_count++;
                            $errors[] = "Row " . ($index + 2) . ": Invalid email format";
                            continue;
                        }
                        
                        // Prepare and execute insert query
                        $stmt = $conn->prepare("INSERT INTO tbl_visitors (event_id, name, email, mobile, address, department, gender, year_of_graduation, added_by, visitor_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'regular')");
                        
                        if ($stmt) {
                            $stmt->bind_param("isssssssi", 
                                $event_id, $name, $email, $mobile, $address, 
                                $department, $gender, $year, $_SESSION['id']
                            );
                            
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                                $errors[] = "Row " . ($index + 2) . ": " . $stmt->error;
                            }
                            $stmt->close();
                        }
                    }
                    
                    $message = "Import completed. Successfully imported: $success_count, Failed: $error_count";
                    $message_type = $error_count === 0 ? 'success' : 'warning';
                    
                } catch (Exception $e) {
                    $message = "Error processing file: " . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = "Invalid file type. Please upload XLSX or XLS files only.";
                $message_type = 'danger';
            }
        }
    } else {
        $message = "Please select both an event and a file.";
        $message_type = 'warning';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Visitors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .upload-zone {
            border: 2px dashed #dee2e6;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-zone:hover {
            border-color: #0d6efd;
            background: #f1f8ff;
        }
        .template-box {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'include/header.php'; ?>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Import Visitors</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo $message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-info">
                                    <h6>Error Details:</h6>
                                    <ul class="mb-0">
                                        <?php foreach($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="template-box">
                            <h6><i class="fas fa-file-download me-2"></i>Download Template</h6>
                            <p class="mb-2 small">Start with our template to ensure proper formatting:</p>
                            <a href="download-template.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-download me-2"></i>Download Excel Template
                            </a>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="event_id" class="form-label">Select Event <span class="text-danger">*</span></label>
                                <select class="form-select" name="event_id" id="event_id" required>
                                    <option value="">Choose an event...</option>
                                    <?php while ($event = mysqli_fetch_assoc($events_result)): ?>
                                        <option value="<?php echo $event['event_id']; ?>">
                                            <?php echo htmlspecialchars($event['event_name']); ?> 
                                            (<?php echo date('Y-m-d', strtotime($event['event_date'])); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">Please select an event.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Upload Excel File <span class="text-danger">*</span></label>
                                <div class="upload-zone" onclick="document.getElementById('excel_file').click();">
                                    <i class="fas fa-file-excel fa-2x text-primary mb-2"></i>
                                    <p class="mb-0">Click to upload or drag and drop</p>
                                    <p class="small text-muted mb-0">XLSX or XLS files only</p>
                                </div>
                                <input type="file" class="form-control d-none" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                <div class="invalid-feedback">Please select an Excel file.</div>
                                <div id="file-name" class="form-text"></div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Import Visitors
                                </button>
                                <a href="manage-visitors.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Visitors
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'include/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();

        // File input handling
        document.getElementById('excel_file').addEventListener('change', function(e) {
            var fileName = e.target.files[0]?.name || 'No file selected';
            document.getElementById('file-name').textContent = fileName;
        });

        // Drag and drop
        const dropZone = document.querySelector('.upload-zone');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropZone.classList.add('border-primary', 'bg-light');
        }

        function unhighlight(e) {
            dropZone.classList.remove('border-primary', 'bg-light');
        }

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            document.getElementById('excel_file').files = files;
            document.getElementById('file-name').textContent = files[0]?.name || 'No file selected';
        }
    </script>
</body>
</html>