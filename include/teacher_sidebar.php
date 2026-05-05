<?php
if(session_status()===PHP_SESSION_NONE){
session_start();
}

if(!isset($_SESSION['user_id']) || $_SESSION['role']!=='teacher'){
header("Location: ../auth/login.php");
exit;
}

$current=basename($_SERVER['PHP_SELF']);
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<aside class="sidebar">

<div class="logo-section">
<img src="../img/sidebar_img/bpc_logo.png" class="logo-icon">
<span class="logo-text">BPC COM-LAB.</span>
</div>

<p class="menu-title">MENU</p>

<ul class="menu-list">

<li class="<?= $current==='dashboard.php'?'active':'' ?>">
<a href="dashboard.php">
<img src="../img/sidebar_img/dashboard.png">
<span>Dashboard</span>
</a>
</li>

<li class="<?= $current==='attendance.php'?'active':'' ?>">
<a href="attendance.php">
<img src="../img/sidebar_img/attendances.png">
<span>Attendance</span>
</a>
</li>

<li class="<?= $current==='submission.php'?'active':'' ?>">
<a href="submission.php">
<img src="../img/sidebar_img/submission.png">
<span>Submission</span>
</a>
</li>

</ul>

<div class="user-section sidebar-logout">
<a href="#" id="logoutBtn">
<img src="../img/sidebar_img/out.png" class="exit-icon">
<span>Logout</span>
</a>
</div>

</aside>

<div class="logout-modal" id="logoutModal">
<div class="logout-box">
<h4>Logout?</h4>
<p>Are you sure you want to logout?</p>
<div class="logout-actions">
<button type="button" id="cancelLogout">Cancel</button>
<a href="../auth/logout.php" class="btn-yes">Yes</a>
</div>
</div>
</div>

<script>
const logoutBtn=document.getElementById('logoutBtn');
const logoutModal=document.getElementById('logoutModal');
const cancelLogout=document.getElementById('cancelLogout');

logoutBtn.addEventListener('click',e=>{
e.preventDefault();
logoutBtn.classList.add('active');
logoutModal.style.display='flex';
});

cancelLogout.addEventListener('click',()=>{
logoutBtn.classList.remove('active');
logoutModal.style.display='none';
});

logoutModal.addEventListener('click',e=>{
if(e.target===logoutModal){
logoutBtn.classList.remove('active');
logoutModal.style.display='none';
}
});
</script>