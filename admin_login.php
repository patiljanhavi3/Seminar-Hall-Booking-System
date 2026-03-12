<?php
include('db.php');
// ALL LOGIC BEFORE HTML
$login_error = '';
if(isset($_POST['alogin'])){
    $e = mysqli_real_escape_string($conn, $_POST['email']);
    $p = $_POST['password'];
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$e' AND password='$p' AND role='admin'");
    if($u = mysqli_fetch_assoc($res)){
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['role']    = 'admin';
        $_SESSION['name']    = $u['name'];
        header("Location: admin_dash.php");
        exit();
    }
    $login_error = "Unauthorized. Check credentials.";
}
head("Staff Login");
?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);padding:20px;">
  <div style="background:#1e293b;border:1px solid rgba(255,255,255,0.07);border-radius:24px;padding:52px 44px;width:100%;max-width:440px;box-shadow:0 24px 60px rgba(0,0,0,0.5);">
    <div style="text-align:center;margin-bottom:36px;">
      <div style="width:56px;height:56px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <i class="fas fa-user-shield" style="color:white;font-size:1.5rem;"></i>
      </div>
      <h2 style="font-weight:800;color:white;margin-bottom:4px;font-size:1.5rem;">Staff Portal</h2>
      <p style="color:#64748b;font-size:0.88rem;">Administrative Access Only</p>
    </div>
    <?php if($login_error){ ?>
      <div style="background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:0.87rem;">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $login_error; ?>
      </div>
    <?php } ?>
    <form method="POST">
      <div style="margin-bottom:16px;">
        <label style="display:block;font-size:0.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:6px;">Admin Email</label>
        <input type="email" name="email" style="width:100%;background:#0f172a;border:1.5px solid #334155;border-radius:10px;padding:12px 14px;color:white;font-size:0.93rem;font-family:inherit;" placeholder="admin@mmcoe.edu" required>
      </div>
      <div style="margin-bottom:28px;">
        <label style="display:block;font-size:0.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.6px;margin-bottom:6px;">Password</label>
        <input type="password" name="password" style="width:100%;background:#0f172a;border:1.5px solid #334155;border-radius:10px;padding:12px 14px;color:white;font-size:0.93rem;font-family:inherit;" placeholder="••••••••" required>
      </div>
      <button type="submit" name="alogin" style="width:100%;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:white;border:none;border-radius:12px;padding:14px;font-size:0.97rem;font-weight:700;cursor:pointer;font-family:inherit;">
        <i class="fas fa-lock-open me-2"></i>Access Dashboard
      </button>
    </form>
    <div style="text-align:center;margin-top:22px;">
      <a href="index.php" style="color:#475569;font-size:0.82rem;text-decoration:none;">← Student Portal</a>
    </div>
  </div>
</div>
</body></html>