<?php
session_start();
include "../include/db.php";

$conn->query("SET time_zone = '+08:00'"); 

if(!isset($_SESSION['student_id'])){
    header("location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

$today = mysqli_query($conn,"
SELECT * FROM attendance
WHERE student_id='$student_id'
AND DATE(date) = CURDATE()
ORDER BY id DESC
");

$all = mysqli_query($conn,"
SELECT * FROM attendance
WHERE student_id='$student_id'
ORDER BY id DESC
");

$previous = mysqli_query($conn,"
SELECT * FROM attendance
WHERE student_id='$student_id'
AND DATE(date) < CURDATE()
ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Attendance</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../mobile_css/student_attendance_mobile_view.css">

</head>

<body>

<div class="attendance-header">
<img src="../img/student_img/back.png" class="back-btn" id="backAttendance">
<h3>My Attendance</h3>
<img src="../img/student_img/dot.png" class="menu-dot" id="menuDot">
</div>

<div class="container">

<?php
function clean($v){
return strtoupper(str_replace(" ","",$v));
}
?>

<div id="today" class="tab-content">

<?php if(mysqli_num_rows($today)>0){ while($row=mysqli_fetch_assoc($today)){ ?>

<div class="attendance-card">

<div class="subject-bar"><?php echo clean($row['comlab_no']); ?></div>

<div class="attendance-row">
<div class="row-item">
<img src="../img/student_img/pc_take.png">
<p><?php echo clean($row['pc_no']); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/time.png">
<p><?php echo date('h:i A',strtotime($row['time_in'])); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/time.png">
<p><?php echo $row['time_out'] ? date('h:i A',strtotime($row['time_out'])) : ''; ?></p>
</div>
</div>

<div class="attendance-row">
<div class="row-item">
<img src="../img/student_img/pc.png">
<p><?php echo strtoupper($row['pc_status'] ?? ''); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/date.png">
<p><?php echo date('M d, Y',strtotime($row['date'])); ?></p>
</div>
</div>

</div>

<?php } } else { ?>

<div class="empty-text">
<img src="../img/student_img/no_history.png">
<p>No attendance today.</p>
</div>

<?php } ?>

</div>

<div id="all" class="tab-content">

<?php if(mysqli_num_rows($all)>0){ while($row=mysqli_fetch_assoc($all)){ ?>

<div class="attendance-card">

<div class="subject-bar"><?php echo clean($row['comlab_no']); ?></div>

<div class="attendance-row">
<div class="row-item">
<img src="../img/student_img/pc_take.png">
<p><?php echo clean($row['pc_no']); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/time.png">
<p><?php echo date('h:i A',strtotime($row['time_in'])); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/time.png">
<p><?php echo $row['time_out'] ? date('h:i A',strtotime($row['time_out'])) : ''; ?></p>
</div>
</div>

<div class="attendance-row">
<div class="row-item">
<img src="../img/student_img/pc.png">
<p><?php echo strtoupper($row['pc_status'] ?? ''); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/date.png">
<p><?php echo date('M d, Y',strtotime($row['date'])); ?></p>
</div>
</div>

</div>

<?php } } else { ?>

<div class="empty-text">
<img src="../img/student_img/no_history.png">
<p>No attendance history.</p>
</div>

<?php } ?>

</div>

<div id="previous" class="tab-content">

<?php if(mysqli_num_rows($previous)>0){ while($row=mysqli_fetch_assoc($previous)){ ?>

<div class="attendance-card">

<div class="subject-bar"><?php echo clean($row['comlab_no']); ?></div>

<div class="attendance-row">
<div class="row-item">
<img src="../img/student_img/pc_take.png">
<p><?php echo clean($row['pc_no']); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/time.png">
<p><?php echo date('h:i A',strtotime($row['time_in'])); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/time.png">
<p><?php echo $row['time_out'] ? date('h:i A',strtotime($row['time_out'])) : ''; ?></p>
</div>
</div>

<div class="attendance-row">
<div class="row-item">
<img src="../img/student_img/pc.png">
<p><?php echo strtoupper($row['pc_status'] ?? ''); ?></p>
</div>

<div class="row-item">
<img src="../img/student_img/date.png">
<p><?php echo date('M d, Y',strtotime($row['date'])); ?></p>
</div>
</div>

</div>

<?php } } else { ?>

<div class="empty-text">
<img src="../img/student_img/no_history.png">
<p>No previous attendance.</p>
</div>

<?php } ?>

</div>

</div>

<div class="filter-panel" id="filterPanel">

<div class="panel-title">Other:</div>

<div class="filter-item tab active" data-tab="all">
<img src="../img/student_img/all.png">
<span>All</span>
</div>

<div class="filter-item tab" data-tab="today">
<img src="../img/student_img/today.png">
<span>Today</span>
</div>

<div class="filter-item tab" data-tab="previous">
<img src="../img/student_img/previous.png">
<span>Previous</span>
</div>

</div>

<div class="overlay" id="overlay"></div>

<script>

document.getElementById("backAttendance").addEventListener("click", function(){
window.location.href = "dashboard.php";
});

const tabs=document.querySelectorAll(".tab");
const contents=document.querySelectorAll(".tab-content");

tabs.forEach(tab=>{
tab.addEventListener("click",function(){
tabs.forEach(t=>t.classList.remove("active"));
this.classList.add("active");
contents.forEach(c=>c.style.display="none");
document.getElementById(this.dataset.tab).style.display="block";
filterPanel.classList.remove("active");
overlay.classList.remove("active");
});
});

const menuDot = document.getElementById("menuDot");
const filterPanel = document.getElementById("filterPanel");
const overlay = document.getElementById("overlay");

menuDot.addEventListener("click", function(){
filterPanel.classList.toggle("active");
overlay.classList.toggle("active");
});

overlay.addEventListener("click", function(){
filterPanel.classList.remove("active");
overlay.classList.remove("active");
});

</script>

</body>
</html>