<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#0e7490">
<title>MediFile Mobile</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial;background:#f0f9ff;min-height:100vh;padding-bottom:80px}
.header{background:#0e7490;color:white;padding:18px;text-align:center;position:sticky;top:0;z-index:10}
.card{background:white;margin:12px;border-radius:16px;padding:16px;box-shadow:0 2px 12px rgba(0,0,0,0.08)}
input,select,textarea{width:100%;padding:14px;margin:6px 0;border:1.5px solid #e2e8f0;border-radius:10px;font-size:16px}
button{width:100%;padding:14px;border:none;border-radius:10px;background:#0e7490;color:white;font-weight:700;font-size:16px;margin-top:8px}
button.secondary{background:#f1f5f9;color:#0e7490}
.record{border-left:4px solid #0e7490;background:#f8fafc;padding:12px;border-radius:8px;margin:8px 0}
.nav{position:fixed;bottom:0;left:0;right:0;background:white;display:flex;justify-content:space-around;padding:10px 0;border-top:1px solid #e2e8f0}
.nav-btn{flex:1;text-align:center;padding:8px;font-size:12px;color:#64748b;cursor:pointer}
.nav-btn.active{color:#0e7490;font-weight:700}
.badge{display:inline-block;background:#dcfce7;color:#166534;padding:2px 8px;border-radius:20px;font-size:11px;margin-left:6px}
</style>
</head>
<body>

<div style="background:#0e7490;padding:10px;text-align:center">
<a href="login.php" style="color:white;margin:5px">Login</a>
<a href="index.php" style="color:white;margin:5px">Patients</a>
<a href="appointments.php" style="color:white;margin:5px">Appointments</a>
<a href="reports.php" style="color:white;margin:5px">Reports</a>
<a href="backup.php" style="color:white;margin:5px">Backup</a>
</div>
<div class="header">
<h2>🏥 MediFile</h2>
<p style="font-size:12px;opacity:0.9">Offline Medical Records • Works on any phone</p>
</div>

<div id="page-add" class="page">
<div class="card">
<h3 style="color:#0e7490;margin-bottom:8px">+ New Patient Record</h3>
<input id="pname" placeholder="Full Name *">
<input id="page" type="number" placeholder="Age">
<select id="pgender"><option value="">Gender</option><option>Male</option><option>Female</option><option>Other</option></select>
<input id="pcontact" placeholder="Contact Number">
<textarea id="pdiagnosis" placeholder="Diagnosis / Reason"></textarea>
<input id="pmeds" placeholder="Medication Prescribed">
<select id="pstatus"><option value="Scheduled">Appointment: Scheduled</option><option value="Confirmed">Confirmed</option><option value="Completed">Completed</option></select>
<button onclick="saveRecord()">💾 Save Record Offline</button>
<p style="font-size:11px;color:#64748b;text-align:center;margin-top:8px">Saved locally - no internet needed</p>
</div>
</div>

<div id="page-list" class="page" style="display:none">
<div class="card">
<h3 style="color:#0e7490">📋 All Records <span id="count" class="badge">0</span></h3>
<div style="margin-top:12px"><input id="search" placeholder="🔍 Search by name..." oninput="render()"></div>
<div id="records" style="margin-top:12px">No records yet - add one!</div>
<button class="secondary" onclick="exportData()">📤 Export for Demo</button>
<button class="secondary" onclick="if(confirm('Clear all?')){localStorage.clear();records=[];render()}" style="color:#ef4444">🗑️ Clear All</button>
</div>
</div>

<div id="page-about" class="page" style="display:none">
<div class="card">
<h3 style="color:#0e7490">About This App</h3>
<p style="margin-top:10px;font-size:14px;line-height:1.5">MediFile is an <b>offline-first</b> mobile medical file system.</p>
<ul style="margin:12px 0 12px 20px;font-size:14px;line-height:1.8">
<li>Works with ZERO internet</li>
<li>Data saved on phone</li>
<li>Syncs when internet available (sync_log table)</li>
<li>Installable on any Android/iPhone</li>
</ul>
<div style="background:#f0f9ff;padding:12px;border-radius:10px;margin-top:12px">
<b>How to Install on ANY phone for demo:</b><br>
<span style="font-size:13px">1. Send this file via WhatsApp/Bluetooth<br>2. Open in Chrome<br>3. Menu (⋮) > Add to Home Screen<br>4. Now it works like a real app!</span>
</div>
</div>
</div>

<div class="nav">
<div class="nav-btn active" onclick="showPage('add')" id="nav-add">➕<br>Add</div>
<div class="nav-btn" onclick="showPage('list')" id="nav-list">📋<br>Records</div>
<div class="nav-btn" onclick="showPage('about')" id="nav-about">ℹ️<br>About</div>
</div>

<script>
let records = JSON.parse(localStorage.getItem('medifile_mobile_v2')||'[]');
function showPage(p){
document.querySelectorAll('.page').forEach(x=>x.style.display='none');
document.querySelectorAll('.nav-btn').forEach(x=>x.classList.remove('active'));
document.getElementById('page-'+p).style.display='block';
document.getElementById('nav-'+p).classList.add('active');
if(p=='list') render();
}
function saveRecord(){
const r={
id: Date.now(),
name: document.getElementById('pname').value.trim(),
age: document.getElementById('page').value,
gender: document.getElementById('pgender').value,
contact: document.getElementById('pcontact').value,
diagnosis: document.getElementById('pdiagnosis').value,
meds: document.getElementById('pmeds').value,
status: document.getElementById('pstatus').value,
date: new Date().toLocaleString()
};
if(!r.name){alert('Please enter patient name');return;}
records.unshift(r);
localStorage.setItem('medifile_mobile_v2', JSON.stringify(records));
alert('✅ Saved! '+r.name);
document.getElementById('pname').value='';document.getElementById('page').value='';document.getElementById('pdiagnosis').value='';document.getElementById('pmeds').value='';
showPage('list');
}
function render(){
const q=(document.getElementById('search').value||'').toLowerCase();
let filtered = records.filter(x=>x.name.toLowerCase().includes(q) || (x.diagnosis||'').toLowerCase().includes(q));
document.getElementById('count').innerText=records.length;
const div=document.getElementById('records');
if(filtered.length==0){div.innerHTML='<p style="text-align:center;color:#94a3b8;padding:20px">No matching records</p>';return;}
div.innerHTML=filtered.map(x=>`<div class="record"><b>${x.name}</b> <span class="badge">${x.status}</span><br><span style="font-size:13px">${x.age?x.age+'y, ':''}${x.gender||''} ${x.contact?'• '+x.contact:''}</span><br><span style="font-size:13px">🩺 ${x.diagnosis||'No diagnosis'}<br>💊 ${x.meds||'No meds'}</span><br><small style="color:#64748b">${x.date}</small></div>`).join('');
}
function exportData(){
let text='MediFile Records Export\n\n'+records.map(x=>`${x.name}, ${x.age}, ${x.gender}, ${x.diagnosis}, ${x.meds}, ${x.status}, ${x.date}`).join('\n');
let blob=new Blob([text],{type:'text/plain'});
let a=document.createElement('a');a.href=URL.createObjectURL(blob);a.download='medifile_export.txt';a.click();
}
render();
</script>
</body>
</html>