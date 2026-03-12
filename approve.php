<?php
include('db.php');
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin'){
    header("Location: admin_login.php"); exit();
}

if(isset($_GET['id']) && isset($_GET['st'])){
    $id = (int)$_GET['id'];
    $allowed = ['Confirmed', 'Rejected'];
    $st = in_array($_GET['st'], $allowed) ? $_GET['st'] : 'Rejected';

    mysqli_query($conn, "UPDATE bookings SET status='$st' WHERE id=$id");

    // In production: send email notification here using mail() or PHPMailer
    // Example: mail($user_email, "Booking $st", "Your booking has been $st by admin.");
}

header("Location: admin_dash.php");
exit();
?>