<?php
include('db.php');
// ALL LOGIC BEFORE HTML
$msg = '';
$msg_type = '';
if(isset($_POST['reg'])){
    $n  = mysqli_real_escape_string($conn, $_POST['name']);
    $e  = mysqli_real_escape_string($conn, $_POST['email']);
    $ph = mysqli_real_escape_string($conn, $_POST['phone']);
    $p  = $_POST['password'];
    $p2 = $_POST['password2'];
    if($p !== $p2){
        $msg = "Passwords do not match!"; $msg_type = 'error';
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$e'");
        if(mysqli_num_rows($check) > 0){
            $msg = "Email already registered. <a href='index.php' style='color:#92400e;font-weight:700;'>Login instead</a>"; $msg_type = 'warning';
        } else {
            mysqli_query($conn, "INSERT INTO users (name,email,phone,password,role) VALUES ('$n','$e','$ph','$p','user')");
            $msg = "Account created successfully! <a href='index.php' style='color:#065f46;font-weight:700;'>Login now →</a>"; $msg_type = 'success';
        }
    }
}
head("Student Register");
?>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1a73e8 0%,#0d47a1 100%);padding:20px;">
  <div style="background:white;border-radius:24px;padding:48px 44px;width:100%;max-width:500px;box-shadow:0 24px 60px rgba(0,0,0,0.2);">
    <div style="text-align:center;margin-bottom:32px;">
      <div style="width:52px;height:52px;background:#0d9f6e;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
        <i class="fas fa-user-graduate" style="color:white;font-size:1.4rem;"></i>
      </div>
      <h2 style="font-weight:800;color:#1a202c;margin-bottom:4px;font-size:1.5rem;">Create Account</h2>
      <p style="color:#718096;font-size:0.88rem;">Join the MMCOE booking portal</p>
    </div>
    <?php if($msg){
      $bg = $msg_type=='success' ? '#d1fae5' : ($msg_type=='warning' ? '#fff3cd' : '#fee2e2');
      $clr = $msg_type=='success' ? '#065f46' : ($msg_type=='warning' ? '#92400e' : '#991b1b');
      echo "<div style='background:$bg;color:$clr;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:0.87rem;'>$msg</div>";
    } ?>
    <form method="POST">
      <div style="margin-bottom:14px;">
        <label class="form-label-pro">Full Name</label>
        <input type="text" name="name" class="form-field" placeholder="Your full name" required>
      </div>
      <div style="margin-bottom:14px;">
        <label class="form-label-pro">College Email</label>
        <input type="email" name="email" class="form-field" placeholder="name@mmcoe.edu.in" required>
      </div>
      <div style="margin-bottom:14px;">
        <label class="form-label-pro">Phone Number</label>
        <input type="tel" name="phone" class="form-field" placeholder="+91 XXXXX XXXXX" required>
      </div>
      <div style="margin-bottom:14px;">
        <label class="form-label-pro">Password</label>
        <input type="password" name="password" class="form-field" placeholder="Create a password" required>
      </div>
      <div style="margin-bottom:24px;">
        <label class="form-label-pro">Confirm Password</label>
        <input type="password" name="password2" class="form-field" placeholder="Repeat password" required>
      </div>
      <button type="submit" name="reg" class="btn-primary-pro" style="width:100%;justify-content:center;padding:14px;background:#0d9f6e;">
        <i class="fas fa-user-plus"></i> Register
      </button>
    </form>
    <div style="text-align:center;margin-top:18px;font-size:0.87rem;color:#718096;">
      Already have an account? <a href="index.php" style="color:#1a73e8;font-weight:600;text-decoration:none;">Sign In</a>
    </div>
  </div>
</div>
</body></html>