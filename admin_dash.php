<?php
include('db.php');
// ALL LOGIC BEFORE HTML
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: admin_login.php"); exit();
}

$msg = '';

// Add Holiday
if(isset($_POST['add_holiday'])){
    $hdate  = $_POST['h_date'];
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    $r = mysqli_query($conn, "INSERT IGNORE INTO holidays (h_date, reason) VALUES ('$hdate', '$reason')");
    $msg = $r
        ? "<div style='background:#d1fae5;color:#065f46;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-check-circle me-2'></i>Holiday added.</div>"
        : "<div style='background:#fff3cd;color:#92400e;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'>Holiday already exists for this date.</div>";
}

// Delete Holiday
if(isset($_GET['del_hol'])){
    $hid = (int)$_GET['del_hol'];
    mysqli_query($conn, "DELETE FROM holidays WHERE id=$hid");
    header("Location: admin_dash.php"); exit();
}

// ── Photo Upload (file input) ──────────────────────────────────────────────
if(isset($_POST['upload_photo']) && isset($_FILES['hall_img'])){
    $bid      = (int)$_POST['booking_id'];
    $file_ext = strtolower(pathinfo($_FILES['hall_img']['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg','jpeg','png','webp'];

    // Resolve upload directory — try document-root-relative first, fall back to script-relative
    $upload_dir = rtrim($_SERVER['DOCUMENT_ROOT'],'/') . '/uploads/';
    if(!is_dir($upload_dir)){
        $upload_dir = dirname(__FILE__) . '/uploads/';
    }
    if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    if(!in_array($file_ext, $allowed)){
        $msg = "<div style='background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-times-circle me-2'></i>Invalid file type. Use JPG, PNG, or WEBP.</div>";
    } elseif($_FILES['hall_img']['size'] > 5 * 1024 * 1024){
        $msg = "<div style='background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-times-circle me-2'></i>File too large. Maximum size is 5 MB.</div>";
    } else {
        $fname  = time() . '_' . uniqid() . '.' . $file_ext;
        $target = $upload_dir . $fname;

        if(move_uploaded_file($_FILES['hall_img']['tmp_name'], $target)){
            // Delete old photo if it exists
            $old = mysqli_query($conn, "SELECT event_photo FROM bookings WHERE id=$bid");
            if($old_row = mysqli_fetch_assoc($old)){
                if($old_row['event_photo'] && file_exists($upload_dir.$old_row['event_photo'])){
                    unlink($upload_dir.$old_row['event_photo']);
                }
            }
            mysqli_query($conn, "UPDATE bookings SET event_photo='$fname' WHERE id=$bid");
            $msg = "<div style='background:#d1fae5;color:#065f46;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-check-circle me-2'></i>Photo uploaded successfully!</div>";
        } else {
            $msg = "<div style='background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-times-circle me-2'></i>Upload failed. Check that /uploads/ exists and is writable.</div>";
        }
    }
}

// ── Stats ──────────────────────────────────────────────────────────────────
$total     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings"))['c'];
$pending   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE status='Pending'"))['c'];
$confirmed = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE status='Confirmed'"))['c'];
$rejected  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE status='Rejected'"))['c'];

// ── Monthly / yearly statistics ────────────────────────────────────────────
$cur_year = isset($_GET['stats_year']) ? (int)$_GET['stats_year'] : date('Y');

$monthly_stats = [];
for($mo = 1; $mo <= 12; $mo++){
    $r = mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE YEAR(booking_date)=$cur_year AND MONTH(booking_date)=$mo AND status='Confirmed'");
    $monthly_stats[$mo] = mysqli_fetch_assoc($r)['c'];
}

$yearly_stats = [];
for($yr = date('Y')-2; $yr <= date('Y'); $yr++){
    $r = mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE YEAR(booking_date)=$yr AND status='Confirmed'");
    $yearly_stats[$yr] = mysqli_fetch_assoc($r)['c'];
}

$halls = ["Seminar Hall 405","IMERT Seminar Hall","Main Auditorium","Civil Seminar Hall"];
$hall_monthly = [];
foreach($halls as $hall){
    $esc = mysqli_real_escape_string($conn, $hall);
    $hall_monthly[$hall] = [];
    for($mo = 1; $mo <= 12; $mo++){
        $r   = mysqli_query($conn,"SELECT COUNT(*) c, GROUP_CONCAT(event_name SEPARATOR ', ') evts FROM bookings WHERE YEAR(booking_date)=$cur_year AND MONTH(booking_date)=$mo AND hall_name='$esc' AND status='Confirmed'");
        $row = mysqli_fetch_assoc($r);
        $hall_monthly[$hall][$mo] = ['count' => $row['c'], 'events' => $row['evts'] ?? ''];
    }
}

// ── HTML STARTS HERE ───────────────────────────────────────────────────────
head("Admin Dashboard");
?>

<style>
/* ── Print ── */
@media print {
  .no-print { display:none !important; }
  .topbar   { display:none !important; }
  body { background:white !important; }
  .card-pro { box-shadow:none !important; border:1px solid #ddd !important; break-inside:avoid; }
  .print-header { display:block !important; }
  @page { margin:15mm; }
}
.print-header { display:none; }

/* ── Camera / viewer modals ── */
#cameraModal, #imageViewerModal {
    position:fixed; top:0; left:0; width:100%; height:100%;
    background:rgba(0,0,0,0.88); z-index:9999;
    display:none; justify-content:center; align-items:center;
}
#cameraModal.open, #imageViewerModal.open { display:flex; animation:fadeIn 0.25s ease; }
@keyframes fadeIn { from{opacity:0} to{opacity:1} }

.cam-modal-box {
    background:white; border-radius:18px; padding:24px;
    max-width:600px; width:92%;
    animation:slideUp 0.25s ease;
}
@keyframes slideUp { from{transform:translateY(40px);opacity:0} to{transform:translateY(0);opacity:1} }

#video { width:100%; border-radius:10px; background:#f1f5f9; margin-bottom:14px; display:block; }
#capturedImage { width:100%; border-radius:10px; margin-bottom:14px; display:none; }

.modal-header-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
.modal-header-row h5 { margin:0; color:#1a202c; font-size:1rem; font-weight:700; }
.modal-header-row .close-x { background:none; border:none; font-size:1.5rem; cursor:pointer; line-height:1; color:#718096; }

.modal-footer-row { display:flex; gap:10px; justify-content:center; flex-wrap:wrap; margin-top:14px; }
.mbtn { padding:9px 18px; border:none; border-radius:8px; font-weight:700; font-size:0.85rem; cursor:pointer; transition:all 0.18s; font-family:inherit; }
.mbtn:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,0.18); }
.mbtn-primary   { background:#1a73e8; color:white; }
.mbtn-success   { background:#0d9f6e; color:white; }
.mbtn-danger    { background:#dc2626; color:white; }
.mbtn-secondary { background:#f1f5f9; color:#1a202c; }
.mbtn-purple    { background:#7c3aed; color:white; }

#switchCameraBtn {
    width:100%; margin-top:10px; padding:9px; background:#7c3aed; color:white;
    border:none; border-radius:8px; font-weight:700; font-size:0.85rem; cursor:pointer;
    transition:0.18s; font-family:inherit;
}
#switchCameraBtn:hover { background:#6d28d9; }

/* image viewer */
.img-viewer-inner { position:relative; max-width:860px; width:95%; }
.img-viewer-inner .close-viewer {
    position:absolute; top:-42px; right:0;
    background:rgba(255,255,255,0.12); border:none; color:white;
    font-size:1.5rem; cursor:pointer; border-radius:8px; padding:2px 10px;
}
#viewerImage { width:100%; border-radius:12px; display:block; }

/* inline action buttons */
.camera-btn {
    background:#0d9f6e; color:white; border:none; border-radius:7px;
    padding:5px 8px; font-size:0.72rem; cursor:pointer;
    display:flex; align-items:center; gap:4px; transition:0.18s;
}
.camera-btn:hover { transform:scale(1.05); box-shadow:0 4px 12px rgba(13,159,110,0.3); }
.upload-btn {
    background:#7c3aed; color:white; border:none; border-radius:7px;
    padding:5px 8px; font-size:0.72rem; cursor:pointer; transition:0.18s;
}
.upload-btn:hover { transform:scale(1.05); box-shadow:0 4px 12px rgba(124,58,237,0.3); }
.retake-btn {
    background:#7c3aed; color:white; border:none; border-radius:5px;
    padding:3px 7px; font-size:0.65rem; cursor:pointer; transition:0.18s;
}
.retake-btn:hover { transform:scale(1.04); }
.photo-thumbnail {
    width:55px; height:40px; object-fit:cover; border-radius:6px;
    cursor:pointer; transition:opacity 0.2s;
}
.photo-thumbnail:hover { opacity:0.78; }
</style>

<!-- ════════════════════ TOP BAR ════════════════════ -->
<div class="topbar no-print" style="background:#0f172a;border-bottom:2px solid #6366f1;">
  <div class="topbar-brand" style="color:#a5b4fc;"><i class="fas fa-tools me-2"></i>MMCOE <span style="color:#818cf8;">Admin</span></div>
  <div style="display:flex;align-items:center;gap:12px;">
    <span style="font-size:0.85rem;color:#64748b;">🛡️ <b style="color:#94a3b8;"><?php echo htmlspecialchars($_SESSION['name']); ?></b></span>
    <a href="academic_display.php" style="background:#7c3aed;color:white;border-radius:8px;padding:7px 16px;font-size:0.82rem;font-weight:700;text-decoration:none;">
      <i class="fas fa-calendar-alt me-1"></i>Institute Planner
    </a>
    <a href="logout.php" style="background:#dc2626;color:white;border-radius:8px;padding:7px 16px;font-size:0.82rem;font-weight:700;text-decoration:none;">
      <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
  </div>
</div>

<!-- ════════════════════ BODY ════════════════════ -->
<div class="container-fluid px-4 py-4">

  <!-- Stat cards -->
  <div class="row g-3 mb-4 no-print">
    <?php
    $stats_cards = [
        ['Total Requests', $total,     '#1a73e8','#e8f0fe','fas fa-calendar-alt'],
        ['Pending',        $pending,   '#d97706','#fff3cd','fas fa-hourglass-half'],
        ['Confirmed',      $confirmed, '#0d9f6e','#d1fae5','fas fa-check-circle'],
        ['Rejected',       $rejected,  '#dc2626','#fee2e2','fas fa-times-circle'],
    ];
    foreach($stats_cards as $s){ ?>
      <div class="col-md-3">
        <div class="stat-card" style="border-left-color:<?php echo $s[2]; ?>;">
          <div class="stat-icon" style="background:<?php echo $s[3]; ?>;color:<?php echo $s[2]; ?>;"><i class="<?php echo $s[4]; ?>"></i></div>
          <div>
            <div style="font-size:0.78rem;color:#718096;font-weight:600;text-transform:uppercase;"><?php echo $s[0]; ?></div>
            <div style="font-size:1.6rem;font-weight:800;color:#1a202c;"><?php echo $s[1]; ?></div>
          </div>
        </div>
      </div>
    <?php } ?>
  </div>

  <?php echo $msg; ?>

  <div class="row g-4">

    <!-- ── Left panel ───────────────────────────────── -->
    <div class="col-lg-4 no-print">

      <!-- Holiday Manager -->
      <div class="card-pro mb-4">
        <div class="card-header-pro" style="background:linear-gradient(90deg,#dc2626,#b91c1c);">
          <i class="fas fa-calendar-times me-2"></i>Holiday Manager
        </div>
        <div class="card-body-pro">
          <form method="POST">
            <div style="margin-bottom:12px;">
              <label class="form-label-pro">Date</label>
              <input type="date" name="h_date" class="form-field" required>
            </div>
            <div style="margin-bottom:14px;">
              <label class="form-label-pro">Reason</label>
              <input type="text" name="reason" class="form-field" placeholder="e.g. Diwali, Republic Day" required>
            </div>
            <button type="submit" name="add_holiday" class="btn-danger-pro" style="width:100%;padding:11px;font-size:0.9rem;">
              <i class="fas fa-plus me-1"></i>Block This Date
            </button>
          </form>
          <div style="margin-top:18px;max-height:220px;overflow-y:auto;">
            <?php
            $hols = mysqli_query($conn, "SELECT * FROM holidays ORDER BY h_date ASC");
            if(mysqli_num_rows($hols) == 0){
                echo '<p style="color:#718096;font-size:0.85rem;text-align:center;padding:12px;">No holidays set.</p>';
            }
            while($h = mysqli_fetch_assoc($hols)){ ?>
              <div style="display:flex;justify-content:space-between;align-items:center;background:#fef2f2;border-radius:9px;padding:9px 12px;margin-bottom:7px;">
                <div>
                  <div style="font-size:0.82rem;font-weight:700;color:#dc2626;"><?php echo date('d M Y', strtotime($h['h_date'])); ?></div>
                  <div style="font-size:0.78rem;color:#b91c1c;"><?php echo htmlspecialchars($h['reason']); ?></div>
                </div>
                <a href="admin_dash.php?del_hol=<?php echo $h['id']; ?>" onclick="return confirm('Remove this holiday?')" style="color:#dc2626;text-decoration:none;"><i class="fas fa-trash"></i></a>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>

      <!-- Hall Usage -->
      <div class="card-pro">
        <div class="card-header-pro" style="background:linear-gradient(90deg,#7c3aed,#6d28d9);">
          <i class="fas fa-chart-bar me-2"></i>Hall Usage
        </div>
        <div class="card-body-pro">
          <?php foreach($halls as $hall){
            $esc = mysqli_real_escape_string($conn, $hall);
            $cnt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE hall_name='$esc' AND status='Confirmed'"))['c'];
            $tot = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE hall_name='$esc'"))['c'];
            $pct = $tot > 0 ? round(($cnt/$tot)*100) : 0;
            ?>
            <div style="margin-bottom:14px;">
              <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:5px;">
                <span style="font-weight:600;color:#1a202c;"><?php echo $hall; ?></span>
                <span style="color:#0d9f6e;font-weight:700;"><?php echo $cnt; ?> confirmed</span>
              </div>
              <div style="background:#e8f0fe;border-radius:100px;height:7px;overflow:hidden;">
                <div style="background:#1a73e8;width:<?php echo $pct; ?>%;height:100%;border-radius:100px;"></div>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>
    </div><!-- /left panel -->

    <!-- ── Bookings table ────────────────────────────── -->
    <div class="col-lg-8 no-print">
      <div class="card-pro">
        <div class="card-header-pro" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
          <span><i class="fas fa-list me-2"></i>All Booking Requests</span>
          <div style="display:flex;gap:8px;">
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search..."
              style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:8px;padding:6px 12px;color:white;font-size:0.82rem;font-family:inherit;">
            <select id="filterStatus" onchange="filterTable()"
              style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:8px;padding:6px 10px;color:white;font-size:0.82rem;font-family:inherit;">
              <option value="">All</option>
              <option value="Pending">Pending</option>
              <option value="Confirmed">Confirmed</option>
              <option value="Rejected">Rejected</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div style="overflow-x:auto;">
          <table class="pro-table" id="bookingsTable">
            <thead>
              <tr><th>Requester</th><th>Hall & Event</th><th>Date & Time</th><th>Chairs</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php
            $res = mysqli_query($conn,
                "SELECT b.*, u.name uname, u.phone, u.email uemail
                 FROM bookings b JOIN users u ON b.user_id=u.id
                 ORDER BY FIELD(b.status,'Pending','Confirmed','Rejected','Cancelled'), b.id DESC");
            if(mysqli_num_rows($res) == 0){ ?>
              <tr><td colspan="6" style="text-align:center;padding:50px;color:#718096;">No booking requests yet.</td></tr>
            <?php }
            while($r = mysqli_fetch_assoc($res)){ ?>
              <tr>
                <td>
                  <div style="font-weight:700;font-size:0.87rem;"><?php echo htmlspecialchars($r['uname']); ?></div>
                  <div style="font-size:0.75rem;color:#718096;"><?php echo htmlspecialchars($r['contact_phone'] ?: $r['phone']); ?></div>
                  <div style="font-size:0.75rem;color:#1a73e8;"><?php echo htmlspecialchars($r['contact_email'] ?: $r['uemail']); ?></div>
                </td>
                <td>
                  <div style="font-weight:600;font-size:0.85rem;"><?php echo htmlspecialchars($r['hall_name']); ?></div>
                  <div style="font-size:0.75rem;color:#718096;"><?php echo htmlspecialchars($r['event_name'] ?? '—'); ?></div>
                </td>
                <td>
                  <div style="font-weight:600;font-size:0.84rem;"><?php echo date('d M Y', strtotime($r['booking_date'])); ?></div>
                  <div style="font-size:0.75rem;color:#718096;">
                    <?php echo date('h:i A', strtotime($r['start_time'])); ?> – <?php echo date('h:i A', strtotime($r['end_time'])); ?>
                  </div>
                </td>
                <td style="font-weight:600;"><?php echo $r['chairs']; ?></td>
                <td><span class="badge-status badge-<?php echo $r['status']; ?>"><?php echo $r['status']; ?></span></td>
                <td>
                  <?php if($r['status'] == 'Pending'){ ?>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                      <a href="approve.php?id=<?php echo $r['id']; ?>&st=Confirmed" class="btn-success-pro"
                         onclick="return confirm('Confirm this booking?')"
                         style="text-decoration:none;font-size:0.78rem;padding:6px 12px;">
                        <i class="fas fa-check me-1"></i>Confirm
                      </a>
                      <a href="approve.php?id=<?php echo $r['id']; ?>&st=Rejected" class="btn-danger-pro"
                         onclick="return confirm('Reject this booking?')"
                         style="text-decoration:none;font-size:0.78rem;padding:6px 12px;">
                        <i class="fas fa-times me-1"></i>Reject
                      </a>
                    </div>

                  <?php } elseif($r['status'] == 'Confirmed'){ ?>
                    <div style="display:flex;flex-direction:column;gap:5px;">
                      <!-- Receipt link -->
                      <a href="report.php?id=<?php echo $r['id']; ?>" target="_blank"
                         style="background:#e8f0fe;color:#1a73e8;border-radius:7px;padding:5px 10px;font-size:0.76rem;font-weight:700;text-decoration:none;">
                        <i class="fas fa-file-invoice me-1"></i>Receipt
                      </a>

                      <?php if(!$r['event_photo']){ ?>
                        <!-- No photo yet — show file upload + camera -->
                        <div style="display:flex;flex-direction:column;gap:5px;">
                          <form method="POST" enctype="multipart/form-data" style="display:flex;gap:4px;align-items:center;">
                            <input type="hidden" name="booking_id" value="<?php echo $r['id']; ?>">
                            <input type="file" name="hall_img" accept=".jpg,.jpeg,.png,.webp" style="font-size:0.72rem;width:100px;" required>
                            <button type="submit" name="upload_photo" class="upload-btn" title="Upload from file">
                              <i class="fas fa-upload"></i>
                            </button>
                          </form>
                          <button type="button" onclick="openCamera(<?php echo $r['id']; ?>)" class="camera-btn">
                            <i class="fas fa-camera"></i> Take Photo
                          </button>
                        </div>

                      <?php } else { ?>
                        <!-- Photo exists — thumbnail + view + retake -->
                        <div style="display:flex;align-items:center;gap:6px;">
                          <img src="uploads/<?php echo htmlspecialchars($r['event_photo']); ?>"
                               class="photo-thumbnail"
                               onclick="viewImage('uploads/<?php echo htmlspecialchars($r['event_photo']); ?>')"
                               title="Click to view full size">
                          <span style="font-size:0.74rem;color:#0d9f6e;font-weight:600;">✔ Photo</span>
                          <button type="button" onclick="openCamera(<?php echo $r['id']; ?>)" class="retake-btn" title="Retake photo">
                            <i class="fas fa-camera"></i> Retake
                          </button>
                        </div>
                      <?php } ?>
                    </div>

                  <?php } else {
                    echo '<span style="color:#b0bec5;font-size:0.78rem;">—</span>';
                  } ?>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div><!-- /bookings table -->

  </div><!-- /row -->

  <!-- ════════════════════ STATISTICS SECTION (Printable) ════════════════════ -->
  <div style="margin-top:32px;">
    <div class="card-pro" id="statsSection">

      <!-- Header -->
      <div class="card-header-pro no-print"
           style="background:linear-gradient(90deg,#0d47a1,#1a73e8);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
        <span><i class="fas fa-chart-line me-2"></i>Hall Usage Statistics — Monthly & Yearly</span>
        <div style="display:flex;gap:8px;align-items:center;">
          <form method="GET" style="display:flex;gap:6px;align-items:center;">
            <label style="color:rgba(255,255,255,0.8);font-size:0.82rem;">Year:</label>
            <select name="stats_year" onchange="this.form.submit()"
              style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);border-radius:8px;padding:5px 10px;color:white;font-size:0.82rem;font-family:inherit;">
              <?php for($y = date('Y')-2; $y <= date('Y')+1; $y++){ ?>
                <option value="<?php echo $y; ?>" <?php echo $y==$cur_year?'selected':''; ?>
                  style="background:#1e3a5f;color:white;"><?php echo $y; ?></option>
              <?php } ?>
            </select>
          </form>
          <button onclick="window.print()"
            style="background:#fbbf24;color:#1a202c;border:none;border-radius:8px;padding:7px 16px;font-size:0.82rem;font-weight:700;cursor:pointer;font-family:inherit;">
            <i class="fas fa-print me-1"></i>Print / Export
          </button>
        </div>
      </div>

      <!-- Print-only report header -->
      <div class="print-header" style="text-align:center;padding:20px 0 10px;border-bottom:2px solid #1a73e8;margin-bottom:20px;">
        <h2 style="color:#1a202c;font-size:1.3rem;font-weight:800;margin:0;">MMCOE — Hall Usage Statistics Report</h2>
        <p style="color:#718096;font-size:0.85rem;margin:4px 0 0;">
          Academic Year <?php echo $cur_year; ?>–<?php echo $cur_year+1; ?>
          &nbsp;|&nbsp; Generated: <?php echo date('d M Y, h:i A'); ?>
        </p>
      </div>

      <div class="card-body-pro">

        <!-- Yearly summary tiles -->
        <div style="display:flex;gap:10px;margin-bottom:22px;flex-wrap:wrap;" class="no-print">
          <div style="background:#e8f0fe;border-radius:10px;padding:10px 18px;text-align:center;min-width:110px;">
            <div style="font-size:0.72rem;color:#1a73e8;font-weight:700;text-transform:uppercase;">Showing</div>
            <div style="font-size:1.4rem;font-weight:800;color:#1a73e8;"><?php echo $cur_year; ?></div>
          </div>
          <?php foreach($yearly_stats as $yr => $cnt){ ?>
          <div style="background:<?php echo $yr==$cur_year?'#1a73e8':'#f8faff'; ?>;border:1.5px solid <?php echo $yr==$cur_year?'#1a73e8':'#e8ecf4'; ?>;border-radius:10px;padding:10px 18px;text-align:center;min-width:110px;">
            <div style="font-size:0.72rem;color:<?php echo $yr==$cur_year?'rgba(255,255,255,0.8)':'#718096'; ?>;font-weight:700;text-transform:uppercase;"><?php echo $yr; ?></div>
            <div style="font-size:1.4rem;font-weight:800;color:<?php echo $yr==$cur_year?'white':'#1a202c'; ?>;"><?php echo $cnt; ?></div>
            <div style="font-size:0.7rem;color:<?php echo $yr==$cur_year?'rgba(255,255,255,0.7)':'#718096'; ?>;">confirmed</div>
          </div>
          <?php } ?>
        </div>

        <!-- Monthly bar chart -->
        <?php
        $month_names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $max_val = max(array_merge(array_values($monthly_stats), [1]));
        ?>
        <div style="margin-bottom:28px;">
          <h6 style="font-weight:700;color:#1a202c;font-size:0.92rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <span style="background:#e8f0fe;color:#1a73e8;border-radius:8px;padding:4px 10px;font-size:0.8rem;">📊</span>
            Monthly Confirmed Bookings — <?php echo $cur_year; ?>
          </h6>
          <div style="display:flex;align-items:flex-end;gap:6px;height:140px;padding:0 4px;border-bottom:2px solid #e8ecf4;border-left:2px solid #e8ecf4;margin-bottom:6px;">
            <?php for($mo = 1; $mo <= 12; $mo++){
              $cnt    = $monthly_stats[$mo];
              $pct    = $max_val > 0 ? round(($cnt/$max_val)*100) : 0;
              $cur_mo = (date('Y') == $cur_year && date('n') == $mo);
              ?>
              <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;gap:3px;">
                <?php if($cnt > 0){ ?><div style="font-size:0.68rem;font-weight:700;color:#1a73e8;"><?php echo $cnt; ?></div><?php } ?>
                <div style="width:100%;background:<?php echo $cur_mo?'#fbbf24':($cnt>0?'#1a73e8':'#e8ecf4'); ?>;height:<?php echo max($pct,2); ?>%;border-radius:4px 4px 0 0;min-height:<?php echo $cnt>0?'8px':'2px'; ?>;"></div>
              </div>
            <?php } ?>
          </div>
          <div style="display:flex;gap:6px;padding:0 4px;">
            <?php foreach($month_names as $mn){ ?>
              <div style="flex:1;text-align:center;font-size:0.65rem;color:#718096;font-weight:600;"><?php echo $mn; ?></div>
            <?php } ?>
          </div>
          <div style="font-size:0.72rem;color:#718096;margin-top:6px;">
            <span style="display:inline-block;width:10px;height:10px;background:#fbbf24;border-radius:2px;margin-right:4px;"></span>Current month
          </div>
        </div>

        <!-- Hall-wise monthly table -->
        <div>
          <h6 style="font-weight:700;color:#1a202c;font-size:0.92rem;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <span style="background:#d1fae5;color:#065f46;border-radius:8px;padding:4px 10px;font-size:0.8rem;">📋</span>
            Hall-Wise Monthly Event Report — <?php echo $cur_year; ?>
          </h6>
          <div style="overflow-x:auto;">
            <table style="border-collapse:collapse;width:100%;font-size:0.78rem;">
              <thead>
                <tr style="background:#0d47a1;color:white;">
                  <th style="padding:10px 12px;text-align:left;font-weight:700;white-space:nowrap;">Hall Name</th>
                  <?php foreach($month_names as $mn){ ?>
                    <th style="padding:10px 6px;text-align:center;font-weight:700;"><?php echo $mn; ?></th>
                  <?php } ?>
                  <th style="padding:10px 12px;text-align:center;font-weight:700;">Total</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $hall_colors = [
                  'Seminar Hall 405'   => '#e8f0fe',
                  'IMERT Seminar Hall' => '#d1fae5',
                  'Main Auditorium'    => '#fef3c7',
                  'Civil Seminar Hall' => '#ede9fe',
              ];
              foreach($halls as $hall){
                  $hall_total = 0;
                  $hcolor = $hall_colors[$hall] ?? '#f8faff';
                  ?>
                  <tr>
                    <td style="padding:9px 12px;font-weight:700;color:#1a202c;background:<?php echo $hcolor; ?>;border-bottom:1px solid #e8ecf4;white-space:nowrap;"><?php echo $hall; ?></td>
                    <?php for($mo = 1; $mo <= 12; $mo++){
                      $d = $hall_monthly[$hall][$mo];
                      $hall_total += $d['count'];
                      ?>
                      <td style="padding:9px 6px;text-align:center;border-bottom:1px solid #e8ecf4;" title="<?php echo htmlspecialchars($d['events']); ?>">
                        <?php if($d['count'] > 0){ ?>
                          <span style="background:#1a73e8;color:white;border-radius:5px;padding:2px 7px;font-weight:700;font-size:0.76rem;cursor:default;"
                                title="Events: <?php echo htmlspecialchars($d['events']); ?>">
                            <?php echo $d['count']; ?>
                          </span>
                        <?php } else { echo '<span style="color:#ddd;">—</span>'; } ?>
                      </td>
                    <?php } ?>
                    <td style="padding:9px 12px;text-align:center;font-weight:800;color:#1a73e8;border-bottom:1px solid #e8ecf4;"><?php echo $hall_total; ?></td>
                  </tr>
              <?php } ?>
              <!-- Grand total row -->
              <?php
              $grand_total = 0;
              echo '<tr style="background:#0d47a1;color:white;font-weight:800;">';
              echo '<td style="padding:10px 12px;">Grand Total</td>';
              for($mo = 1; $mo <= 12; $mo++){
                  $mo_total = 0;
                  foreach($halls as $h) $mo_total += $hall_monthly[$h][$mo]['count'];
                  $grand_total += $mo_total;
                  echo '<td style="padding:10px 6px;text-align:center;">'.($mo_total > 0 ? $mo_total : '—').'</td>';
              }
              echo '<td style="padding:10px 12px;text-align:center;">'.$grand_total.'</td>';
              echo '</tr>';
              ?>
              </tbody>
            </table>
          </div>
          <p style="font-size:0.75rem;color:#718096;margin-top:8px;">
            <i class="fas fa-info-circle me-1"></i>Hover a number to see event names. Only confirmed bookings counted.
          </p>
        </div>

        <!-- Detailed event log by month -->
        <div style="margin-top:24px;">
          <h6 style="font-weight:700;color:#1a202c;font-size:0.92rem;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <span style="background:#fef3c7;color:#92400e;border-radius:8px;padding:4px 10px;font-size:0.8rem;">📅</span>
            Detailed Event Log — <?php echo $cur_year; ?>
          </h6>
          <?php
          $event_res = mysqli_query($conn,
              "SELECT b.booking_date, b.hall_name, b.event_name, b.start_time, b.end_time, b.chairs, u.name uname
               FROM bookings b JOIN users u ON b.user_id=u.id
               WHERE YEAR(b.booking_date)=$cur_year AND b.status='Confirmed'
               ORDER BY b.booking_date ASC, b.start_time ASC");
          $events_by_month = [];
          while($ev = mysqli_fetch_assoc($event_res)){
              $mo = (int)date('n', strtotime($ev['booking_date']));
              $events_by_month[$mo][] = $ev;
          }
          foreach($events_by_month as $mo => $evs){ ?>
            <div style="margin-bottom:18px;break-inside:avoid;">
              <div style="background:#1a73e8;color:white;padding:7px 14px;border-radius:8px 8px 0 0;font-weight:700;font-size:0.85rem;">
                <?php echo $month_names[$mo-1].' '.$cur_year; ?> — <?php echo count($evs); ?> event(s)
              </div>
              <table style="border-collapse:collapse;width:100%;font-size:0.78rem;border:1px solid #e8ecf4;border-top:none;">
                <thead>
                  <tr style="background:#f8faff;">
                    <th style="padding:7px 10px;color:#718096;font-weight:700;border-bottom:1px solid #e8ecf4;text-align:left;">Date</th>
                    <th style="padding:7px 10px;color:#718096;font-weight:700;border-bottom:1px solid #e8ecf4;text-align:left;">Hall</th>
                    <th style="padding:7px 10px;color:#718096;font-weight:700;border-bottom:1px solid #e8ecf4;text-align:left;">Event</th>
                    <th style="padding:7px 10px;color:#718096;font-weight:700;border-bottom:1px solid #e8ecf4;text-align:left;">Time</th>
                    <th style="padding:7px 10px;color:#718096;font-weight:700;border-bottom:1px solid #e8ecf4;text-align:left;">Requester</th>
                    <th style="padding:7px 10px;color:#718096;font-weight:700;border-bottom:1px solid #e8ecf4;text-align:center;">Chairs</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach($evs as $ev){ ?>
                  <tr>
                    <td style="padding:7px 10px;border-bottom:1px solid #f1f5f9;font-weight:600;"><?php echo date('d M', strtotime($ev['booking_date'])); ?></td>
                    <td style="padding:7px 10px;border-bottom:1px solid #f1f5f9;color:#1a73e8;font-weight:600;"><?php echo htmlspecialchars($ev['hall_name']); ?></td>
                    <td style="padding:7px 10px;border-bottom:1px solid #f1f5f9;"><?php echo htmlspecialchars($ev['event_name'] ?? '—'); ?></td>
                    <td style="padding:7px 10px;border-bottom:1px solid #f1f5f9;color:#718096;"><?php echo date('h:i A',strtotime($ev['start_time'])).' – '.date('h:i A',strtotime($ev['end_time'])); ?></td>
                    <td style="padding:7px 10px;border-bottom:1px solid #f1f5f9;"><?php echo htmlspecialchars($ev['uname']); ?></td>
                    <td style="padding:7px 10px;border-bottom:1px solid #f1f5f9;text-align:center;font-weight:600;"><?php echo $ev['chairs']; ?></td>
                  </tr>
                <?php } ?>
                </tbody>
              </table>
            </div>
          <?php }
          if(empty($events_by_month)){
              echo '<p style="color:#718096;font-style:italic;">No confirmed bookings in '.$cur_year.'.</p>';
          } ?>
          <div style="text-align:right;margin-top:10px;font-size:0.75rem;color:#b0bec5;" class="no-print">
            <i class="fas fa-print me-1"></i>Use the Print button above to export this report as PDF.
          </div>
        </div>

      </div><!-- /card-body-pro -->
    </div><!-- /card-pro -->
  </div><!-- /stats section -->

</div><!-- /container-fluid -->

<!-- ════════════════════ CAMERA MODAL ════════════════════ -->
<div id="cameraModal">
  <div class="cam-modal-box">
    <div class="modal-header-row">
      <h5><i class="fas fa-camera me-2" style="color:#1a73e8;"></i>Capture Hall Photo</h5>
      <button class="close-x" onclick="closeCamera()">&times;</button>
    </div>
    <video id="video" autoplay playsinline></video>
    <canvas id="canvas" style="display:none;"></canvas>
    <img id="capturedImage" alt="Captured photo">
    <div class="modal-footer-row">
      <button id="captureBtn"  onclick="capturePhoto()" class="mbtn mbtn-primary"><i class="fas fa-camera me-1"></i>Capture</button>
      <button id="retakeBtn"   onclick="startCamera()"  class="mbtn mbtn-secondary" style="display:none;"><i class="fas fa-redo me-1"></i>Retake</button>
      <button id="uploadCamBtn" onclick="uploadCameraPhoto()" class="mbtn mbtn-success" style="display:none;"><i class="fas fa-upload me-1"></i>Upload</button>
      <button onclick="closeCamera()" class="mbtn mbtn-danger"><i class="fas fa-times me-1"></i>Cancel</button>
    </div>
    <button id="switchCameraBtn" onclick="switchCamera()"><i class="fas fa-sync-alt me-1"></i>Switch Camera (Front / Back)</button>
    <!-- Hidden form used by camera AJAX upload -->
    <form id="cameraUploadForm" method="POST" enctype="multipart/form-data" style="display:none;">
      <input type="hidden" name="booking_id" id="cameraBookingId">
      <input type="file"   name="hall_img"   id="cameraFileInput" accept="image/*">
    </form>
  </div>
</div>

<!-- ════════════════════ IMAGE VIEWER MODAL ════════════════════ -->
<div id="imageViewerModal">
  <div class="img-viewer-inner">
    <button class="close-viewer" onclick="closeImageViewer()">&times; Close</button>
    <img id="viewerImage" src="" alt="Full-size event photo">
  </div>
</div>

<!-- ════════════════════ SCRIPTS ════════════════════ -->
<script>
// ── Table filter ──────────────────────────────────────────────────────────
function filterTable(){
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('filterStatus').value.toLowerCase();
    document.querySelectorAll('#bookingsTable tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = (text.includes(search) && (status === '' || text.includes(status))) ? '' : 'none';
    });
}

// ── Camera state ──────────────────────────────────────────────────────────
let currentStream     = null;
let currentBookingId  = null;
let capturedBlob      = null;
let usingFrontCamera  = false;

function openCamera(bookingId){
    currentBookingId = bookingId;
    document.getElementById('cameraBookingId').value = bookingId;
    document.getElementById('cameraModal').classList.add('open');
    startCamera();
}

async function startCamera(){
    try {
        if(currentStream) currentStream.getTracks().forEach(t => t.stop());
        const constraints = {
            video: {
                facingMode: usingFrontCamera ? 'user' : 'environment',
                width:  { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        document.getElementById('video').srcObject = currentStream;

        // Reset UI
        document.getElementById('video').style.display          = 'block';
        document.getElementById('capturedImage').style.display  = 'none';
        document.getElementById('captureBtn').style.display     = 'inline-flex';
        document.getElementById('retakeBtn').style.display      = 'none';
        document.getElementById('uploadCamBtn').style.display   = 'none';
    } catch(err){
        alert('Camera error: ' + err.message + '\n\nPlease grant camera permission and try again.');
    }
}

function capturePhoto(){
    const video  = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx    = canvas.getContext('2d');
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(function(blob){
        capturedBlob = blob;
        const url = URL.createObjectURL(blob);
        const img = document.getElementById('capturedImage');
        img.src             = url;
        img.style.display   = 'block';

        document.getElementById('video').style.display        = 'none';
        document.getElementById('captureBtn').style.display   = 'none';
        document.getElementById('retakeBtn').style.display    = 'inline-flex';
        document.getElementById('uploadCamBtn').style.display = 'inline-flex';

        if(currentStream) { currentStream.getTracks().forEach(t => t.stop()); currentStream = null; }
    }, 'image/jpeg', 0.9);
}

function uploadCameraPhoto(){
    if(!capturedBlob){ alert('No photo captured yet.'); return; }

    const file     = new File([capturedBlob], 'camera_' + Date.now() + '.jpg', { type: 'image/jpeg' });
    const formData = new FormData();
    formData.append('booking_id', currentBookingId);
    formData.append('hall_img',   file);
    formData.append('upload_photo', '1');

    const btn = document.getElementById('uploadCamBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading…';
    btn.disabled  = true;

    fetch(window.location.href, { method: 'POST', body: formData })
        .then(res => {
            if(res.ok){ alert('Photo uploaded successfully!'); location.reload(); }
            else { throw new Error('Server error'); }
        })
        .catch(() => {
            alert('Upload failed. Please check your connection and try again.');
            btn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload';
            btn.disabled  = false;
        });

    closeCamera();
}

function switchCamera(){
    usingFrontCamera = !usingFrontCamera;
    startCamera();
}

function closeCamera(){
    if(currentStream){ currentStream.getTracks().forEach(t => t.stop()); currentStream = null; }
    document.getElementById('cameraModal').classList.remove('open');
    capturedBlob = null;
}

// ── Image Viewer ─────────────────────────────────────────────────────────
function viewImage(path){
    document.getElementById('viewerImage').src = path;
    document.getElementById('imageViewerModal').classList.add('open');
}

function closeImageViewer(){
    document.getElementById('imageViewerModal').classList.remove('open');
}

// ── Close modals on backdrop click ────────────────────────────────────────
document.getElementById('cameraModal').addEventListener('click', function(e){
    if(e.target === this) closeCamera();
});
document.getElementById('imageViewerModal').addEventListener('click', function(e){
    if(e.target === this) closeImageViewer();
});

// ── Keyboard shortcuts ────────────────────────────────────────────────────
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape'){
        closeCamera();
        closeImageViewer();
    }
});

// ── Graceful degradation: hide camera buttons if API unavailable ──────────
if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
    document.querySelectorAll('.camera-btn, .retake-btn').forEach(btn => btn.style.display = 'none');
}
</script>

<?php footer_info(); ?>
