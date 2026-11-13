<?php
header('Content-Type: application/json');
include 'db.php';

$name = $_POST['name'];
$age = $_POST['age'];
$gender = $_POST['gender'];
$contact = $_POST['contact'];
$diagnosis = $_POST['diagnosis'];
$meds = $_POST['meds'];
$status = $_POST['status'];

$sql = "INSERT INTO patients (full_name, age, gender, contact, diagnosis, medication, status) VALUES ('$name','$age','$gender','$contact','$diagnosis','$meds','$status')";
if($conn->query($sql)){
    // also add to sync_log
    $conn->query("INSERT INTO sync_log (user_id, record_type, action, sync_status, timestamp) VALUES (1, 'patient', 'CREATE', 'Synced', NOW())");
    echo json_encode(["success"=>true]);
} else {
    echo json_encode(["success"=>false, "error"=>$conn->error]);
}
?>