<?php include 'connect.php'; ?>
<html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><style>body{font-family:Arial;padding:12px;background:#f0f9ff}.card{background:white;padding:14px;border-radius:10px;margin:10px 0}</style></head><body>
<h2>📊 MediFile Reports - 3 Reports Required</h2>
<div class="card"><h3>Report 1: Patients by Gender</h3><?php $r=$conn->query("SELECT gender, COUNT(*) c FROM patients GROUP BY gender"); while($x=$r->fetch_assoc()) echo $x['gender'].": ".$x['c']."<br>"; ?></div>
<div class="card"><h3>Report 2: Patients Count</h3><?php $r=$conn->query("SELECT COUNT(*) c FROM patients"); echo $r->fetch_assoc()['c']." Total Patients"; ?></div>
<div class="card"><h3>Report 3: Appointments by Status</h3><?php $r=$conn->query("SELECT status, COUNT(*) c FROM appointments GROUP BY status"); while($x=$r->fetch_assoc()) echo $x['status'].": ".$x['c']."<br>"; ?></div>
<button onclick="window.print()">Print Reports</button><br><br><a href="index.php">Back</a>
</body></html>