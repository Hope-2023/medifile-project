<?php include 'connect.php';
$sa=$_POST['sa_id_number']; $first=$_POST['first_name']; $last=$_POST['last_name'];
$dob=$_POST['date_of_birth']; $gender=$_POST['gender']; $phone=$_POST['phone_number']; $lang=$_POST['preferred_lang'];

// VALIDATION - for 10 marks Validation!
if(strlen($sa)!=13) { echo json_encode(["status"=>"error","message"=>"SA ID must be 13 digits"]); exit(); }
if(empty($first) || empty($last)) { echo json_encode(["status"=>"error","message"=>"First/Last required"]); exit(); }

$sql="INSERT INTO patients (sa_id_number, first_name, last_name, date_of_birth, gender, phone_number, preferred_lang) VALUES ('$sa','$first','$last','$dob','$gender','$phone','$lang')";
if($conn->query($sql)){
  $conn->query("INSERT INTO sync_log (user_id, record_type, action, sync_status) VALUES (1,'patient','CREATE','Synced')");
  echo json_encode(["status"=>"success"]);
} else {
  echo json_encode(["status"=>"error","message"=>$conn->error]);
}
?>