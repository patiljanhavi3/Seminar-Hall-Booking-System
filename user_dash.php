<?php
include('db.php');
// ALL LOGIC BEFORE HTML
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user'){
    header("Location: index.php"); exit();
}
$uid   = $_SESSION['user_id'];
$uname = $_SESSION['name'];
$msg   = '';

// Cancel Booking
if(isset($_GET['cancel_id'])){
    $cid = (int)$_GET['cancel_id'];
    $chk = mysqli_query($conn, "SELECT id FROM bookings WHERE id=$cid AND user_id=$uid AND status='Pending'");
    if(mysqli_num_rows($chk) > 0){
        mysqli_query($conn, "UPDATE bookings SET status='Cancelled' WHERE id=$cid");
        $msg = "<div style='background:#d1fae5;color:#065f46;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-check-circle me-2'></i>Booking cancelled successfully.</div>";
    }
}

// New Booking
if(isset($_POST['book_hall'])){
    $hall   = mysqli_real_escape_string($conn, $_POST['hall_name']);
    $date   = $_POST['b_date'];
    $start  = $_POST['s_time'];
    $end    = $_POST['e_time'];
    $chairs = (int)$_POST['chairs'];
    $cname  = mysqli_real_escape_string($conn, $_POST['contact_name']);
    $cphone = mysqli_real_escape_string($conn, $_POST['contact_phone']);
    $cemail = mysqli_real_escape_string($conn, $_POST['contact_email']);
    $ename  = mysqli_real_escape_string($conn, $_POST['event_name']);

    $hcheck = mysqli_query($conn, "SELECT reason FROM holidays WHERE h_date='$date'");
    $scheck = mysqli_query($conn, "SELECT id FROM bookings WHERE hall_name='$hall' AND booking_date='$date' AND status='Confirmed' AND ('$start' < end_time AND '$end' > start_time)");

    if($start >= $end){
        $msg = "<div style='background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-times-circle me-2'></i>End time must be after start time.</div>";
    } elseif(mysqli_num_rows($hcheck) > 0){
        $h = mysqli_fetch_assoc($hcheck);
        $msg = "<div style='background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-calendar-times me-2'></i>College holiday on this date: <b>".$h['reason']."</b></div>";
    } elseif(mysqli_num_rows($scheck) > 0){
        $msg = "<div style='background:#fff3cd;color:#92400e;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-exclamation-triangle me-2'></i>This slot is already booked. Choose a different time.</div>";
    } else {
        mysqli_query($conn, "INSERT INTO bookings (user_id,hall_name,booking_date,start_time,end_time,chairs,contact_name,contact_phone,contact_email,event_name)
            VALUES ('$uid','$hall','$date','$start','$end','$chairs','$cname','$cphone','$cemail','$ename')");
        $msg = "<div style='background:#d1fae5;color:#065f46;border-radius:10px;padding:12px 18px;margin-bottom:16px;font-weight:500;'><i class='fas fa-check-circle me-2'></i>Booking request submitted! Admin will review shortly.</div>";
    }
}

// Stats
$total     = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE user_id=$uid"))['c'];
$pending   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE user_id=$uid AND status='Pending'"))['c'];
$confirmed = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM bookings WHERE user_id=$uid AND status='Confirmed'"))['c'];

// HTML STARTS HERE
head("Student Dashboard");
?>
<div class="topbar">
  <div class="topbar-brand"><i class="fas fa-university me-2"></i>MMCOE <span>Booking</span></div>
  <div style="display:flex;align-items:center;gap:16px;">
    <span style="font-size:0.87rem;color:#718096;">👤 <b style="color:#1a202c;"><?php echo htmlspecialchars($uname); ?></b></span>
    <a href="logout.php" style="background:#fee2e2;color:#991b1b;border-radius:8px;padding:7px 16px;font-size:0.82rem;font-weight:700;text-decoration:none;">
      <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
  </div>
</div>

<div class="container-fluid px-4 py-4">
  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="stat-card" style="border-left-color:#1a73e8;">
        <div class="stat-icon" style="background:#e8f0fe;color:#1a73e8;"><i class="fas fa-calendar-alt"></i></div>
        <div><div style="font-size:0.78rem;color:#718096;font-weight:600;text-transform:uppercase;">Total Bookings</div><div style="font-size:1.6rem;font-weight:800;"><?php echo $total; ?></div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card" style="border-left-color:#d97706;">
        <div class="stat-icon" style="background:#fff3cd;color:#d97706;"><i class="fas fa-hourglass-half"></i></div>
        <div><div style="font-size:0.78rem;color:#718096;font-weight:600;text-transform:uppercase;">Pending</div><div style="font-size:1.6rem;font-weight:800;"><?php echo $pending; ?></div></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="stat-card" style="border-left-color:#0d9f6e;">
        <div class="stat-icon" style="background:#d1fae5;color:#0d9f6e;"><i class="fas fa-check-circle"></i></div>
        <div><div style="font-size:0.78rem;color:#718096;font-weight:600;text-transform:uppercase;">Confirmed</div><div style="font-size:1.6rem;font-weight:800;"><?php echo $confirmed; ?></div></div>
      </div>
    </div>
  </div>

  <?php echo $msg; ?>

  <div class="row g-4">
    <!-- Booking Form -->
    <div class="col-lg-5">
      <div class="card-pro">
        <div class="card-header-pro"><i class="fas fa-calendar-plus me-2"></i>New Hall Booking Request</div>
        <div class="card-body-pro">
          <form method="POST">
            <div style="margin-bottom:14px;">
              <label class="form-label-pro">Select Hall</label>
              <select name="hall_name" class="form-field" required>
                <option value="">-- Choose a Hall --</option>
                <option value="Seminar Hall 405">Seminar Hall 405 (AC, 60 Seats)</option>
                <option value="IMERT Seminar Hall">IMERT Seminar Hall (AC, 100 Seats)</option>
                <option value="Main Auditorium">Main Auditorium (300 Seats)</option>
                <option value="Civil Seminar Hall">Civil Seminar Hall (50 Seats)</option>
              </select>
            </div>
            <div style="margin-bottom:14px;">
              <label class="form-label-pro">Event Name</label>
              <input type="text" name="event_name" class="form-field" placeholder="e.g. Technical Seminar, Workshop" required>
            </div>
            <div class="row g-2" style="margin-bottom:14px;">
              <div class="col-6">
                <label class="form-label-pro">Date</label>
                <input type="date" name="b_date" class="form-field" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
              </div>
              <div class="col-6">
                <label class="form-label-pro">No. of Chairs</label>
                <input type="number" name="chairs" class="form-field" placeholder="e.g. 50" min="1" max="500" required>
              </div>
            </div>
            <div class="row g-2" style="margin-bottom:14px;">
              <div class="col-6">
                <label class="form-label-pro">Start Time</label>
                <input type="time" name="s_time" class="form-field" required>
              </div>
              <div class="col-6">
                <label class="form-label-pro">End Time</label>
                <input type="time" name="e_time" class="form-field" required>
              </div>
            </div>
            <hr style="border-color:#e8ecf4;margin:16px 0 14px;">
            <div style="font-size:0.78rem;font-weight:700;color:#718096;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:12px;">Contact Details</div>
            <div style="margin-bottom:12px;">
              <label class="form-label-pro">Your Name</label>
              <input type="text" name="contact_name" class="form-field" placeholder="Full name" required value="<?php echo htmlspecialchars($uname); ?>">
            </div>
            <div class="row g-2" style="margin-bottom:20px;">
              <div class="col-6">
                <label class="form-label-pro">Phone</label>
                <input type="tel" name="contact_phone" class="form-field" placeholder="+91 XXXXX" required>
              </div>
              <div class="col-6">
                <label class="form-label-pro">Email</label>
                <input type="email" name="contact_email" class="form-field" placeholder="your@email.com" required value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
              </div>
            </div>
            <button type="submit" name="book_hall" class="btn-primary-pro" style="width:100%;justify-content:center;padding:14px;font-size:1rem;">
              <i class="fas fa-paper-plane"></i> Submit Booking Request
            </button>
          </form>
        </div>
      </div>

      <!-- Slot Checker -->
      <div class="card-pro mt-4">
        <div class="card-header-pro" style="background:linear-gradient(90deg,#0d9f6e,#059669);"><i class="fas fa-clock me-2"></i>Check Slot Availability</div>
        <div class="card-body-pro">
          <div style="margin-bottom:12px;">
            <label class="form-label-pro">Hall</label>
            <select id="avail_hall" class="form-field" onchange="loadSlots()">
              <option value="Seminar Hall 405">Seminar Hall 405</option>
              <option value="IMERT Seminar Hall">IMERT Seminar Hall</option>
              <option value="Main Auditorium">Main Auditorium</option>
              <option value="Civil Seminar Hall">Civil Seminar Hall</option>
            </select>
          </div>
          <div>
            <label class="form-label-pro">Date</label>
            <input type="date" id="avail_date" class="form-field" min="<?php echo date('Y-m-d'); ?>" onchange="loadSlots()">
          </div>
          <div id="slot_display" style="margin-top:14px;font-size:0.85rem;color:#718096;">Select a hall and date to check availability.</div>
        </div>
      </div>
    </div>

    <!-- Booking History -->
    <div class="col-lg-7">
      <div class="card-pro" style="height:100%;">
        <div class="card-header-pro"><i class="fas fa-history me-2"></i>My Booking History</div>
        <div style="overflow-x:auto;">
          <?php
          $res = mysqli_query($conn, "SELECT * FROM bookings WHERE user_id=$uid ORDER BY id DESC");
          if(mysqli_num_rows($res) == 0){ ?>
            <div style="text-align:center;padding:60px 20px;color:#718096;">
              <i class="fas fa-calendar-times" style="font-size:2.5rem;opacity:0.3;display:block;margin-bottom:12px;"></i>
              No bookings yet. Make your first request!
            </div>
          <?php } else { ?>
          <table class="pro-table">
            <thead>
              <tr><th>Hall & Event</th><th>Date & Time</th><th>Chairs</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php while($r = mysqli_fetch_assoc($res)){ ?>
              <tr>
                <td>
                  <div style="font-weight:700;color:#1a202c;font-size:0.88rem;"><?php echo htmlspecialchars($r['hall_name']); ?></div>
                  <div style="font-size:0.76rem;color:#718096;"><?php echo htmlspecialchars($r['event_name'] ?? '—'); ?></div>
                </td>
                <td>
                  <div style="font-weight:600;font-size:0.85rem;"><?php echo date('d M Y', strtotime($r['booking_date'])); ?></div>
                  <div style="font-size:0.76rem;color:#718096;"><?php echo date('h:i A', strtotime($r['start_time'])); ?> – <?php echo date('h:i A', strtotime($r['end_time'])); ?></div>
                </td>
                <td style="font-weight:600;"><?php echo $r['chairs']; ?></td>
                <td><span class="badge-status badge-<?php echo $r['status']; ?>"><?php echo $r['status']; ?></span></td>
                <td>
                  <?php if($r['status'] == 'Pending'){ ?>
                    <a href="user_dash.php?cancel_id=<?php echo $r['id']; ?>" onclick="return confirm('Cancel this booking request?')"
                       style="background:#fee2e2;color:#991b1b;border-radius:7px;padding:5px 12px;font-size:0.78rem;font-weight:700;text-decoration:none;">
                       <i class="fas fa-times me-1"></i>Cancel
                    </a>
                  <?php } elseif($r['status'] == 'Confirmed'){ ?>
                    <a href="report.php?id=<?php echo $r['id']; ?>" target="_blank"
                       style="background:#e8f0fe;color:#1a73e8;border-radius:7px;padding:5px 12px;font-size:0.78rem;font-weight:700;text-decoration:none;">
                       <i class="fas fa-file-invoice me-1"></i>Receipt
                    </a>
                  <?php } else { echo '<span style="color:#b0bec5;font-size:0.78rem;">—</span>'; } ?>
                </td>
              </tr>
            <?php } ?>
            </tbody>
          </table>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Holidays + Contacts -->
  <div class="row g-4 mt-2">
    <div class="col-lg-6">
      <div class="card-pro">
        <div class="card-header-pro" style="background:linear-gradient(90deg,#dc2626,#b91c1c);"><i class="fas fa-calendar-times me-2"></i>College Holidays (Booking Blocked)</div>
        <div class="card-body-pro">
          <?php
          $hols = mysqli_query($conn, "SELECT * FROM holidays ORDER BY h_date ASC");
          if(mysqli_num_rows($hols) == 0){ echo '<p style="color:#718096;font-size:0.87rem;">No holidays listed.</p>'; }
          else { echo '<div style="display:flex;flex-wrap:wrap;gap:8px;">';
            while($h = mysqli_fetch_assoc($hols)){
              echo '<div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:8px 14px;font-size:0.82rem;">
                <div style="font-weight:700;color:#991b1b;">'.date('d M Y', strtotime($h['h_date'])).'</div>
                <div style="color:#b91c1c;">'.htmlspecialchars($h['reason']).'</div></div>';
            }
            echo '</div>';
          } ?>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card-pro">
        <div class="card-header-pro" style="background:linear-gradient(90deg,#7c3aed,#6d28d9);"><i class="fas fa-address-book me-2"></i>Contacts & Help</div>
        <div class="card-body-pro">
          <div class="row g-3">
            <div class="col-6">
              <div style="background:#f8faff;border-radius:10px;padding:12px;">
                <div style="font-size:0.75rem;font-weight:700;color:#718096;text-transform:uppercase;margin-bottom:6px;">Coordinators</div>
                <div style="font-size:0.82rem;margin-bottom:3px;"><b>Prof. Kulkarni</b> — SH 405</div>
                <div style="font-size:0.82rem;margin-bottom:3px;"><b>Prof. Deshmukh</b> — IMERT</div>
                <div style="font-size:0.82rem;"><b>Prof. Patil</b> — Auditorium</div>
              </div>
            </div>
            <div class="col-6">
              <div style="background:#f8faff;border-radius:10px;padding:12px;">
                <div style="font-size:0.75rem;font-weight:700;color:#718096;text-transform:uppercase;margin-bottom:6px;">Support</div>
                <div style="font-size:0.82rem;margin-bottom:3px;"><b>Mr. Mahesh</b> — Cleaning</div>
                <div style="font-size:0.82rem;margin-bottom:8px;"><b>Mr. Ramesh</b> — AV Setup</div>
                <div style="font-size:0.82rem;color:#1a73e8;"><i class="fas fa-key me-1"></i>Key: Security Desk</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php footer_info(); ?>

<script>
function loadSlots(){
  const hall = document.getElementById('avail_hall').value;
  const date = document.getElementById('avail_date').value;
  const display = document.getElementById('slot_display');
  if(!date){ display.innerHTML='<span style="color:#718096;">Please select a date.</span>'; return; }
  display.innerHTML='<span style="color:#1a73e8;"><i class="fas fa-spinner fa-spin me-2"></i>Checking...</span>';
  fetch('test.php?hall='+encodeURIComponent(hall)+'&date='+date)
    .then(r=>r.json())
    .then(data=>{
      if(data.holiday){
        display.innerHTML='<div style="background:#fee2e2;color:#991b1b;border-radius:8px;padding:10px 14px;font-size:0.85rem;"><i class="fas fa-ban me-2"></i><b>Holiday:</b> '+data.holiday+'</div>';
        return;
      }
      if(data.bookings.length===0){
        display.innerHTML='<div style="background:#d1fae5;color:#065f46;border-radius:8px;padding:10px 14px;font-size:0.85rem;"><i class="fas fa-check-circle me-2"></i>All slots available!</div>';
      } else {
        let html='<div style="font-size:0.78rem;font-weight:700;color:#718096;margin-bottom:8px;text-transform:uppercase;">Booked Slots:</div>';
        data.bookings.forEach(b=>{
          html+='<div style="display:flex;justify-content:space-between;align-items:center;background:#fee2e2;border-radius:8px;padding:8px 12px;margin-bottom:6px;font-size:0.83rem;">';
          html+='<span><i class="fas fa-clock me-2" style="color:#dc2626;"></i>'+b.start+' – '+b.end+'</span>';
          html+='<span style="background:#dc2626;color:white;border-radius:5px;padding:2px 8px;font-size:0.74rem;font-weight:700;">'+b.status+'</span></div>';
        });
        html+='<div style="background:#fff3cd;color:#92400e;border-radius:8px;padding:8px 12px;font-size:0.82rem;margin-top:4px;"><i class="fas fa-info-circle me-2"></i>Choose a time outside these ranges.</div>';
        display.innerHTML=html;
      }
    }).catch(()=>{ display.innerHTML='<span style="color:#dc2626;">Error loading. Try refreshing.</span>'; });
}
</script>
</body></html>