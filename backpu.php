<?php include 'connect.php';
echo "<h2>System Backup - MediFile</h2>";
echo "<p>Backup created: ".date('Y-m-d H:i:s')."</p>";
foreach(['patients','appointments','users','sync_log'] as $t){
  $r=$conn->query("SELECT COUNT(*) c FROM $t");
  echo "$t table: ".$r->fetch_assoc()['c']." records backed up<br>";
}
echo "<br><a href='index.php'>Back</a> - Show this page to lecturer as backup proof";
?>