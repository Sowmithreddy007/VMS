<?php
session_start();
include 'connection.php';

// If not logged in → redirect to login
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Fetch session variables
$name = $_SESSION['name'];
$id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? 'member';

// Fetch member data from database
$member_query = mysqli_query($conn, "SELECT * FROM tbl_members WHERE id='$id'");
$member_data = mysqli_fetch_assoc($member_query);

// Fetch upcoming events
$upcoming_events = mysqli_query($conn, "SELECT * FROM tbl_events WHERE event_date >= CURDATE() ORDER BY event_date ASC");

// Fetch events for dropdowns
$events_array = [];
$events_result = mysqli_query($conn, "SELECT * FROM tbl_events WHERE event_date >= CURDATE() ORDER BY event_date ASC");
while ($event = mysqli_fetch_assoc($events_result)) {
    $events_array[] = $event;
}

// Fetch registered events for this member
$registered_events_query = mysqli_query($conn, "SELECT er.*, e.event_name, e.event_date
                                                FROM event_registrations er
                                                JOIN tbl_events e ON er.event = e.event_name
                                                WHERE er.user_id='$id'");
$registered_events = [];
while ($row = mysqli_fetch_assoc($registered_events_query)) {
    $registered_events[] = $row;
}

// Fetch announcements (could be from a new table or use coordinator notes)
$announcements = mysqli_query($conn, "SELECT * FROM tbl_coordinator_notes WHERE note_type='LOG' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>VMS Console — Member Dashboard</title>
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
      <span class="logo"><i class="fa-solid fa-user text-white"></i></span>
      <span>VMS Console</span>
    </div>
    <ul class="nav">
      <li><a href="member_dashboard.php" class="is-active"><i class="fa-solid fa-house"></i><span>Member Dashboard</span></a></li>
      <li><a href="#my-events"><i class="fa-solid fa-calendar-days"></i><span>My Events</span></a></li>
      <li><a href="#register-events"><i class="fa-solid fa-edit"></i><span>Register for Events</span></a></li>
      <li><a href="#profile"><i class="fa-solid fa-user-pen"></i><span>Profile & Social</span></a></li>
    </ul>
  </aside>

  <!-- Main -->
  <main class="main">
    <!-- Topbar -->
    <div class="topbar">
      <button class="btn btn-soft toggle" id="toggleSidebar"><i class="fa-solid fa-bars"></i></button>
      <div class="crumbs">
        <a href="member_dashboard.php">Dashboard</a>
        <span>›</span>
        <span>Member Dashboard</span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-muted">Welcome, <?php echo htmlspecialchars($name); ?></span>
        <button class="btn btn-soft"><i class="fa-regular fa-bell"></i></button>
        <button class="btn btn-soft"><i class="fa-regular fa-circle-question"></i></button>
        <a href="logout.php" class="btn btn-soft"><i class="fa-solid fa-right-from-bracket"></i></a>
      </div>
    </div>

    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
      <div class="title-row">
        <span class="chip"><i class="fa-solid fa-user text-primary"></i> Member Dashboard</span>
        <h2>👋 Welcome, <?php echo htmlspecialchars($name); ?></h2>
        <span class="badge">Member Access</span>
      </div>
    </div>

    <!-- Upcoming Events -->
    <div class="card-lite mb-4">
      <div class="card-head">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-calendar-check text-primary"></i>
          <span class="fw-semibold">Upcoming Events</span>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php while($event = mysqli_fetch_assoc($upcoming_events)) { ?>
          <div class="col-md-6">
            <div class="card p-3">
              <h5><?php echo htmlspecialchars($event['event_name']); ?></h5>
              <p class="text-muted"><?php echo date('F j, Y', strtotime($event['event_date'])); ?></p>
              <button class="btn btn-primary btn-sm" onclick="showRegistrationForm('<?php echo $event['event_id']; ?>', '<?php echo htmlspecialchars($event['event_name']); ?>')">
                <i class="fa-solid fa-right-to-bracket me-1"></i> Register
              </button>
            </div>
          </div>
          <?php } ?>
        </div>
      </div>
    </div>

    <!-- Registered Events -->
    <div class="card-lite mb-4">
      <div class="card-head">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-bookmark text-success"></i>
          <span class="fw-semibold">My Registered Events</span>
        </div>
      </div>
      <div class="card-body">
        <ul class="list-group">
          <?php if (count($registered_events) > 0) { ?>
            <?php foreach ($registered_events as $reg_event) { ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?php echo htmlspecialchars($reg_event['event_name']); ?>
              <span class="badge bg-success">Registered</span>
            </li>
            <?php } ?>
          <?php } else { ?>
            <li class="list-group-item text-center text-muted">
              You haven't registered for any events yet.
            </li>
          <?php } ?>
        </ul>
      </div>
    </div>
  
    <!-- Spot Entry Form -->
    <div class="card-lite mb-4">
      <div class="card-head">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-user-plus text-primary"></i>
          <span class="fw-semibold">Spot Entry - Manual Guest Registration</span>
        </div>
      </div>
      <div class="card-body">
        <form id="spotEntryForm" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Event <span class="text-danger">*</span></label>
            <select class="form-select" name="event_id" required>
              <option value="">Select Event</option>
              <?php foreach ($events_array as $event): ?>
              <option value="<?php echo $event['event_id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email">
          </div>
          <div class="col-md-6">
            <label class="form-label">Mobile Number</label>
            <input type="tel" class="form-control" name="mobile" placeholder="+91 9876543210">
          </div>
          <div class="col-md-6">
            <label class="form-label">Department</label>
            <input type="text" class="form-control" name="department">
          </div>
          <div class="col-md-6">
            <label class="form-label">Gender</label>
            <select class="form-select" name="gender">
              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i> Add Spot Entry</button>
          </div>
        </form>
      </div>
    </div>
  
    <!-- Add Additional Members Form -->
    <div class="card-lite mb-4">
      <div class="card-head">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-users text-success"></i>
          <span class="fw-semibold">Add Additional Members (Family/Friends)</span>
        </div>
      </div>
      <div class="card-body">
        <form id="additionalMemberForm" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Event <span class="text-danger">*</span></label>
            <select class="form-select" name="event_id" required>
              <option value="">Select Event</option>
              <?php foreach ($events_array as $event): ?>
              <option value="<?php echo $event['event_id']; ?>"><?php echo htmlspecialchars($event['event_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control" name="email">
          </div>
          <div class="col-md-6">
            <label class="form-label">Mobile Number</label>
            <input type="tel" class="form-control" name="mobile" placeholder="+91 9876543210">
          </div>
          <div class="col-md-6">
            <label class="form-label">Department</label>
            <input type="text" class="form-control" name="department">
          </div>
          <div class="col-md-6">
            <label class="form-label">Gender</label>
            <select class="form-select" name="gender">
              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Relation to Existing Guest (Optional)</label>
            <input type="text" class="form-control" name="relation" placeholder="e.g., Family member, Friend, Colleague">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-success"><i class="fa-solid fa-user-plus me-2"></i> Add Member</button>
          </div>
        </form>
      </div>
    </div>
  
    <!-- Profile & Social Media -->
    <div class="card-lite mb-4">
      <div class="card-head">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-user-gear text-warning"></i>
          <span class="fw-semibold">Profile & Social Media</span>
        </div>
      </div>
      <div class="card-body">
        <form id="profileForm" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($member_data['member_name'] ?? ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($member_data['emailid'] ?? ''); ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Department</label>
            <select class="form-select" name="department">
              <option value="">Select Department</option>
              <?php
              $depts = mysqli_query($conn, "SELECT * FROM tbl_department WHERE status=1 ORDER BY department");
              while($dept = mysqli_fetch_assoc($depts)) {
                $selected = ($member_data['department'] ?? '') == $dept['department'] ? 'selected' : '';
                echo "<option value='{$dept['department']}' $selected>{$dept['department']}</option>";
              }
              ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Year of Graduation</label>
            <select class="form-select" name="graduation_year">
              <option value="">Select Year</option>
              <?php for ($y = 2000; $y <= date("Y"); $y++) { 
                $selected = ($member_data['graduation_year'] ?? '') == $y ? 'selected' : '';
                echo "<option value='$y' $selected>$y</option>";
              } ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">LinkedIn ID</label>
            <input type="text" class="form-control" name="linkedin" placeholder="linkedin.com/in/username" value="<?php echo htmlspecialchars($member_data['linkedin'] ?? ''); ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Instagram ID</label>
            <input type="text" class="form-control" name="instagram" placeholder="@username" value="<?php echo htmlspecialchars($member_data['instagram'] ?? ''); ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">WhatsApp Number</label>
            <input type="text" class="form-control" name="whatsapp" placeholder="+91 9876543210" value="<?php echo htmlspecialchars($member_data['whatsapp'] ?? ''); ?>">
          </div>
          <div class="col-12">
            <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk me-2"></i> Save Profile</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Announcements -->
    <div class="card-lite mb-4">
      <div class="card-head">
        <div class="d-flex align-items-center gap-2">
          <i class="fa-solid fa-bullhorn text-danger"></i>
          <span class="fw-semibold">Announcements</span>
        </div>
      </div>
      <div class="card-body">
        <?php while($announcement = mysqli_fetch_assoc($announcements)) { ?>
        <div class="alert alert-info">
          <i class="fa-solid fa-circle-info me-2"></i> <?php echo htmlspecialchars($announcement['content']); ?>
        </div>
        <?php } ?>
        <?php if (mysqli_num_rows($announcements) == 0) { ?>
        <div class="alert alert-warning">
          <i class="fa-solid fa-triangle-exclamation me-2"></i> No announcements at this time.
        </div>
        <?php } ?>
      </div>
    </div>
  </main>

  <!-- Event Registration Modal -->
  <div class="modal fade" id="registrationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Event Registration</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="registrationForm">
            <input type="hidden" name="event_id" id="event_id">
            <input type="hidden" name="event_name" id="event_name">
            
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" required value="<?php echo htmlspecialchars($member_data['member_name'] ?? ''); ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($member_data['emailid'] ?? ''); ?>">
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Phone Number (India) <span class="text-danger">*</span></label>
                <input type="tel" class="form-control" name="phone" required pattern="[+91]{3} [0-9]{10}" placeholder="+91 9876543210" value="<?php echo htmlspecialchars($member_data['whatsapp'] ?? ''); ?>">
                <div class="form-text">Format: +91 followed by 10-digit number</div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Department</label>
                <select class="form-select" name="department">
                  <option value="">Select Department</option>
                  <?php
                  $depts = mysqli_query($conn, "SELECT * FROM tbl_department WHERE status=1 ORDER BY department");
                  while($dept = mysqli_fetch_assoc($depts)) {
                    $selected = ($member_data['department'] ?? '') == $dept['department'] ? 'selected' : '';
                    echo "<option value='{$dept['department']}' $selected>{$dept['department']}</option>";
                  }
                  ?>
                </select>
              </div>
              
              <div class="col-md-6">
                <label class="form-label">Food Preferences</label>
                <select class="form-select" name="food_preference">
                  <option value="">Select Preference</option>
                  <option value="Vegetarian">Vegetarian</option>
                  <option value="Non-Vegetarian">Non-Vegetarian</option>
                  <option value="Vegan">Vegan</option>
                  <option value="Jain">Jain</option>
                  <option value="No Preference">No Preference</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Dietary Restrictions</label>
                <input type="text" class="form-control" name="dietary_restrictions" placeholder="Any allergies or restrictions">
              </div>
              
              <div class="col-md-4">
                <label class="form-label">LinkedIn Profile</label>
                <input type="url" class="form-control" name="linkedin" placeholder="https://linkedin.com/in/username" value="<?php echo htmlspecialchars($member_data['linkedin'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Instagram ID</label>
                <input type="text" class="form-control" name="instagram" placeholder="@username" value="<?php echo htmlspecialchars($member_data['instagram'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Twitter/X ID</label>
                <input type="text" class="form-control" name="twitter" placeholder="@username">
              </div>
              
              <div class="col-12">
                <label class="form-label">How did you hear about this event?</label>
                <select class="form-select" name="referral_source">
                  <option value="">Select Source</option>
                  <option value="Email">Email</option>
                  <option value="Social Media">Social Media</option>
                  <option value="Word of Mouth">Word of Mouth</option>
                  <option value="College Notice">College Notice</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              
              <div class="col-12">
                <label class="form-label">Additional Comments or Questions</label>
                <textarea class="form-control" name="comments" rows="3" placeholder="Any special requests or questions"></textarea>
              </div>
              
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="newsletter" id="newsletter" checked>
                  <label class="form-check-label" for="newsletter">
                    Subscribe to our newsletter for future events
                  </label>
                </div>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitRegistration()">Complete Registration</button>
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

    function showRegistrationForm(eventId, eventName) {
      document.getElementById('event_id').value = eventId;
      document.getElementById('event_name').value = eventName;
      
      const modal = new bootstrap.Modal(document.getElementById('registrationModal'));
      modal.show();
    }

    function submitRegistration() {
      const form = document.getElementById('registrationForm');
      const formData = new FormData(form);
      
      // Add user_id to the form data
      formData.append('user_id', '<?php echo $id; ?>');
      
      fetch('register_event.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Registration successful!');
          // Close the modal
          const modal = bootstrap.Modal.getInstance(document.getElementById('registrationModal'));
          modal.hide();
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error registering for event');
      });
    }

    document.getElementById('profileForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      
      fetch('update_profile.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Profile updated successfully!');
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error updating profile');
      });
    });

    // Handle Spot Entry Form Submission
    document.getElementById('spotEntryForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      
      fetch('add_spot_entry.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Spot entry added successfully!');
          this.reset();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error adding spot entry');
      });
    });

    // Handle Additional Member Form Submission
    document.getElementById('additionalMemberForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      
      fetch('add_additional_member.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Additional member added successfully!');
          this.reset();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error adding additional member');
      });
    });
  </script>
</body>
</html>
