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
    $msg = $r ? "<div style='background:#d1fae5;color:#065f46;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-check-circle me-2'></i>Holiday added.</div>"
              : "<div style='background:#fff3cd;color:#92400e;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'>Holiday already exists for this date.</div>";
}

// Delete Holiday
if(isset($_GET['del_hol'])){
    $hid = (int)$_GET['del_hol'];
    mysqli_query($conn, "DELETE FROM holidays WHERE id=$hid");
    header("Location: admin_dash.php"); exit();
}

// Photo Upload
if(isset($_POST['upload_photo']) && isset($_FILES['hall_img'])){
    $bid = (int)$_POST['booking_id'];
    if(!is_dir("uploads/")) mkdir("uploads/", 0777, true);
    $fname  = time()."_".basename($_FILES['hall_img']['name']);
    $target = "uploads/".$fname;
    $ext    = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
    if(in_array($ext,['jpg','jpeg','png','webp']) && move_uploaded_file($_FILES['hall_img']['tmp_name'],$target)){
        mysqli_query($conn, "UPDATE bookings SET event_photo='$fname' WHERE id=$bid");
        $msg = "<div style='background:#d1fae5;color:#065f46;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-check-circle me-2'></i>Photo uploaded.</div>";
    } else {
        $msg = "<div style='background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'>Upload failed. Use JPG/PNG/WEBP.</div>";
    }
}

// Stats
$total     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings"))['c'];
$pending   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE status='Pending'"))['c'];
$confirmed = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE status='Confirmed'"))['c'];
$rejected  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE status='Rejected'"))['c'];

// HTML STARTS HERE
head("Admin Dashboard");
?>
<div class="topbar" style="background:#0f172a;border-bottom:2px solid #6366f1;">
  <div class="topbar-brand" style="color:#a5b4fc;"><i class="fas fa-tools me-2"></i>MMCOE <span style="color:#818cf8;">Admin</span></div>
  <div style="display:flex;align-items:center;gap:16px;">
    <span style="font-size:0.85rem;color:#64748b;">🛡️ <b style="color:#94a3b8;"><?php echo htmlspecialchars($_SESSION['name']); ?></b></span>
    <a href="logout.php" style="background:#dc2626;color:white;border-radius:8px;padding:7px 16px;font-size:0.82rem;font-weight:700;text-decoration:none;">
      <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
  </div>
</div>

<div class="container-fluid px-4 py-4">
  <!-- Stats -->
  <div class="row g-3 mb-4">
    <?php
    $stats = [
      ['Total Requests',$total,'#1a73e8','#e8f0fe','fas fa-calendar-alt'],
      ['Pending',$pending,'#d97706','#fff3cd','fas fa-hourglass-half'],
      ['Confirmed',$confirmed,'#0d9f6e','#d1fae5','fas fa-check-circle'],
      ['Rejected',$rejected,'#dc2626','#fee2e2','fas fa-times-circle'],
    ];
    foreach($stats as $s){ ?>
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
    <!-- Left Panel -->
    <div class="col-lg-4">
      <div class="card-pro mb-4">
        <div class="card-header-pro" style="background:linear-gradient(90deg,#dc2626,#b91c1c);"><i class="fas fa-calendar-times me-2"></i>Holiday Manager</div>
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
              <i class="fas fa-plus me-1"></i> Block This Date
            </button>
          </form>
          <div style="margin-top:18px;max-height:220px;overflow-y:auto;">
            <?php
            $hols = mysqli_query($conn, "SELECT * FROM holidays ORDER BY h_date ASC");
            if(mysqli_num_rows($hols) == 0){ echo '<p style="color:#718096;font-size:0.85rem;text-align:center;padding:12px;">No holidays set.</p>'; }
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

      <div class="card-pro">
        <div class="card-header-pro" style="background:linear-gradient(90deg,#7c3aed,#6d28d9);"><i class="fas fa-chart-bar me-2"></i>Hall Usage</div>
        <div class="card-body-pro">
          <?php
          $halls = ["Seminar Hall 405","IMERT Seminar Hall","Main Auditorium","Civil Seminar Hall"];
          foreach($halls as $hall){
            $esc  = mysqli_real_escape_string($conn,$hall);
            $cnt  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE hall_name='$esc' AND status='Confirmed'"))['c'];
            $tot  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE hall_name='$esc'"))['c'];
            $pct  = $tot > 0 ? round(($cnt/$tot)*100) : 0;
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
    </div>

    <!-- Bookings Table -->
    <div class="col-lg-8">
      <div class="card-pro">
        <div class="card-header-pro" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
          <span><i class="fas fa-list me-2"></i>All Booking Requests</span>
          <div style="display:flex;gap:8px;">
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search..." style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:8px;padding:6px 12px;color:white;font-size:0.82rem;font-family:inherit;">
            <select id="filterStatus" onchange="filterTable()" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.25);border-radius:8px;padding:6px 10px;color:white;font-size:0.82rem;font-family:inherit;">
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
            $res = mysqli_query($conn, "SELECT b.*, u.name uname, u.phone, u.email uemail FROM bookings b JOIN users u ON b.user_id=u.id ORDER BY FIELD(b.status,'Pending','Confirmed','Rejected','Cancelled'), b.id DESC");
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
                  <div style="font-weight:600;font-size:0.84rem;"><?php echo date('d M Y',strtotime($r['booking_date'])); ?></div>
                  <div style="font-size:0.75rem;color:#718096;"><?php echo date('h:i A',strtotime($r['start_time'])); ?> – <?php echo date('h:i A',strtotime($r['end_time'])); ?></div>
                </td>
                <td style="font-weight:600;"><?php echo $r['chairs']; ?></td>
                <td><span class="badge-status badge-<?php echo $r['status']; ?>"><?php echo $r['status']; ?></span></td>
                <td>
                  <?php if($r['status']=='Pending'){ ?>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                      <a href="approve.php?id=<?php echo $r['id']; ?>&st=Confirmed" class="btn-success-pro" onclick="return confirm('Confirm this booking?')" style="text-decoration:none;font-size:0.78rem;padding:6px 12px;">
                        <i class="fas fa-check me-1"></i>Confirm
                      </a>
                      <a href="approve.php?id=<?php echo $r['id']; ?>&st=Rejected" class="btn-danger-pro" onclick="return confirm('Reject this booking?')" style="text-decoration:none;font-size:0.78rem;padding:6px 12px;">
                        <i class="fas fa-times me-1"></i>Reject
                      </a>
                    </div>
                  <?php } elseif($r['status']=='Confirmed'){ ?>
                    <div style="display:flex;flex-direction:column;gap:5px;">
                      <a href="report.php?id=<?php echo $r['id']; ?>" target="_blank" style="background:#e8f0fe;color:#1a73e8;border-radius:7px;padding:5px 10px;font-size:0.76rem;font-weight:700;text-decoration:none;">
                        <i class="fas fa-file-invoice me-1"></i>Receipt
                      </a>
                      <?php if(!$r['event_photo']){ ?>
                        <form method="POST" enctype="multipart/form-data" style="display:flex;gap:4px;align-items:center;">
                          <input type="hidden" name="booking_id" value="<?php echo $r['id']; ?>">
                          <input type="file" name="hall_img" accept=".jpg,.jpeg,.png,.webp" style="font-size:0.72rem;width:100px;" required>
                          <button type="submit" name="upload_photo" style="background:#7c3aed;color:white;border:none;border-radius:7px;padding:5px 8px;font-size:0.72rem;cursor:pointer;"><i class="fas fa-upload"></i></button>
                        </form>
                      <?php } else { ?>
                        <div style="display:flex;align-items:center;gap:6px;">
                          <img src="uploads/<?php echo htmlspecialchars($r['event_photo']); ?>" style="width:55px;height:40px;object-fit:cover;border-radius:6px;">
                          <span style="font-size:0.74rem;color:#0d9f6e;font-weight:600;">✔ Photo</span>
                        </div>
                      <?php } ?>
                    </div>
                  <?php } else { echo '<span style="color:#b0bec5;font-size:0.78rem;">—</span>'; } ?>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function filterTable(){
  const search = document.getElementById('searchInput').value.toLowerCase();
  const status = document.getElementById('filterStatus').value.toLowerCase();
  document.querySelectorAll('#bookingsTable tbody tr').forEach(row=>{
    const text = row.innerText.toLowerCase();
    row.style.display = (text.includes(search) && (status===''||text.includes(status))) ? '' : 'none';
  });
}
</script>

<?php footer_info(); ?>