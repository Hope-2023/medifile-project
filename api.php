<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include 'connect.php';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if($action=='login'){
  $u=$_POST['username']; $p=$_POST['password'];
  $res=$conn->query("SELECT * FROM users WHERE username='$u'");
  if($res && $res->num_rows>0){
    $user=$res->fetch_assoc();
    if($p==$user['password_hash']){ echo json_encode(["status"=>"ok","user"=>$user]); }
    else { echo json_encode(["status"=>"error","message"=>"Wrong password is: ".$user['password_hash']]); }
  } else { echo json_encode(["status"=>"error","message"=>"User not found"]); }
  exit;
}
if($action=='get_patients'){ $r=$conn->query("SELECT * FROM patients ORDER BY patient_id DESC"); $d=[]; while($row=$r->fetch_assoc()) $d[]=$row; echo json_encode($d); exit; }
if($action=='get_appointments'){ $r=$conn->query("SELECT a.*, p.first_name, p.last_name, p.sa_id_number FROM appointments a LEFT JOIN patients p ON a.patient_id=p.patient_id ORDER BY app_date_time DESC"); $d=[]; while($row=$r->fetch_assoc()) $d[]=$row; echo json_encode($d); exit; }
if($action=='get_medical_records'){ $r=$conn->query("SELECT m.*, p.first_name, p.last_name FROM medical_records m LEFT JOIN patients p ON m.patient_id=p.patient_id ORDER BY record_id DESC"); $d=[]; while($row=$r->fetch_assoc()) $d[]=$row; echo json_encode($d); exit; }
if($action=='get_prescriptions'){ $r=$conn->query("SELECT pr.*, p.first_name, p.last_name, m.diagnosis FROM prescriptions pr LEFT JOIN patients p ON pr.patient_id=p.patient_id LEFT JOIN medical_records m ON pr.record_id=m.record_id ORDER BY prescription_id DESC"); $d=[]; while($row=$r->fetch_assoc()) $d[]=$row; echo json_encode($d); exit; }
if($action=='get_users'){ $r=$conn->query("SELECT user_id, username, full_name, role, email FROM users"); $d=[]; while($row=$r->fetch_assoc()) $d[]=$row; echo json_encode($d); exit; }
if($action=='get_sync_log'){ $r=$conn->query("SELECT s.*, u.full_name FROM sync_log s LEFT JOIN users u ON s.user_id=u.user_id ORDER BY log_id DESC LIMIT 20"); $d=[]; while($row=$r->fetch_assoc()) $d[]=$row; echo json_encode($d); exit; }

if($action=='add_patient'){
  $saId=$_POST['saId']; $first=$_POST['firstName']; $last=$_POST['lastName']; $dob=$_POST['dob']; $gender=$_POST['gender']; $phone=$_POST['phone'];
  if(strlen($saId)!=13){ echo json_encode(["status"=>"error","message"=>"SA ID must be 13 digits"]); exit; }
  if(!ctype_digit($saId)){ echo json_encode(["status"=>"error","message"=>"SA ID numbers only"]); exit; }
  if(empty($first)||empty($last)){ echo json_encode(["status"=>"error","message"=>"First/Last required"]); exit; }
  $conn->query("INSERT INTO patients (sa_id_number, first_name, last_name, date_of_birth, gender, phone_number) VALUES ('$saId','$first','$last','$dob','$gender','$phone')");
  $conn->query("INSERT INTO sync_log (user_id, record_type, action, sync_status) VALUES (1,'patient','CREATE','Pending')");
  echo json_encode(["status"=>"ok"]); exit;
}
if($action=='reports'){
  $patients=$conn->query("SELECT COUNT(*) c FROM patients")->fetch_assoc()['c'];
  $male=$conn->query("SELECT COUNT(*) c FROM patients WHERE gender='Male'")->fetch_assoc()['c'];
  $female=$conn->query("SELECT COUNT(*) c FROM patients WHERE gender='Female'")->fetch_assoc()['c'];
  $apps=$conn->query("SELECT COUNT(*) c FROM appointments")->fetch_assoc()['c'];
  $pending=$conn->query("SELECT COUNT(*) c FROM sync_log WHERE sync_status='Pending'")->fetch_assoc()['c'];
  $records=$conn->query("SELECT COUNT(*) c FROM medical_records")->fetch_assoc()['c'];
  $presc=$conn->query("SELECT COUNT(*) c FROM prescriptions")->fetch_assoc()['c'];
  $users=$conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
  echo json_encode(["patients"=>$patients,"male"=>$male,"female"=>$female,"appointments"=>$apps,"pending"=>$pending,"records"=>$records,"prescriptions"=>$presc,"users"=>$users]); exit;
}
?>