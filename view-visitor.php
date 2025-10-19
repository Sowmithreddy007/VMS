<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

$event_id = $_GET['event_id'] ?? 0;

// Fetch event name
$stmt_event = $conn->prepare("SELECT event_name FROM tbl_events WHERE event_id = ?");
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$event_result = $stmt_event->get_result();
$event = $event_result->fetch_assoc();
$event_name = $event['event_name'] ?? "Unknown Event";
$stmt_event->close();

// Fetch visitors
$stmt_visitors = $conn->prepare("SELECT * FROM tbl_visitors WHERE event_id = ?");
$stmt_visitors->bind_param("i", $event_id);
$stmt_visitors->execute();
$visitors = $stmt_visitors->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Visitors for <?php echo htmlspecialchars($event_name); ?></title>
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
  <?php include 'Admin_dashbaord/side-bar.php'; ?>

  <div class="content-wrapper p-4">
    <h3>Visitors for: <?php echo htmlspecialchars($event_name); ?></h3>

    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>#</th>
          <th>Visitor Name</th>
          <th>Email</th>
          <th>Mobile</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $i = 1;
        while ($row = mysqli_fetch_assoc($visitors)) {
            echo "<tr>
                    <td>{$i}</td>
                    <td>".htmlspecialchars($row['name'])."</td>
                    <td>".htmlspecialchars($row['email'])."</td>
                    <td>".htmlspecialchars($row['mobile'])."</td>
                  </tr>";
            $i++;
        }
        $stmt_visitors->close();
        ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
