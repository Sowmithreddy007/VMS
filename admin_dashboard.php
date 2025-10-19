<?php
session_start();
include 'connection.php';
$name = $_SESSION['name'];
$id = $_SESSION['id'];
if (empty($id)) {
    header("Location: index.php");
    exit();
}

// ================= Dashboard Stats =================
$total_visitors = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_visitors"));
$checked_in = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM tbl_visitors WHERE status=1 AND in_time IS NOT NULL"));
$goodies_given = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(quantity) FROM tbl_goodies_distribution"));
$event_participants = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(participant_count) FROM tbl_event_participation"));

// ================= Visitor Data =================
$visitors = mysqli_query($conn, "SELECT * FROM tbl_visitors ORDER BY created_at DESC LIMIT 10");

// ================= Inventory Data =================
$inventory = mysqli_query($conn, "SELECT * FROM tbl_inventory ORDER BY status, item_name");

// ================= Goodies Distribution =================
$goodies = mysqli_query($conn, "SELECT g.*, v.name as visitor_name FROM tbl_goodies_distribution g LEFT JOIN tbl_visitors v ON g.visitor_id = v.id ORDER BY g.distribution_time DESC LIMIT 10");

// ================= Event Participation =================
$participation = mysqli_query($conn, "SELECT * FROM tbl_event_participation ORDER BY participant_count DESC");

// ================= Coordinator Notes =================
$notes_log = mysqli_query($conn, "SELECT * FROM tbl_coordinator_notes WHERE note_type='LOG' ORDER BY created_at DESC LIMIT 5");
$notes_actions = mysqli_query($conn, "SELECT * FROM tbl_coordinator_notes WHERE note_type='ACTION_ITEM' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>VMS Console — Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Fonts & Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet"/>

  <style>
    :root{
      --bg:#f6f7fb;
      --card:#ffffff;
      --ink:#1f2937;
      --ink-soft:#6b7280;
      --edge:#e5e7eb;
      --brand:#2563eb;
      --accent:#10b981;
      --warn:#f59e0b;
      --danger:#ef4444;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      background:var(--bg);
      color:var(--ink);
      font-family:'Poppins',sans-serif;
      font-size:15px;
    }

    /* Sidebar */
    .sidebar{
      position:fixed; inset:0 auto 0 0;
      width:260px; background:#0f172a; color:#fff;
      border-right:1px solid rgba(255,255,255,0.06);
      display:flex; flex-direction:column;
    }
    .brand{
      padding:18px 20px; border-bottom:1px solid rgba(255,255,255,0.08);
      display:flex; align-items:center; gap:10px; font-weight:600;
    }
    .brand .logo{display:inline-flex; width:34px; height:34px; border-radius:8px; background:#1d4ed8; align-items:center; justify-content:center}
    .nav{
      list-style:none; padding:10px 10px 24px; margin:0; overflow:auto;
    }
    .nav a{
      display:flex; gap:12px; align-items:center;
      padding:10px 12px; margin:4px 0; color:#cbd5e1; text-decoration:none;
      border-radius:8px; transition:all .15s ease;
    }
    .nav a:hover{background:rgba(255,255,255,0.06); color:#fff}
    .nav .section-label{
      font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:#94a3b8;
      padding:12px 12px 6px; margin-top:6px;
    }
    .nav .is-active{background:#1e293b; color:#fff}

    /* Main */
    .main{
      margin-left:260px; padding:22px;
      min-height:100vh;
    }

    /* Cards */
    .card-lite{
      background:var(--card); border:1px solid var(--edge);
      border-radius:14px; box-shadow:0 2px 10px rgba(16,24,40,.04);
    }
    .card-head{
      padding:16px 18px; border-bottom:1px solid var(--edge);
      display:flex; align-items:center; justify-content:space-between; gap:12px;
    }
    .card-body{ padding:16px 18px; }
    .chip{
      display:inline-flex; align-items:center; gap:8px;
      padding:6px 10px; border-radius:20px; font-size:12px; border:1px solid var(--edge); background:#fff;
    }
    .chip i{opacity:.85}

    /* Tables */
    .table thead th{color:#6b7280; font-weight:600; background:#fafafa}
    .table td, .table th{vertical-align:middle}
    .stat{
      display:flex; align-items:center; gap:10px;
      padding:14px; border-radius:14px; background:var(--card);
      border:1px solid var(--edge);
    }
    .stat i{font-size:18px}
    .muted{color:var(--ink-soft)}
    .btn-soft{
      border:1px solid var(--edge); background:#fff; color:var(--ink);
    }
    .btn-soft:hover{border-color:#cbd5e1; background:#f9fafb}

    /* Topbar */
    .topbar{
      display:flex; align-items:center; justify-content:space-between; gap:14px; margin-bottom:18px;
    }
    .crumbs{
      display:flex; align-items:center; gap:8px; color:var(--ink-soft); font-size:13px;
    }
    .crumbs a{ color:var(--ink-soft); text-decoration:none }
    .crumbs a:hover{ text-decoration:underline }
    .title-row{ display:flex; align-items:center; gap:12px; }
    .title-row h2{ margin:0; font-weight:600; font-size:22px; }
    .title-row .badge{
      background:#eef2ff; color:#4338ca; border:1px solid #e0e7ff;
      padding:6px 10px; border-radius:10px; font-weight:500;
    }

    /* Responsive */
    @media (max-width: 992px){
      .sidebar{transform:translateX(-100%); transition:transform .2s ease}
      .sidebar.show{transform:translateX(0)}
      .main{margin-left:0}
      .topbar .toggle{display:inline-flex}
    }
    @media (min-width: 993px){
      .topbar .toggle{display:none}
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <span class="logo"><i class="fa-solid fa-bullseye text-white"></i></span>
      <span>VMS Console</span>
    </div>
    <ul class="nav">
      <li><a href="admin_dashboard.php" class="is-active"><i class="fa-solid fa-house"></i><span>Admin Dashboard</span></a></li>
      <li><a href="new-visitor.php"><i class="fa-solid fa-user-plus"></i><span>New Visitor</span></a></li>
      <li><a href="manage-visitors.php"><i class="fa-solid fa-users-gear"></i><span>Manage Visitors</span></a></li>

      <li class="section-label">Event dashboards</li>
      <li><a href="#"><i class="fa-solid fa-scroll"></i><span>Nostalgia</span></a></li>
      <li><a href="#"><i class="fa-solid fa-microphone-lines"></i><span>Alumni Talks</span></a></li>
      <li><a href="#"><i class="fa-solid fa-graduation-cap"></i><span>Induction Program</span></a></li>
      <li><a href="#"><i class="fa-solid fa-briefcase"></i><span>Mock Interviews</span></a></li>
    </ul>
  </aside>

  <!-- Main -->
  <main class="main">
    <!-- Topbar -->
    <div class="topbar">
      <button class="btn btn-soft toggle" id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>
      <div class="crumbs">
        <a href="admin_dashboard.php">Dashboard</a>
        <span>›</span>
        <span>Admin Dashboard</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted">Welcome, <?php echo $name; ?></span>
        <button class="btn btn-soft"><i class="fa-regular fa-bell"></i></button>
        <button class="btn btn-soft"><i class="fa-regular fa-circle-question"></i></button>
        <a href="logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i></a>
      </div>
    </div>

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div class="title-row">
        <span class="chip"><i class="fa-solid fa-tachometer-alt text-primary"></i> Dashboard</span>
        <h2>📊 VMS — Admin Dashboard</h2>
        <span class="badge">Live Data</span>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary" onclick="exportData()"><i class="fa-solid fa-download me-2"></i>Export CSV</button>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filtersModal"><i class="fa-solid fa-sliders me-2"></i>Filters</button>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEventModal"><i class="fa-solid fa-calendar-plus me-2"></i>Create Event</button>
      </div>
    </div>

    <!-- Quick stats -->
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="stat">
          <i class="fa-solid fa-users text-primary"></i>
          <div>
            <div class="fw-semibold">Total Visitors</div>
            <div class="muted"><?php echo $total_visitors[0]; ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat">
          <i class="fa-solid fa-door-open text-success"></i>
          <div>
            <div class="fw-semibold">Checked In</div>
            <div class="muted"><?php echo $checked_in[0]; ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat">
          <i class="fa-solid fa-gift text-warning"></i>
          <div>
            <div class="fw-semibold">Goodies Given</div>
            <div class="muted"><?php echo $goodies_given[0] ?: '0'; ?></div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat">
          <i class="fa-solid fa-clipboard-check text-danger"></i>
          <div>
            <div class="fw-semibold">Participants</div>
            <div class="muted"><?php echo $event_participants[0]; ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Content grid -->
    <div class="row g-3">
      <!-- Visitor In/Out -->
      <div class="col-12 col-lg-7">
        <div class="card-lite">
          <div class="card-head">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-right-left text-primary"></i>
              <span class="fw-semibold">Visitor In/Out</span>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-sm btn-soft" onclick="location.href='new-visitor.php'"><i class="fa-solid fa-plus me-1"></i>Add</button>
              <button class="btn btn-sm btn-soft" onclick="location.reload()"><i class="fa-solid fa-arrow-rotate-right me-1"></i>Refresh</button>
            </div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Department</th>
                    <th>In Time</th>
                    <th>Out Time</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($row = mysqli_fetch_assoc($visitors)) { ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['in_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['out_time'] ?: '—'); ?></td>
                    <td>
                      <span class="badge <?php echo $row['out_time'] ? 'text-bg-success-subtle text-success border border-success' : 'text-bg-primary-subtle text-primary border border-primary'; ?>">
                        <?php echo $row['out_time'] ? 'Checked Out' : 'Checked In'; ?>
                      </span>
                    </td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
              <span class="muted">Showing recent visitors</span>
              <a href="manage-visitors.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Inventory -->
      <div class="col-12 col-lg-5">
        <div class="card-lite h-100">
          <div class="card-head">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-boxes-stacked text-success"></i>
              <span class="fw-semibold">Inventory</span>
            </div>
            <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#addInventoryModal"><i class="fa-solid fa-plus me-1"></i>Add Item</button>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>In Stock</th>
                    <th>Used</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($item = mysqli_fetch_assoc($inventory)) { 
                    $remaining = $item['total_stock'] - $item['used_count'];
                    $status_class = '';
                    if ($remaining <= 0) {
                      $status_class = 'text-bg-danger-subtle text-danger border border-danger';
                    } elseif ($remaining <= 10) {
                      $status_class = 'text-bg-warning-subtle text-warning border border-warning';
                    } else {
                      $status_class = 'text-bg-success-subtle text-success border border-success';
                    }
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo $item['total_stock']; ?></td>
                    <td><?php echo $item['used_count']; ?></td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo $item['status']; ?></span></td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <div class="d-flex gap-2 mt-2">
              <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#restockModal"><i class="fa-solid fa-arrow-up-short-wide me-1"></i>Restock</button>
              <button class="btn btn-sm btn-outline-secondary"><i class="fa-regular fa-file-lines me-1"></i>View Log</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Goodies -->
      <div class="col-12 col-lg-7">
        <div class="card-lite">
          <div class="card-head">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-gifts text-warning"></i>
              <span class="fw-semibold">Goodies Distribution</span>
            </div>
            <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#addGoodieModal"><i class="fa-solid fa-plus me-1"></i>Record</button>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Goodie</th>
                    <th>Qty</th>
                    <th>Time</th>
                    <th>Remarks</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($goodie = mysqli_fetch_assoc($goodies)) { ?>
                  <tr>
                    <td><?php echo htmlspecialchars($goodie['visitor_name'] ?: 'Unknown'); ?></td>
                    <td><?php echo htmlspecialchars($goodie['goodie_name']); ?></td>
                    <td><?php echo $goodie['quantity']; ?></td>
                    <td><?php echo date('H:i', strtotime($goodie['distribution_time'])); ?></td>
                    <td><?php echo htmlspecialchars($goodie['remarks'] ?: '—'); ?></td>
                  </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <div class="d-flex justify-content-end">
              <button class="btn btn-sm btn-outline-primary"><i class="fa-regular fa-clipboard me-1"></i>Summary</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Participation -->
      <div class="col-12 col-lg-5">
        <div class="card-lite">
          <div class="card-head">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-solid fa-clipboard-list text-danger"></i>
              <span class="fw-semibold">Event Participation</span>
            </div>
            <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#addParticipationModal"><i class="fa-solid fa-user-plus me-1"></i>Add</button>
          </div>
          <div class="card-body">
            <ul class="list-group list-group-flush">
              <?php while($activity = mysqli_fetch_assoc($participation)) { ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <?php echo htmlspecialchars($activity['activity_name']); ?>
                <span class="badge rounded-pill text-bg-primary"><?php echo $activity['participant_count']; ?></span>
              </li>
              <?php } ?>
            </ul>
            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-filter me-1"></i>Filter</button>
              <button class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-share-nodes me-1"></i>Share</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes / Actions -->
      <div class="col-12">
        <div class="card-lite">
          <div class="card-head">
            <div class="d-flex align-items-center gap-2">
              <i class="fa-regular fa-note-sticky text-secondary"></i>
              <span class="fw-semibold">Coordinator Notes</span>
            </div>
            <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#addNoteModal"><i class="fa-regular fa-pen-to-square me-1"></i>Add Note</button>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="p-3 border rounded-3 bg-white">
                  <div class="small text-uppercase text-muted mb-1">Log</div>
                  <ul class="mb-0">
                    <?php while($note = mysqli_fetch_assoc($notes_log)) { ?>
                    <li><?php echo htmlspecialchars($note['content']); ?></li>
                    <?php } ?>
                  </ul>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="p-3 border rounded-3 bg-white">
                  <div class="small text-uppercase text-muted mb-1">Action items</div>
                  <ul class="mb-0">
                    <?php while($note = mysqli_fetch_assoc($notes_actions)) { ?>
                    <li><?php echo htmlspecialchars($note['content']); ?></li>
                    <?php } ?>
                  </ul>
                </div>
              </div>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-3">
              <button class="btn btn-outline-secondary"><i class="fa-regular fa-eye me-1"></i>Preview</button>
              <button class="btn btn-primary"><i class="fa-regular fa-floppy-disk me-1"></i>Save Snapshot</button>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /grid -->
  </main>

  <!-- Modals -->
  <div class="modal fade" id="addInventoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Inventory Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="inventoryForm">
            <div class="mb-3">
              <label class="form-label">Item Name</label>
              <input type="text" class="form-control" name="item_name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Initial Stock</label>
              <input type="number" class="form-control" name="total_stock" min="0" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="addInventoryItem()">Add Item</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Goodies Distribution Modal -->
  <div class="modal fade" id="addGoodieModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Record Goodie Distribution</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="goodieForm">
            <div class="mb-3">
              <label class="form-label">Visitor ID</label>
              <input type="number" class="form-control" name="visitor_id" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Goodie Name</label>
              <input type="text" class="form-control" name="goodie_name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Quantity</label>
              <input type="number" class="form-control" name="quantity" min="1" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Remarks</label>
              <textarea class="form-control" name="remarks" rows="2"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="addGoodieItem()">Record Distribution</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Event Participation Modal -->
  <div class="modal fade" id="addParticipationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Event Participation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="participationForm">
            <div class="mb-3">
              <label class="form-label">Activity Name</label>
              <input type="text" class="form-control" name="activity_name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Participant Count</label>
              <input type="number" class="form-control" name="participant_count" min="1" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="addParticipation()">Add Participation</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Coordinator Notes Modal -->
  <div class="modal fade" id="addNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add Coordinator Note</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="noteForm">
            <div class="mb-3">
              <label class="form-label">Note Type</label>
              <select class="form-select" name="note_type" required>
                <option value="LOG">Log Entry</option>
                <option value="ACTION_ITEM">Action Item</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Content</label>
              <textarea class="form-control" name="content" rows="3" required></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="addNote()">Add Note</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Event Modal -->
  <div class="modal fade" id="addEventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Create New Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="eventForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="mb-3">
              <label class="form-label">Event Name</label>
              <input type="text" class="form-control" name="event_name" required placeholder="Enter event name">
            </div>
            <div class="mb-3">
              <label class="form-label">Event Date</label>
              <input type="date" class="form-control" name="event_date" required>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="addEvent()">Create Event</button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Scripts -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('toggleSidebar');
    if (toggle) {
      toggle.addEventListener('click', () => {
        sidebar.classList.toggle('show');
      });
    }

    function exportData() {
      // Simple CSV export for visitor data
      fetch('export_visitors.php')
        .then(response => response.text())
        .then(csv => {
          const blob = new Blob([csv], { type: 'text/csv' });
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = 'visitors_export.csv';
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          window.URL.revokeObjectURL(url);
        })
        .catch(error => {
          alert('Error exporting data: ' + error);
        });
    }

    function addInventoryItem() {
      const form = document.getElementById('inventoryForm');
      const formData = new FormData(form);
      
      fetch('add_inventory.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Inventory item added successfully!');
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error adding inventory item');
      });
    }

    function addGoodieItem() {
      const form = document.getElementById('goodieForm');
      const formData = new FormData(form);
      
      fetch('add_goodie.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Goodie distribution recorded successfully!');
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error recording goodie distribution');
      });
    }

    function addParticipation() {
      const form = document.getElementById('participationForm');
      const formData = new FormData(form);
      
      fetch('add_participation.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Participation recorded successfully!');
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error recording participation');
      });
    }

    function addNote() {
      const form = document.getElementById('noteForm');
      const formData = new FormData(form);
      
      fetch('add_note.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Note added successfully!');
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error adding note');
      });
    }

    function addEvent() {
      const form = document.getElementById('eventForm');
      const formData = new FormData(form);
      
      fetch('add_event.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Event created successfully!');
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error creating event');
      });
    }
  </script>
</body>
</html>
