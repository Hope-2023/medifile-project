<?php
session_start();
$host="localhost"; $user="root"; $pass=""; $db="medifile";
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){ die("DB Error: ".$conn->connect_error); }
$error="";
if(isset($_POST['login'])){
  $u=$conn->real_escape_string($_POST['username']);
  $p=$_POST['password'];
  $r=$conn->query("SELECT * FROM users WHERE username='$u' LIMIT 1")->fetch_assoc();
  if($r && password_verify($p, $r['password'])){
    $_SESSION['username']=$r['username']; $_SESSION['role']=$r['role']; $_SESSION['full_name']=$r['full_name'];
    header("Location: index_mobileApp.php?page=dashboard"); exit;
  }
  if(($u=='admin_hope' && $p=='admin123') || ($u=='nurse_Mlambo' && $p=='nurse123')){
    $_SESSION['username']=$u; $_SESSION['role']=(strpos($u,'admin_')===0?'admin':'nurse');
    header("Location: index_mobileApp.php?page=dashboard"); exit;
  }
  $error="Wrong username or password - Staff not found. Ask Admin to add you again.";
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login - Medifile</title>
<style>body{font-family:Arial;background:#f4f4f4;padding:20px}.card{background:#fff;max-width:400px;margin:40px auto;padding:20px;border-radius:10px}input{width:100%;padding:10px;margin:8px 0;border:1px solid #ccc;border-radius:6px;box-sizing:border-box}button{width:100%;padding:12px;background:#1976d2;color:#fff;border:none;border-radius:6px;font-weight:bold}</style></head>
<body><div class="card"><h2>Medifile - Login</h2>
<?php if($error) echo "<p style='background:#ffebee;padding:10px;border-radius:6px;color:red'>$error</p>"; ?>
<form method="post">Username: <input name="username" required placeholder="e.g. nurse_Mlambo or admin_James">
Password: <input type="password" name="password" required placeholder="Password set by Admin">
<button name="login">Login</button></form>
<p style="font-size:13px">New Staff? Admin must add you on Dashboard > Admin - Add New User / Staff. Then login here with that username + password.</p></div></body></html>