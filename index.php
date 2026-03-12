<?php
include('db.php');
// ALL LOGIC BEFORE ANY OUTPUT
$login_error = '';
if(isset($_POST['login'])){
    $e = mysqli_real_escape_string($conn, $_POST['email']);
    $p = $_POST['password'];
    $res = mysqli_query($conn, "SELECT * FROM users WHERE email='$e' AND password='$p' AND role='user'");
    if($u = mysqli_fetch_assoc($res)){
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['role']    = 'user';
        $_SESSION['name']    = $u['name'];
        $_SESSION['email']   = $u['email'];
        header("Location: user_dash.php");
        exit();
    }
    $login_error = "Invalid email or password. Please try again.";
}
// HTML STARTS HERE
head("Student Login");
?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a73e8 0%,#0d47a1 100%);padding:20px;">
  <div style="display:flex;max-width:900px;width:100%;border-radius:24px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,0.25);">
    <div style="flex:1;background:white;padding:48px 40px;">
      <div style="margin-bottom:32px;">
        <div style="width:52px;height:52px;background:#1a73e8;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
          <i class="fas fa-university" style="color:white;font-size:1.4rem;"></i>
        </div>
        <h2 style="font-weight:800;color:#1a202c;margin-bottom:4px;font-size:1.6rem;">Student Portal</h2>
        <p style="color:#718096;font-size:0.9rem;">MMCOE Seminar Hall Booking System</p>
      </div>
      <?php if($login_error){ ?>
        <div style="background:#fee2e2;color:#991b1b;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:0.87rem;font-weight:500;">
          <i class="fas fa-exclamation-circle me-2"></i><?php echo $login_error; ?>
        </div>
      <?php } ?>
      <form method="POST">
        <div style="margin-bottom:16px;">
          <label class="form-label-pro">Email Address</label>
          <input type="email" name="email" class="form-field" placeholder="student@mmcoe.edu.in" required>
        </div>
        <div style="margin-bottom:24px;">
          <label class="form-label-pro">Password</label>
          <input type="password" name="password" class="form-field" placeholder="Enter your password" required>
        </div>
        <button type="submit" name="login" class="btn-primary-pro" style="width:100%;justify-content:center;padding:14px;">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
      </form>
      <div style="text-align:center;margin-top:20px;font-size:0.87rem;color:#718096;">
        New student? <a href="signup.php" style="color:#1a73e8;font-weight:600;text-decoration:none;">Create Account</a>
      </div>
      <div style="text-align:center;margin-top:10px;font-size:0.82rem;">
        <a href="admin_login.php" style="color:#b0bec5;text-decoration:none;">Staff / Admin Login →</a>
      </div>
    </div>
    <div style="flex:1;background:linear-gradient(160deg,#0d47a1,#1565c0);padding:48px 36px;color:white;display:flex;flex-direction:column;justify-content:center;">
      <h4 style="font-weight:800;margin-bottom:8px;font-size:1.3rem;">
        <i class="fas fa-exclamation-triangle me-2" style="color:#fcd34d;"></i>Important Notices
      </h4>
      <p style="opacity:0.7;font-size:0.85rem;margin-bottom:28px;">Please read before booking</p>
      <div style="display:flex;flex-direction:column;gap:14px;">
        <?php
        $warnings = [
          ["fas fa-ban","Misuse results in suspension of booking privileges"],
          ["fas fa-clone","Fake or duplicate bookings will be reported to admin"],
          ["fas fa-tools","Users are liable for any damage to hall equipment"],
          ["fas fa-clock","Bookings must be submitted at least 24 hours in advance"],
          ["fas fa-check-circle","Approval from coordinator is mandatory"],
        ];
        foreach($warnings as $w){ ?>
          <div style="display:flex;align-items:flex-start;gap:12px;background:rgba(255,255,255,0.08);border-radius:10px;padding:13px;">
            <i class="<?php echo $w[0]; ?>" style="color:#fcd34d;margin-top:2px;min-width:16px;"></i>
            <span style="font-size:0.85rem;opacity:0.9;"><?php echo $w[1]; ?></span>
          </div>
        <?php } ?>
      </div>
      <div style="margin-top:28px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.15);">
        <p style="font-size:0.8rem;opacity:0.5;margin-bottom:4px;">Login Issues? Contact:</p>
        <p style="font-size:0.85rem;opacity:0.8;"><i class="fas fa-envelope me-2"></i>admin@mmcoe.edu</p>
      </div>
    </div>
  </div>
</div>
</body></html>