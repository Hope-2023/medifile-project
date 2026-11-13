<?php
session_start();
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

if(!isset($_SESSION['username'])) $_SESSION['username']='nurse_Mlambo';

@include 'db_connect.php';

if(!isset($conn) || !$conn || $conn->connect_error){
    $conn = new mysqli("localhost","root","","medifile_db");
    if($conn->connect_error){
        $conn = new mysqli("localhost","root","","medifile");
    }
}

if(isset($_GET['download']) && $_GET['download']=='backup'){
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="medifile_backup_'.date('Y-m-d').'.sql"');

    $tables = ['patients','appointments','visits','staff'];

    foreach($tables as $t){
        $q = $conn->query("SELECT * FROM $t");

        if(!$q) continue;

        echo "-- Table: $t\n";

        while($r=$q->fetch_assoc()){
            $cols = array_keys($r);
            $vals = array_map(function($v) use ($conn){
                return "'".$conn->real_escape_string($v)."'";
            }, array_values($r));

            echo "INSERT INTO $t (".implode(',', $cols).") VALUES (".implode(',', $vals).");\n";
        }

        echo "\n";
    }

    exit;
}

$username=$_SESSION['username'];
$isAdmin=(stripos($username,'admin')!==false);

// Validate a South African ID number (13 digits, valid date and Luhn check digit).
function validSAID($id){

    if(!preg_match('/^[0-9]{13}$/', $id)) return false;

    $year = (int)substr($id, 0, 2);
    $month = (int)substr($id, 2, 2);
    $day = (int)substr($id, 4, 2);

    if(!checkdate($month, $day, 2000 + $year)) return false;

    // The citizenship digit must be 0 or 1.
    if(!in_array(substr($id, 10, 1), ['0', '1'], true)) return false;

    // Luhn checksum.
    $sum = 0;

    for($i = 0; $i < 13; $i++){

        $digit = (int)$id[$i];

        if($i % 2 === 1){
            $digit *= 2;

            if($digit > 9) $digit -= 9;
        }

        $sum += $digit;
    }

    return ($sum % 10) === 0;
}

// Accept South African numbers as 0XXXXXXXXX or +27XXXXXXXXX.
function normaliseSAPhone($phone){

    $phone = trim($phone);
    $phone = preg_replace('/[\s()\-]/', '', $phone);

    if(strpos($phone, '+27') === 0){
        $phone = '0'.substr($phone, 3);
    }
    elseif(strpos($phone, '27') === 0 && strlen($phone) === 11){
        $phone = '0'.substr($phone, 2);
    }

    return $phone;
}

// Database table updates.
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS arrival_time DATETIME DEFAULT CURRENT_TIMESTAMP");
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS file_wait_start DATETIME DEFAULT CURRENT_TIMESTAMP");
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS file_release_time DATETIME NULL");
$conn->query("ALTER TABLE patients ADD COLUMN IF NOT EXISTS seen_time DATETIME NULL");

$conn->query("CREATE TABLE IF NOT EXISTS patient_visits (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    visit_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    diagnosis TEXT,
    notes TEXT,
    doctor_nurse VARCHAR(100)
)");

$conn->query("CREATE TABLE IF NOT EXISTS staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    full_name VARCHAR(100),
    role VARCHAR(20),
    password VARCHAR(255)
)");

$conn->query("CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    app_date_time DATETIME,
    appointment_date DATETIME,
    type VARCHAR(20),
    reason TEXT,
    status VARCHAR(20) DEFAULT 'Scheduled',
    created_by VARCHAR(100)
)");

$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS app_date_time DATETIME NULL");
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS appointment_date DATETIME NULL");
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS type VARCHAR(20) DEFAULT 'Scheduled'");
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS created_by VARCHAR(100)");
$conn->query("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'Scheduled'");

$page=$_GET['page']??'dashboard';
$action=$_GET['action']??'';
$msg=$_GET['msg']??'';

// ADD PATIENT
if(isset($_POST['add_patient'])){

    $raw_id=trim($_POST['sa_id_number'] ?? '');
    $phone=normaliseSAPhone($_POST['phone_number'] ?? '');
    $dob=trim($_POST['date_of_birth'] ?? '');

    // Validate South African ID.
    if(!validSAID($raw_id)){

        header("Location: index_mobileApp.php?page=patients&action=add&msg=".urlencode("Invalid South African ID number. Enter a valid 13-digit ID."));
        exit;
    }

    // Validate South African phone number.
    if(!preg_match('/^0[1-9][0-9]{8}$/', $phone)){

        header("Location: index_mobileApp.php?page=patients&action=add&msg=".urlencode("Invalid phone number. Enter a valid South African number, e.g. 0821234567 or +27821234567."));
        exit;
    }

    // Validate date of birth and compare it with the ID number.
    if(
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) ||
        !checkdate((int)substr($dob,5,2), (int)substr($dob,8,2), (int)substr($dob,0,4)) ||
        substr($raw_id,0,6) !== substr($dob,2,2).substr($dob,5,2).substr($dob,8,2)
    ){

        header("Location: index_mobileApp.php?page=patients&action=add&msg=".urlencode("Date of birth does not match the date encoded in the SA ID number."));
        exit;
    }

    $sa=$conn->real_escape_string($raw_id);
    $fn=$conn->real_escape_string($_POST['first_name']);
    $ln=$conn->real_escape_string($_POST['last_name']);
    $dob=$conn->real_escape_string($dob);
    $gen=$conn->real_escape_string($_POST['gender']);
    $phone=$conn->real_escape_string($phone);
    $lang=$conn->real_escape_string($_POST['preferred_lang']);
    $diag=$conn->real_escape_string($_POST['diagnosis']);
    $med=$conn->real_escape_string($_POST['medication_prescribed']);

    // Check whether the SA ID already exists.
    $check=$conn->prepare("SELECT patient_id FROM patients WHERE sa_id_number=? LIMIT 1");
    $check->bind_param("s", $raw_id);
    $check->execute();

    $existing=$check->get_result();

    if($existing && $existing->num_rows>0){

        header("Location: index_mobileApp.php?page=patients&action=add&msg=".urlencode("A patient with this SA ID number already exists."));
        exit;
    }

    // Patient arrival and file times.
    $arrival=date('Y-m-d H:i:s', strtotime('-'.rand(30,180).' minutes'));

    $file_release=date('Y-m-d H:i:s', strtotime($arrival.' + '.rand(8,34).' minutes'));

    $seen=rand(0,1)
        ? date('Y-m-d H:i:s', strtotime($arrival.' + '.rand(40,115).' minutes'))
        : NULL;

    // Insert patient only after validation succeeds.
    $conn->query("
        INSERT INTO patients
        (
            sa_id_number,
            first_name,
            last_name,
            date_of_birth,
            gender,
            phone_number,
            preferred_lang,
            diagnosis,
            medication_prescribed,
            arrival_time,
            file_wait_start,
            file_release_time,
            seen_time
        )
        VALUES
        (
            '$sa',
            '$fn',
            '$ln',
            '$dob',
            '$gen',
            '$phone',
            '$lang',
            '$diag',
            '$med',
            '$arrival',
            '$arrival',
            '$file_release',
            '$seen'
        )
    ");

    $pid=$conn->insert_id;

    if($pid){

        // Save current appointment.
        $conn->query("
            INSERT INTO appointments
            (
                patient_id,
                app_date_time,
                appointment_date,
                type,
                reason,
                status,
                created_by
            )
            VALUES
            (
                $pid,
                '$arrival',
                '$arrival',
                'Current',
                '$diag - Current Visit',
                'Completed',
                '$username'
            )
        ");

        // Save upcoming appointment if provided.
        if(isset($_POST['next_appointment']) && $_POST['next_appointment']!=''){

            $next=$conn->real_escape_string($_POST['next_appointment']);
            $next_reason=$conn->real_escape_string($_POST['next_reason']);

            $conn->query("
                INSERT INTO appointments
                (
                    patient_id,
                    app_date_time,
                    appointment_date,
                    type,
                    reason,
                    status,
                    created_by
                )
                VALUES
                (
                    $pid,
                    '$next',
                    '$next',
                    'Upcoming',
                    '$next_reason',
                    'Scheduled',
                    'Patient'
                )
            ");
        }

        header("Location: index_mobileApp.php?page=patients&msg=Patient Added Successfully");
        exit;
    }

    header("Location: index_mobileApp.php?page=patients&action=add&msg=".urlencode("Patient could not be added. Please check the database and try again."));
    exit;
}

// EDIT PATIENT
if(isset($_POST['edit_patient'])){

    $id=intval($_GET['id']);

    $fn=$conn->real_escape_string($_POST['first_name']);
    $ln=$conn->real_escape_string($_POST['last_name']);
    $dob=$conn->real_escape_string($_POST['date_of_birth']);
    $gen=$conn->real_escape_string($_POST['gender']);
    $phone=$conn->real_escape_string($_POST['phone_number']);
    $lang=$conn->real_escape_string($_POST['preferred_lang']);
    $diag=$conn->real_escape_string($_POST['diagnosis']);
    $med=$conn->real_escape_string($_POST['medication_prescribed']);

    $conn->query("
        UPDATE patients
        SET
            first_name='$fn',
            last_name='$ln',
            date_of_birth='$dob',
            gender='$gen',
            phone_number='$phone',
            preferred_lang='$lang',
            diagnosis='$diag',
            medication_prescribed='$med'
        WHERE patient_id=$id
    ");

    header("Location: index_mobileApp.php?page=patients&msg=Updated - ID Locked");
    exit;
}

// ADD VISIT
if(isset($_POST['add_visit'])){

    $pid=intval($_POST['patient_id']);
    $diag=$conn->real_escape_string($_POST['diagnosis']);
    $notes=$conn->real_escape_string($_POST['notes']);

    $conn->query("
        INSERT INTO patient_visits
        (patient_id, diagnosis, notes, doctor_nurse)
        VALUES
        ($pid,'$diag','$notes','$username')
    ");

    $conn->query("UPDATE patients SET seen_time=NOW() WHERE patient_id=$pid");

    $conn->query("
        INSERT INTO appointments
        (patient_id, app_date_time, appointment_date, type, reason, status, created_by)
        VALUES
        ($pid, NOW(), NOW(), 'Previous', '$diag - $notes', 'Completed', '$username')
    ");

    header("Location: index_mobileApp.php?page=patients&action=view&id=$pid&msg=Different Diagnosis Added + Previous saved");
    exit;
}

// ADD APPOINTMENT
if(isset($_POST['add_appointment'])){

    $pid=intval($_POST['patient_id']);
    $adate=$conn->real_escape_string($_POST['appointment_date']);
    $atype=$conn->real_escape_string($_POST['appointment_type'] ?? 'Upcoming');
    $reason=$conn->real_escape_string($_POST['reason']);

    $status=(strtotime($adate) < time()) ? 'Completed' : 'Scheduled';

    $conn->query("
        INSERT INTO appointments
        (patient_id, app_date_time, appointment_date, type, reason, status, created_by)
        VALUES
        ($pid, '$adate', '$adate', '$atype', '$reason', '$status', '$username')
    ");

    header("Location: index_mobileApp.php?page=appointments&msg=Appointment Set - Patient can set upcoming");
    exit;
}

// ADD STAFF
if(isset($_POST['add_staff'])){

    $un=$conn->real_escape_string($_POST['username']);
    $fname=$conn->real_escape_string($_POST['full_name']);
    $r=$conn->real_escape_string($_POST['role']);
    $pwd=password_hash($_POST['password'], PASSWORD_DEFAULT);

    $conn->query("
        INSERT INTO staff
        (username,full_name,role,password)
        VALUES
        ('$un','$fname','$r','$pwd')
    ");

    header("Location: index_mobileApp.php?page=dashboard&msg=Staff Added by Admin on Dashboard");
    exit;
}

// DELETE PATIENT - ADMIN ONLY
if($action=='delete'){

    $id=intval($_GET['id']);

    if($isAdmin && $id>0){

        // Remove linked records first so patient deletion also works when foreign keys are enabled.
        $conn->query("DELETE FROM appointments WHERE patient_id=$id");
        $conn->query("DELETE FROM patient_visits WHERE patient_id=$id");

        $stmt=$conn->prepare("DELETE FROM patients WHERE patient_id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if($stmt->affected_rows>0){

            header("Location: index_mobileApp.php?page=patients&msg=".urlencode("Patient and linked records deleted successfully."));
        }
        else{

            header("Location: index_mobileApp.php?page=patients&msg=".urlencode("Patient was not found or could not be deleted."));
        }

        exit;
    }
    else{

        header("Location: index_mobileApp.php?page=patients&msg=".urlencode("Only Admin can delete patients."));
        exit;
    }
}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Medifile Mobile App</title>

<style>

*{
    box-sizing:border-box
}

body{
    margin:0;
    font-family:Arial;
    background:#f5f5f5
}

.top{
    background:#1976d2;
    color:#fff;
    padding:10px;
    text-align:center
}

.nav{
    display:flex;
    background:#fff;
    border-bottom:1px solid #ddd;
    overflow-x:auto
}

.nav a{
    flex:1;
    padding:12px;
    text-align:center;
    text-decoration:none;
    color:#333;
    font-weight:bold;
    font-size:13px;
    white-space:nowrap
}

.nav a.active{
    background:#1976d2;
    color:#fff
}

.container{
    max-width:1100px;
    margin:auto;
    padding:12px
}

.card{
    background:#fff;
    padding:15px;
    border-radius:8px;
    box-shadow:0 1px 4px rgba(0,0,0,0.1);
    margin-bottom:12px
}

.badge{
    padding:4px 10px;
    border-radius:12px;
    font-size:11px;
    color:#fff;
    font-weight:bold;
    display:inline-block
}

.btn{
    display:inline-block;
    color:#fff;
    padding:6px 12px;
    border-radius:6px;
    text-decoration:none;
    font-size:12px;
    margin:2px;
    border:none
}

.alert{
    padding:10px;
    background:#d4edda;
    border-radius:6px;
    margin-bottom:10px
}

input,select,textarea{
    width:100%;
    padding:9px;
    margin:5px 0;
    border:1px solid #ccc;
    border-radius:6px
}

.btn-mobile{
    display:block;
    width:100%;
    padding:12px;
    color:#fff;
    border:none;
    border-radius:8px;
    margin:8px 0;
    text-align:center;
    text-decoration:none
}

.table-wrap{
    overflow-x:auto;
    margin-top:10px
}

table{
    width:100%;
    border-collapse:collapse;
    font-size:13px
}

th,td{
    padding:10px;
    text-align:left;
    border:1px solid #ddd;
    vertical-align:middle;
    white-space:nowrap
}

th{
    background:#f5f5f5
}

td.wrap{
    white-space:normal
}

</style>

</head>

<body>

<div class="top">

<b>MediFile - Offline Records</b>

<br>

<small>
Welcome <?php echo $username; ?>

<?php echo $isAdmin?'- ADMIN DASHBOARD has Add User + Backup':''; ?>

|

<a href="logout.php" style="color:#fff">Logout</a>

</small>

</div>

<div class="nav">

<a href="?page=dashboard" class="<?php echo $page=='dashboard'?'active':''; ?>">
Dashboard
</a>

<a href="?page=patients" class="<?php echo $page=='patients'?'active':''; ?>">
Patients
</a>

<a href="?page=appointments" class="<?php echo $page=='appointments'?'active':''; ?>">
Appointments
</a>

<?php if ($isAdmin): ?>
<a href="?page=reports">Reports</a>
<?php endif; ?>

<?php if($isAdmin): ?>
<a href="?page=backup" class="<?php echo $page=='backup'?'active':''; ?>">
Backup
</a>
<?php endif; ?>

</div>

<div class="container">

<?php if($msg): ?>
<div class="alert">
<?php echo $msg; ?>
</div>
<?php endif; ?>

<?php
if($page=='dashboard' && $action==''):

$tot=$conn->query("SELECT COUNT(*) c FROM patients");
$total=$tot?$tot->fetch_assoc()['c']:0;

$today=$conn->query("SELECT COUNT(*) c FROM patients WHERE DATE(arrival_time)=CURDATE()");
$todayC=$today?$today->fetch_assoc()['c']:0;

?>

<h2 style="color:#0d47a1">
Dashboard - Admin has Add User + Backup with Files + Daily Report
</h2>

<div class="card" style="background:linear-gradient(135deg,#0d47a1,#1976d2);color:#fff">

<h3>
MediFile Live - Today: <?php echo $todayC; ?> patients came - File &lt;35min - Nurse &lt;2h - Times different
</h3>

</div>

<?php if($isAdmin): ?>

<div class="card" style="border-left:4px solid #4caf50">

<h3>Admin - Add New User / Staff - On Dashboard</h3>

<form method="post">

<input name="username" required placeholder="Username:">

<input name="full_name" required placeholder="Full Name">

<input type="text" name="role" placeholder="Type Staff Title - Matron,Clerk,Doctor,Cleaner" required>

<input type="password" name="password" placeholder="password" required>

<button type="submit" name="add_staff" style="background:green;color:white;padding:12px;width:100%;font-size:16px;border:none;border-radius:8px;">
Add User / Staff
</button>

</form>

Current Staff:

<?php
$s=$conn->query("SELECT * FROM staff");

if($s){
    while($st=$s->fetch_assoc()){
        echo $st['username']." (".$st['role'].") - ";
    }
}
?>

</div>

<div class="card" style="border-left:4px solid #f44336">

<h3>Backup - On Admin with Files if System/App Crush</h3>

<p>
Files backed up: index_mobileApp.php, db_connect.php, patients table, appointments, visits, staff
<br>
GitHub + Local SQL backup ready
<br>
Recovery: 1. git clone 2. Import medifile_db.sql 3. Back in 10 min
</p>

<button class="btn" style="background:#333">
Backup Files: medifile_db.sql (<?php echo $total; ?> patients)
</button>

</div>

<?php endif; ?>

<div class="card">

<h3>Daily Report - How Many Patients Came Today: <?php echo $todayC; ?></h3>

<div class="table-wrap">

<table>

<tr>
<th>Patient</th>
<th>Arrival</th>
<th>File Release (different)</th>
<th>Nurse/Doctor (different)</th>
<th>Wait</th>
</tr>

<?php

$resQ=$conn->query("SELECT * FROM patients WHERE DATE(arrival_time)=CURDATE() ORDER BY arrival_time ASC");

if($resQ && $resQ->num_rows==0){
    $resQ=$conn->query("SELECT * FROM patients ORDER BY arrival_time DESC LIMIT 5");
}

if($resQ){

    while($row=$resQ->fetch_assoc()){

        $arr=strtotime($row['arrival_time']);

        $file=strtotime($row['file_release_time']?$row['file_release_time']:$row['arrival_time']);

        $seen=$row['seen_time']?strtotime($row['seen_time']):time();

        $fMin=round(($file-$arr)/60);
        $nMin=round(($seen-$arr)/60);

        echo "<tr>
        <td>".$row['first_name']." ".$row['last_name']."</td>
        <td>".date('H:i:s d/m',$arr)."</td>
        <td>".date('H:i:s',$file)." (".$fMin."m)</td>
        <td>".($row['seen_time']?date('H:i:s',$seen)." (".$nMin."m)":$nMin."m waiting")."</td>
        <td style='color:".($fMin>35||$nMin>120?'red':'green')."'>".($fMin>35?'OVER 35m! ':($nMin>120?'OVER 2h! ':'OK'))."</td>
        </tr>";
    }
}

?>

</table>

</div>

</div>

<?php elseif($action=='add'): ?>

<div class="card">

<h3>Add Patient - Disease + Set Upcoming Appointment</h3>

<form method="post">

SA ID:

<input
    type="text"
    name="sa_id_number"
    required
    maxlength="13"
    minlength="13"
    pattern="[0-9]{13}"
    inputmode="numeric"
    title="Enter a valid 13-digit South African ID number"
>

First Name:
<input name="first_name" required>

Last Name:
<input name="last_name" required>

DOB:
<input type="date" name="date_of_birth" required>

Gender:

<select name="gender" required>
<option>Male</option>
<option>Female</option>
</select>

Phone:

<input
    type="tel"
    name="phone_number"
    required
    pattern="(0[1-9][0-9]{8}|\+27[1-9][0-9]{8})"
    title="Enter a valid South African phone number, e.g. 0821234567 or +27821234567"
>

Language:
<input name="preferred_lang" required>

Diagnosis:
<input type="text" name="diagnosis" required placeholder="Type illness - NOT combo">

Medication:
<textarea name="medication_prescribed" required></textarea>

<h4>Patient Upcoming Appointment</h4>

Next Appointment Date:
<input type="datetime-local" name="next_appointment">

Reason for Next Visit:
<input type="text" name="next_reason" placeholder="e.g. Collection, Follow-up, Results">

<button type="submit" name="add_patient" class="btn-mobile" style="background:#4caf50">
ADD - Times will be different
</button>

</form>

</div>

<?php elseif($action=='edit'):

$id=intval($_GET['id']);

$r=$conn->query("SELECT * FROM patients WHERE patient_id=$id")->fetch_assoc();

?>

<div class="card">

<h3>UPDATE - SA ID Locked</h3>

<form method="post">

SA ID Locked:

<input type="text" value="<?php echo $r['sa_id_number']; ?>" readonly style="background:#e0e0e0">

First Name:
<input name="first_name" value="<?php echo $r['first_name']; ?>">

Last Name:
<input name="last_name" value="<?php echo $r['last_name']; ?>">

DOB:
<input type="date" name="date_of_birth" value="<?php echo $r['date_of_birth']; ?>">

Gender:
<input name="gender" value="<?php echo $r['gender']; ?>">

Phone:
<input name="phone_number" value="<?php echo $r['phone_number']; ?>">

Diagnosis TEXT:
<input type="text" name="diagnosis" value="<?php echo $r['diagnosis']; ?>">

Medication:
<textarea name="medication_prescribed"><?php echo $r['medication_prescribed']; ?></textarea>

<button type="submit" name="edit_patient" class="btn-mobile" style="background:#ff9800">
UPDATE - ID stays same
</button>

</form>

</div>

<?php elseif($action=='view'):

$id=intval($_GET['id']);

$r=$conn->query("SELECT * FROM patients WHERE patient_id=$id")->fetch_assoc();

?>

<div class="card">

<h3>
Patient: <?php echo $r['first_name']." ".$r['last_name']; ?>

- Arrival: <?php echo $r['arrival_time']; ?>

- File: <?php echo $r['file_release_time']; ?>

- Seen: <?php echo $r['seen_time']?$r['seen_time']:'Waiting'; ?>
</h3>

<?php

$arrT=strtotime($r['arrival_time']);

$fileT=!empty($r['file_release_time'])?strtotime($r['file_release_time']):0;

$seenT=!empty($r['seen_time'])?strtotime($r['seen_time']):0;

$fileMin=$fileT?round(($fileT-$arrT)/60):0;

?>

<p>
Times are DIFFERENT:

Arrival <?php echo date('H:i:s',$arrT); ?>

|

File Release <?php echo $fileT?date('H:i:s',$fileT).' ('.$fileMin.'m)':'Not yet'; ?>

|

Nurse <?php echo $seenT?date('H:i:s',$seenT):'Waiting'; ?>
</p>

<h4>Previous + Current + Upcoming - Available when Doctor/Nurse Log In</h4>

<div class="table-wrap">

<table>

<tr>
<th>Type</th>
<th>Date</th>
<th>Reason</th>
<th>Created By</th>
</tr>

<?php

$apps=$conn->query("
    SELECT *
    FROM appointments
    WHERE patient_id=$id
    ORDER BY COALESCE(app_date_time, appointment_date) ASC
");

if($apps){

    while($a=$apps->fetch_assoc()){

        $d=$a['app_date_time']?$a['app_date_time']:$a['appointment_date'];

        echo "<tr>
        <td><span class='badge' style='background:".($a['type']=='Previous'?'#ff9800':($a['type']=='Current'?'#4caf50':'#2196f3'))."'>".$a['type']."</span></td>
        <td>".date('d/m/Y H:i',strtotime($d))."</td>
        <td class='wrap'>".$a['reason']."</td>
        <td>".$a['created_by']."</td>
        </tr>";
    }
}

$vis=$conn->query("
    SELECT *
    FROM patient_visits
    WHERE patient_id=$id
    ORDER BY visit_date DESC
");

if($vis){

    while($v=$vis->fetch_assoc()){

        echo "<tr>
        <td><span class='badge' style='background:#ff9800'>Previous Visit</span></td>
        <td>".date('d/m/Y H:i',strtotime($v['visit_date']))."</td>
        <td class='wrap'>".$v['diagnosis']." - ".$v['notes']."</td>
        <td>".$v['doctor_nurse']."</td>
        </tr>";
    }
}

?>

</table>

</div>

<?php if(!$isAdmin): ?>

<form method="post" style="margin-top:10px">

<h4>Add Different Diagnosis Every Visit</h4>

<input type="hidden" name="patient_id" value="<?php echo $id; ?>">

Diagnosis TEXT:
<input type="text" name="diagnosis" required>

<textarea name="notes" placeholder="Notes"></textarea>

<button type="submit" name="add_visit" class="btn-mobile" style="background:#1976d2">
Add Visit + Save as Previous
</button>

</form>

<form method="post" style="margin-top:10px">

<h4>Patient Set Upcoming Appointment</h4>

<input type="hidden" name="patient_id" value="<?php echo $id; ?>">

<input type="datetime-local" name="appointment_date" required>

<select name="appointment_type">
<option>Upcoming</option>
</select>

<input type="text" name="reason" required placeholder="Reason for upcoming">

<button type="submit" name="add_appointment" class="btn-mobile" style="background:#2e7d32">
Set Upcoming Appointment
</button>

</form>

<?php endif; ?>

<a href="?page=patients" class="btn" style="background:#1976d2">
Back
</a>

</div>

<?php elseif($page=='patients' && $action==''): ?>

<div class="card">

<h3>Patient Records</h3>

<a href="?page=patients&action=add" class="btn" style="background:#4caf50">
+ Add Patient +
</a>

<div class="table-wrap">

<table>

<tr>

<th>ID</th>
<th>Name</th>

<?php if($isAdmin): ?>

<th>Patient ID</th>
<th>Time Came</th>

<?php else: ?>

<th>Diagnosis</th>

<?php endif; ?>

<th>File Release</th>
<th>Nurse / Doctor</th>
<th>Action</th>

<?php if($isAdmin): ?>
<th>Delete</th>
<?php endif; ?>

</tr>

<?php

$res=$conn->query("SELECT * FROM patients ORDER BY arrival_time DESC");

if($res){

    while($row=$res->fetch_assoc()){

        $arr=strtotime($row['arrival_time']);

        $file=!empty($row['file_release_time'])?strtotime($row['file_release_time']):0;

        $seen=!empty($row['seen_time'])?strtotime($row['seen_time']):0;

        $f=$file?floor(($file-$arr)/60):0;

        $n=$seen?floor(($seen-$arr)/60):floor((time()-$arr)/60);

        echo "<tr>
        <td>".$row['patient_id']."</td>
        <td>".$row['first_name']." ".$row['last_name']."</td>";

        if($isAdmin){

            echo "<td>".$row['sa_id_number']."</td>
            <td>".date('H:i:s',$arr)."</td>";
        }
        else{

            echo "<td class='wrap'>".$row['diagnosis']."</td>";
        }

        echo "
        <td>".($file?date('H:i:s',$file)." (".$f."m)":'Waiting')."</td>
        <td>".($seen?date('H:i:s',$seen)." (".$n."m)":'Waiting ('.$n.'m)')."</td>
        <td>
            <a href='?page=patients&action=view&id=".$row['patient_id']."' class='btn' style='background:#2196f3'>View</a>
        </td>";

        if($isAdmin){

            echo "<td>
                <a href='?page=patients&action=delete&id=".$row['patient_id']."' class='btn' style='background:#f44336' onclick=\"return confirm('Delete?')\">Del</a>
            </td>";
        }

        echo "</tr>";
    }
}

?>

</table>

</div>

</div>

<?php elseif($page=='appointments'): ?>

<div class="card">

<h3>
Appointments - Previous, Current, Upcoming - Nurse,Matron and Doctor can set upcoming
</h3>

<div <?php

$r=strtolower($_SESSION['role']);

if($r!='nurse' && $r!='matron' && $r!='doctor'){
    echo 'style="display:none"';
}

?>>

<form method="post" style="background:#f5f5f5;padding:10px;border-radius:8px">

<h4>Set Appointment</h4>

Patient ID:
<input type="number" name="patient_id" required placeholder="Patient ID">

Date:
<input type="datetime-local" name="appointment_date" required>

Type:

<select name="appointment_type">
<option>Upcoming</option>
<option>Previous</option>
<option>Current</option>
</select>

Reason:
<input type="text" name="reason" required placeholder="Reason">

<button type="submit" name="add_appointment" class="btn" style="background:#4caf50">
Set Appointment
</button>

</form>

</div>

<div class="table-wrap">

<table>

<tr>
<th>Patient</th>
<th>Type</th>
<th>Appointment Date</th>
<th>Reason</th>
<th>Status</th>
</tr>

<?php

$res=$conn->query("
    SELECT a.*, p.first_name, p.last_name
    FROM appointments a
    JOIN patients p ON a.patient_id=p.patient_id
    ORDER BY COALESCE(a.app_date_time, a.appointment_date) DESC
");

if($res){

    while($row=$res->fetch_assoc()){

        $d=$row['app_date_time']?$row['app_date_time']:$row['appointment_date'];

        echo "<tr>
        <td>".$row['first_name']." ".$row['last_name']." (ID:".$row['patient_id'].")</td>
        <td><span class='badge' style='background:".($row['type']=='Previous'?'#ff9800':($row['type']=='Current'?'#4caf50':'#2196f3'))."'>".$row['type']."</span></td>
        <td>".date('d/m/Y H:i:s',strtotime($d))."</td>
        <td class='wrap'>".$row['reason']."</td>
        <td>".$row['status']."</td>
        </tr>";
    }
}

?>

</table>

</div>

</div>

<?php elseif ($page=='reports'): ?>

<?php if(!$isAdmin): ?>

<div class="card">

<h3>Access Denied - Admin Only</h3>

<p>
Reports and PDF downloads are for Admin only. Please contact Admin.
</p>

</div>

<?php else: ?>

<div class="card">

<h3>Reports - Auto Generated - Download PDF</h3>

<p>
Today: <?php echo date('d M Y'); ?>

|

These tables update automatically every time you add a patient / appointment.
</p>

<!-- REPORT 1 : PREVIOUS ILLNESS % -->

<div id="report1" style="background:#fff;padding:15px;border-radius:8px;margin:10px 0;border:1px solid #ddd">

<h4>1. Previous Illness Appointment - Percentage Per Illness</h4>

<table border="1" cellpadding="8" cellspacing="0" width="100%">

<tr>
<th>Illness / Reason</th>
<th>Count</th>
<th>Percentage</th>
</tr>

<?php

$tot = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE type='Previous'");

$totalPrev = $tot ? $tot->fetch_assoc()['c'] : 0;

if($totalPrev==0) $totalPrev=1;

$q1 = $conn->query("
    SELECT reason, COUNT(*) as cnt
    FROM appointments
    WHERE type='Previous'
    GROUP BY reason
    ORDER BY cnt DESC
");

if($q1 && $q1->num_rows>0){

    while($r=$q1->fetch_assoc()){

        $pct = round(($r['cnt']/$totalPrev)*100,1);

        echo '<tr>
        <td>'.$r['reason'].'</td>
        <td>'.$r['cnt'].'</td>
        <td>'.$pct.'%</td>
        </tr>';
    }
}
else{

    echo '<tr><td colspan="3">No Previous appointments yet</td></tr>';
}

?>

</table>

</div>

<button onclick="printDiv('report1')" style="background:#1a3a6b;color:#fff;padding:10px;border:none;border-radius:5px">
Download PDF - Previous Illness %
</button>

<!-- REPORT 2 : UPCOMING -->

<div id="report2" style="background:#fff;padding:15px;border-radius:8px;margin:15px 0;border:1px solid #ddd">

<h4>2. Upcoming Appointment Visits</h4>

<table border="1" cellpadding="8" cellspacing="0" width="100%">

<tr>
<th>Patient ID</th>
<th>Date</th>
<th>Reason</th>
</tr>

<?php

$q2 = $conn->query("
    SELECT *
    FROM appointments
    WHERE app_date_time>NOW()
    ORDER BY app_date_time ASC
");

if($q2 && $q2->num_rows>0){

    while($r=$q2->fetch_assoc()){

        echo '<tr>
        <td>'.$r['patient_id'].'</td>
        <td>'.$r['app_date_time'].'</td>
        <td>'.$r['reason'].'</td>
        </tr>';
    }
}
else{

    echo '<tr><td colspan="3">No Upcoming appointments</td></tr>';
}

?>

</table>

</div>

<button onclick="printDiv('report2')" style="background:green;color:#fff;padding:10px;border:none;border-radius:5px">
Download PDF - Upcoming
</button>

<!-- REPORT 3 : CURRENT -->

<div id="report3" style="background:#fff;padding:15px;border-radius:8px;margin:15px 0;border:1px solid #ddd">

<h4>3. Current Appointment Visits</h4>

<table border="1" cellpadding="8" cellspacing="0" width="100%">

<tr>
<th>Patient ID</th>
<th>Date</th>
<th>Reason</th>
</tr>

<?php

$q3 = $conn->query("
    SELECT *
    FROM appointments
    WHERE DATE(app_date_time)=CURDATE()
    OR appointment_type='Current'
    ORDER BY app_date_time ASC
");

if($q3 && $q3->num_rows>0){

    while($r=$q3->fetch_assoc()){

        echo '<tr>
        <td>'.$r['patient_id'].'</td>
        <td>'.$r['app_date_time'].'</td>
        <td>'.$r['reason'].'</td>
        </tr>';
    }
}
else{

    echo '<tr><td colspan="3">No Current appointments</td></tr>';
}

?>

</table>

</div>

<button onclick="printDiv('report3')" style="background:orange;color:#fff;padding:10px;border:none;border-radius:5px">
Download PDF - Current
</button>

<!-- DAILY VISITS AUTO -->

<div id="reportDaily" style="background:#fff;padding:15px;border-radius:8px;margin:15px 0;border:1px solid #ddd">

<h4>Daily Visits - How Many Patients Came (Auto)</h4>

<table border="1" cellpadding="8" cellspacing="0" width="100%">

<tr>
<th>Date</th>
<th>Patients Came</th>
</tr>

<?php

$q4 = $conn->query("
    SELECT date, COUNT(*) as cnt
    FROM appointments
    GROUP BY date
    ORDER BY date DESC
");

if($q4 && $q4->num_rows>0){

    while($r=$q4->fetch_assoc()){

        echo '<tr>
        <td>'.$r['date'].'</td>
        <td>'.$r['cnt'].' patients</td>
        </tr>';
    }
}
else{

    echo '<tr><td colspan="2">No visits yet</td></tr>';
}

?>

</table>

</div>

<button onclick="printDiv('reportDaily')" style="background:#333;color:#fff;padding:10px;border:none;border-radius:5px">
Download PDF - Daily Visits
</button>

</div>

<script>

function printDiv(id){

    var content = document.getElementById(id).innerHTML;

    var w = window.open('', '', 'width=800,height=600');

    w.document.write(
        '<html><head><title>Medifile Report</title></head><body><h2>MEDIFILE - Hope Clinic</h2><p>Date: '+
        new Date().toLocaleString()+
        '</p>'+
        content+
        '</body></html>'
    );

    w.document.close();
    w.focus();
    w.print();
}

</script>

<?php endif; ?>

<?php elseif($page=='backup'): ?>

<?php if(!$isAdmin): ?>

<div class="card">
Backup - Only Admin when logged in
</div>

<?php else: ?>

<div class="card">

<h3>Backup - Admin with Files if System/App Crush</h3>

<p>
Backup files ready: index_mobileApp.php, db_connect.php, medifile_db.sql with

<?php

$c=$conn->query("SELECT COUNT(*) c FROM patients");

echo $c?$c->fetch_assoc()['c']:0;

?>

patients, appointments, visits, staff
</p>

<p>
GitHub Repo: medifile-mobile-web-app

<br>

Local Backup: C:/xampp/htdocs/medifile/backup/<?php echo date('Y-m-d'); ?>_medifile_db.sql
</p>

<a href="index_mobileApp.php?download=backup">

<button style="background:green;color:#fff;padding:10px;border:none;border-radius:5px;cursor:pointer">
Download SQL Backup - If system crush, import this file
</button>

</a>

<br><br>

Current Files:

<?php echo implode(", ", array_map('basename', glob(__DIR__.'/*.php'))); ?>

</div>

<?php endif; ?>

<?php endif; ?>

</div>

</body>
</html>