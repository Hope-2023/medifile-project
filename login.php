<?php
session_start();
require_once "connect.php";
$err="";
if($_SERVER['REQUEST_METHOD']=='POST'){
  $u = trim($_POST['username']);
  $p = trim($_POST['password']);
  $res = $conn->query("SELECT * FROM users WHERE username='$u' LIMIT 1");
  if($row = $res->fetch_assoc()){
    $dbpass = $row['password_hash'];
    // Accept both plain text AND hashed
    if($p === $dbpass || password_verify($p, $dbpass)){
      $_SESSION['username']=$row['username'];
      $_SESSION['role']=$row['role'];
      header("Location: index_mobileApp.php?page=dashboard");
      exit();
    } else { $err="Wrong password"; }
  } else { $err="User not found"; }
}
?>
<!DOCTYPE html>
<html><head><meta name="viewport" content="width=device-width, initial-scale=1">
<style>body{margin:0;font-family:Arial;background:linear-gradient(135deg,#1976d2,#64b5f6);height:100vh;display:flex;align-items:center;justify-content:center} .box{background:#fff;padding:30px;border-radius:15px;width:90%;max-width:350px;box-shadow:0 5px 20px rgba(0,0,0,0.3)} input{width:100%;padding:12px;margin:8px 0;border:1px solid #ccc;border-radius:8px} .btn{background:#1976d2;color:#fff;border:none;padding:12px;width:100%;border-radius:8px;font-size:16px}</style>
</head><body>
<div class="box">
<h2>🔒 MediFile Login</h2>
<?php if($err) echo "<p style='color:red'>$err</p>"; ?>
<form method="POST">
<input name="username" placeholder="Username" required>
<input name="password" type="password" placeholder="Password" required>
<button class="btn">Login</button>
</form>
<p style="font-size:12px;color:gray">Secure login - session protected</p>
</div></body></html>