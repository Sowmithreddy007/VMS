<?php
if (!isset($conn)) {
    include 'connection.php';
}

// Fetch events
$events_res = mysqli_query($conn, "SELECT event_id, event_name FROM tbl_events ORDER BY event_date DESC");
$has_events = ($events_res && mysqli_num_rows($events_res) > 0);
?>

<!-- Sidebar -->
<ul class="navbar-nav bg-dark sidebar sidebar-dark accordion" id="accordionSidebar">

  <!-- Admin Dashboard -->
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center" href="admin_dashboard.php">
      <i class="bi bi-speedometer2 me-2"></i>
      <span>Admin Dashboard</span>
    </a>
  </li>

  <!-- New Visitors -->
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center" href="new-visitor.php">
      <i class="bi bi-person-plus-fill me-2"></i>
      <span>New Visitor</span>
    </a>
  </li>

  <!-- Manage Visitors -->
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center" href="manage-visitors.php">
      <i class="bi bi-people-fill me-2"></i>
      <span>Manage Visitors</span>
    </a>
  </li>

  <!-- Manage Events (Dropdown) -->
  <li class="nav-item">
    <a class="nav-link d-flex align-items-center collapsed" href="#"
       data-bs-toggle="collapse" data-bs-target="#collapseEvents"
       aria-expanded="false" aria-controls="collapseEvents">
      <i class="bi bi-calendar-event-fill me-2"></i>
      <span>Manage Events</span>
    </a>
    <div id="collapseEvents" class="collapse <?php echo $has_events ? 'show' : ''; ?>" data-bs-parent="#accordionSidebar">
      <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
        <li>
          <a href="add-event.php" class="nav-link d-flex align-items-center ms-4">
            <i class="bi bi-plus-circle me-2"></i> Add New Event
          </a>
        </li>
        <?php
        if ($has_events) {
            while ($ev = mysqli_fetch_assoc($events_res)) {
                $id = (int)$ev['event_id'];
                $name = htmlspecialchars($ev['event_name']);
                echo "
                <li>
                  <a href='view-event.php?event_id={$id}' class='nav-link d-flex align-items-center ms-4'>
                    <i class='bi bi-pin-angle-fill me-2'></i> {$name}
                  </a>
                </li>";
            }
        }
        ?>
      </ul>
    </div>
  </li>

</ul>
