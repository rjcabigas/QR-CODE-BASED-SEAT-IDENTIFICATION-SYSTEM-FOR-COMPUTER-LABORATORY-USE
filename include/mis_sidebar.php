<?php 
if(session_status()===PHP_SESSION_NONE){
session_start();
}

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['mis','admin'])){
header("Location: ../auth/login.php");
exit;
}

include "../include/db.php";

$current=basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'];

$targetCheck = ($role === 'mis') ? 'admin' : 'mis';

$hasRequest = false;

$stmt = $conn->prepare("
SELECT id FROM access_requests 
WHERE requester_role=? 
AND target_role=? 
AND status='pending' 
LIMIT 1
");
$stmt->bind_param("ss", $role, $targetCheck);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows > 0){
$hasRequest = true;
}

$hasAccess = false;

$stmt2 = $conn->prepare("
SELECT id FROM access_requests 
WHERE 
(
    requester_role=? AND target_role=? 
    OR 
    requester_role=? AND target_role=?
)
AND status='approved'
LIMIT 1
");

$stmt2->bind_param("ssss", $role, $targetCheck, $targetCheck, $role);
$stmt2->execute();

$result = $stmt2->get_result();

if($result->num_rows > 0){
    $hasAccess = true;
}
?>

<link rel="stylesheet" href="../css/mis_sidebar.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<aside class="mis-sidebar">

<div class="logo-section">
<img src="../img/sidebar_img/bpc_logo.png" class="logo-icon">
<span class="logo-text">BPC_LAB</span>
</div>

<p class="menu-title mis-title toggle-row">
<span>mis</span>
<?php if($role === 'admin' && !$hasAccess): ?>
<label class="switch">
<input type="checkbox" class="toggle-btn" data-target="mis" <?= $hasRequest ? 'checked' : '' ?>>
<span class="slider"></span>
</label>
<?php endif; ?>
</p>

<ul class="mis-menu">
<li class="<?= $current==='dashboard.php'?'active':'' ?>">
<a href="dashboard.php">
<img src="../img/sidebar_img/dashboard.png">
<span>Dashboard</span>
</a>
</li>

<li class="<?= $current==='monitor.php'?'active':'' ?> <?= ($role==='admin' && !$hasAccess) ? 'no-access' : '' ?>">
<a href="<?= ($role==='admin' && !$hasAccess) ? 'javascript:void(0)' : 'monitor.php' ?>" <?= ($role==='admin' && !$hasAccess) ? 'onclick="return false;"' : '' ?>>
<img src="../img/sidebar_img/monitoring.png">
<span>Monitor lab.</span>
</a>
</li>

<li class="<?= $current==='computer_log.php'?'active':'' ?> <?= ($role==='admin' && !$hasAccess) ? 'no-access' : '' ?>">
<a href="<?= ($role==='admin' && !$hasAccess) ? 'javascript:void(0)' : 'computer_log.php' ?>" <?= ($role==='admin' && !$hasAccess) ? 'onclick="return false;"' : '' ?>>
<img src="../img/sidebar_img/incognito.png">
<span>Computer Log</span>
</a>
</li>
</ul>

<hr>

<p class="menu-title admin-title toggle-row">
<span>admin</span>
<?php if($role === 'mis' && !$hasAccess): ?>
<label class="switch">
<input type="checkbox" class="toggle-btn" data-target="admin" <?= $hasRequest ? 'checked' : '' ?>>
<span class="slider"></span>
</label>
<?php endif; ?>
</p>

<ul class="mis-menu">
<li class="<?= $current==='manage_student.php'?'active':'' ?> <?= (!$hasAccess && $role==='mis') ? 'no-access' : '' ?>">
<a href="<?= (!$hasAccess && $role==='mis') ? 'javascript:void(0)' : 'manage_student.php' ?>" <?= (!$hasAccess && $role==='mis') ? 'onclick="return false;"' : '' ?>>
<img src="../img/sidebar_img/manage_student.png">
<span>Manage student.</span>
</a>
</li>

<li class="<?= $current==='pc_status.php'?'active':'' ?> <?= (!$hasAccess && $role==='mis') ? 'no-access' : '' ?>">
<a href="<?= (!$hasAccess && $role==='mis') ? 'javascript:void(0)' : 'pc_status.php' ?>" <?= (!$hasAccess && $role==='mis') ? 'onclick="return false;"' : '' ?>>
<img src="../img/sidebar_img/pc_status.png">
<span>Pc Status</span>
</a>
</li>
</ul>

<div class="user-section mis-logout">
<a href="#" id="logoutBtn">
<img src="../img/sidebar_img/log_out.png" class="exit-icon">
<span>Logout.</span>
</a>
</div>

</aside>

<div class="logout-overlay" id="logoutModal">
<div class="logout-box">
<h3>Logout?</h3>
<p>Are you sure you want to logout?</p>
<hr>
<div class="logout-actions">
<button id="cancelLogout">Cancel</button>
<a href="../auth/logout.php" class="logout-confirm">Yes</a>
</div>
</div>
</div>

<div id="sidebarToast" class="sidebar-toast"></div>

<script>

const logoutBtn = document.getElementById("logoutBtn");
const logoutModal = document.getElementById("logoutModal");
const cancelBtn = document.getElementById("cancelLogout");

logoutBtn.onclick = function(e){
e.preventDefault();
logoutModal.style.display = "flex";
}

cancelBtn.onclick = function(){
logoutModal.style.display = "none";
}

function showToast(message){
const toast=document.getElementById("sidebarToast");
toast.innerText=message;
toast.classList.add("show");
setTimeout(()=>{
toast.classList.remove("show");
},3000);
}

document.querySelectorAll(".toggle-btn").forEach(btn=>{
btn.addEventListener("change",function(){

const target = this.getAttribute("data-target");
const requester = "<?= $role ?>";

fetch("../include/insert_request.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"target="+target+"&requester="+requester
})
.then(res=>res.text())
.then(data=>{
if(data.trim()==="success"){
showToast("Request sent ✔");
}else{
showToast("Error, try again");
}
});

});
});

</script>