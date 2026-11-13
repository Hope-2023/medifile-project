<?php include 'connect.php'; ?>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{font-family:Arial;background:#f0f9ff;padding:12px}
.card{background:white;padding:14px;border-radius:10px;margin:8px 0;box-shadow:0 2px 4px #ccc}
input,select,button{width:100%;padding:10px;margin:4px 0;box-sizing:border-box}
button{background:#0e7490;color:white;border:none;border-radius:6px;padding:12px;font-weight:bold}
table{width:100%;border-collapse:collapse;font-size:13px}
th,td{border:1px solid #ccc;padding:8px;text-align:left}
th{background:#0e7490;color:white}
</style>
</head>
<body>

<h2>Appointments Module - Module 2</h2>

<div class="card">
<h3>Add Appointment</h3>
<form method="POST" action="appointment.php">
<select name="patient_id" required>
<option value="">Select Patient *</option>
<?php 
$patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY patient_id");
while($p = $patients->fetch_assoc()){
 echo "<option value='{$p['patient_id']}'>{$p['first_name']} {$p['last_name']} (ID {$p['patient_id']})</option>";
}
?>
</select>
<input name="app_date_time" type="datetime-local" required>
<input name="reason" placeholder="Reason e.g. Checkup" required>
<select name="status">
<option value="Scheduled">Scheduled</option>
<option value="Confirmed">Confirmed</option>
<option value="Completed">Completed</option>
<option value="Cancelled">Cancelled</option>
</select>
<button type="submit">Save Appointment</button>
</form>

<?php
if($_SERVER