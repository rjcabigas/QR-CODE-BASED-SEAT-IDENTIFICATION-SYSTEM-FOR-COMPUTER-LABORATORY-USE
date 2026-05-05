<?php
if(session_status()===PHP_SESSION_NONE){
session_start();
}

if(!isset($_SESSION['student_id']) || !isset($_SESSION['user_id']) || $_SESSION['role']!=='student'){
header("Location: ../auth/login.php");
exit;
}

$studentName=$_SESSION['student_name'] ?? '';
$studentId=$_SESSION['student_id'] ?? '';

$current=basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="../css/student_sidebar.css">

<aside class="student-sidebar" id="studentSidebar">

<div class="student-logo">

<div class="logo-left">
<img src="../img/sidebar_img/bpc_logo.png">
<span class="logo-text">BPC_LAB</span>
</div>

<button class="minimize-btn" id="sidebarToggle">
<img src="../img/sidebar_img/minimize.png">
</button>

</div>

<div class="student-top">
<ul class="student-menu">

<li class="<?= $current==='dashboard.php'?'active':'' ?>">
<a href="dashboard.php" data-tooltip="Dashboard">
<img src="../img/sidebar_img/home.png">
<span>Dashboard</span>
</a>
</li>

<li class="<?= $current==='upload.php'?'active':'' ?>">
<a href="upload.php" data-tooltip="Upload">
<img src="../img/sidebar_img/upload.png">
<span>Upload</span>
</a>
</li>

<li class="<?= $current==='attendance.php'?'active':'' ?>">
<a href="attendance.php" data-tooltip="Attendance">
<img src="../img/sidebar_img/attendance.png">
<span>Attendance</span>
</a>
</li>

</ul>
</div>

</aside>

<script>
const toggle=document.getElementById("sidebarToggle");
const sidebar=document.getElementById("studentSidebar");

toggle.onclick=()=>{
sidebar.classList.toggle("collapsed");
localStorage.setItem(
"sidebar",
sidebar.classList.contains("collapsed")?"min":"full"
);
};

if(localStorage.getItem("sidebar")==="min"){
sidebar.classList.add("collapsed");
}
</script>