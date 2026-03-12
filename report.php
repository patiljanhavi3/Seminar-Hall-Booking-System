<?php
include('db.php');
// ALL LOGIC BEFORE HTML
if(!isset($_GET['id'])){ header("Location: index.php"); exit(); }
$id  = (int)$_GET['id'];
$res = mysqli_query($conn, "SELECT b.*, u.name uname, u.email uemail, u.phone uphone FROM bookings b JOIN users u ON b.user_id=u.id WHERE b.id=$id AND b.status='Confirmed'");
if(mysqli_num_rows($res) == 0){
    die("<div style='font-family:Inter,sans-serif;padding:40px;color:#991b1b;text-align:center;'>Booking not found or not yet confirmed.</div>");
}
$d = mysqli_fetch_assoc($res);
// HTML STARTS HERE
head("Booking Receipt #".$d['id']);
?>
<div style="min-height:100vh;background:#f4f6fb;display:flex;align-items:center;justify-content:center;padding:30px;">
  <div style="background:white;border-radius:20px;max-width:620px;width:100%;box-shadow:0 12px 40px rgba(0,0,0,0.1);overflow:hidden;">
    <div style="background:linear-gradient(135deg,#1a73e8,#0d47a1);color:white;padding:36px;text-align:center;">
      <div style="width:60px;height:60px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
        <i class="fas fa-check" style="font-size:1.6rem;"></i>
      </div>
      <h2 style="font-weight:800;margin-bottom:4px;font-size:1.4rem;">Booking Confirmed!</h2>
      <p style="opacity:0.75;font-size:0.88rem;">MMCOE Seminar Hall Booking System</p>
      <div style="background:rgba(255,255,255,0.15);border-radius:10px;padding:8px 18px;display:inline-block;margin-top:10px;font-size:0.85rem;font-weight:700;">
        Booking ID: #<?php echo str_pad($d['id'],5,'0',STR_PAD_LEFT); ?>
      </div>
    </div>
    <div style="padding:32px;">
      <?php
      $rows = [
        ['fas fa-user','Booked By', htmlspecialchars($d['uname'])],
        ['fas fa-phone','Contact', htmlspecialchars($d['contact_phone'] ?: $d['uphone'])],
        ['fas fa-envelope','Email', htmlspecialchars($d['contact_email'] ?: $d['uemail'])],
        ['fas fa-tag','Event', htmlspecialchars($d['event_name'] ?? '—')],
        ['fas fa-university','Hall', htmlspecialchars($d['hall_name'])],
        ['fas fa-calendar','Date', date('l, d F Y', strtotime($d['booking_date']))],
        ['fas fa-clock','Time', date('h:i A',strtotime($d['start_time'])).' – '.date('h:i A',strtotime($d['end_time']))],
        ['fas fa-chair','Chairs', $d['chairs']],
        ['fas fa-calendar-check','Booked On', date('d M Y, h:i A', strtotime($d['created_at']))],
      ];
      foreach($rows as $row){ ?>
        <div style="display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid #f1f5f9;">
          <div style="width:34px;height:34px;background:#e8f0fe;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="<?php echo $row[0]; ?>" style="color:#1a73e8;font-size:0.85rem;"></i>
          </div>
          <div>
            <div style="font-size:0.73rem;font-weight:700;color:#718096;text-transform:uppercase;letter-spacing:0.5px;"><?php echo $row[1]; ?></div>
            <div style="font-size:0.92rem;font-weight:600;color:#1a202c;"><?php echo $row[2]; ?></div>
          </div>
        </div>
      <?php } ?>
      <?php if($d['event_photo']){ ?>
        <div style="margin-top:16px;">
          <div style="font-size:0.78rem;font-weight:700;color:#718096;text-transform:uppercase;margin-bottom:8px;">Hall Photo</div>
          <img src="uploads/<?php echo htmlspecialchars($d['event_photo']); ?>" style="width:100%;border-radius:12px;max-height:200px;object-fit:cover;">
        </div>
      <?php } ?>
      <div style="background:#d1fae5;border-radius:12px;padding:14px 18px;margin-top:20px;text-align:center;">
        <i class="fas fa-check-circle" style="color:#065f46;margin-right:8px;"></i>
        <span style="color:#065f46;font-weight:700;font-size:0.9rem;">Officially approved by Admin</span>
      </div>
      <div style="display:flex;gap:12px;margin-top:20px;">
        <button onclick="window.print()" style="flex:1;background:#1a73e8;color:white;border:none;border-radius:10px;padding:13px;font-size:0.9rem;font-weight:700;cursor:pointer;font-family:inherit;">
          <i class="fas fa-print me-2"></i>Print Receipt
        </button>
        <a href="user_dash.php" style="flex:1;background:#f1f5f9;color:#1a202c;border-radius:10px;padding:13px;font-size:0.9rem;font-weight:700;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;">
          <i class="fas fa-arrow-left"></i>Back
        </a>
      </div>
    </div>
  </div>
</div>
</body></html>