<?php
include('db.php');
header('Content-Type: application/json');

$hall = isset($_GET['hall']) ? mysqli_real_escape_string($conn, $_GET['hall']) : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

if(empty($hall) || empty($date)){
    echo json_encode(['bookings' => [], 'holiday' => null]);
    exit();
}

// Check holiday
$h = mysqli_query($conn, "SELECT reason FROM holidays WHERE h_date='$date'");
if(mysqli_num_rows($h) > 0){
    $row = mysqli_fetch_assoc($h);
    echo json_encode(['bookings' => [], 'holiday' => $row['reason']]);
    exit();
}

// Get confirmed bookings for that hall and date
$res = mysqli_query($conn, "SELECT start_time, end_time, status FROM bookings WHERE hall_name='$hall' AND booking_date='$date' AND status IN ('Confirmed','Pending') ORDER BY start_time ASC");

$bookings = [];
while($r = mysqli_fetch_assoc($res)){
    $bookings[] = [
        'start'  => date('h:i A', strtotime($r['start_time'])),
        'end'    => date('h:i A', strtotime($r['end_time'])),
        'status' => $r['status']
    ];
}

echo json_encode(['bookings' => $bookings, 'holiday' => null]);
?>