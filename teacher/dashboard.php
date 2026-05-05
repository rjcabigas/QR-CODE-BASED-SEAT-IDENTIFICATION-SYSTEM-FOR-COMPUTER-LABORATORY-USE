<?php include "dashboard_data.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Teacher Dashboard</title>

<link rel="stylesheet" href="../css/teacher/teacher_dashboard.css">
<link rel="stylesheet" href="../css/teacher/teacher_modal_dashboard.css">
<link rel="stylesheet" href="../css/teacher_sidebar.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<?php include "../include/teacher_sidebar.php"; ?>

<div class="content <?= $setupRequired ? 'dashboard-locked' : '' ?>">

<div class="top-header">

<h1 class="welcome-title">
WELCOME BACK, <span id="typedName"></span><span class="cursor">|</span>
</h1>

<div class="settings-icon" id="dotBtn">
<img src="../img/teacher_img/dot.png">
</div>

</div>

<div class="stats-row">

<div class="stat-card">
<div class="stat-left">
<p class="stat-label">TOTAL</p>
<p class="stat-value"><?= number_format($totalStudents) ?></p>
</div>
<img src="../img/teacher_img/total.png" class="stat-icon">
</div>

<div class="stat-card">
<div class="stat-left">
<p class="stat-label">PRESENT</p>
<p class="stat-value"><?= number_format($present) ?></p>
</div>
<img src="../img/teacher_img/present.png" class="stat-icon">
</div>

<div class="stat-card">
<div class="stat-left">
<p class="stat-label">LATE</p>
<p class="stat-value"><?= number_format($late) ?></p>
</div>
<img src="../img/teacher_img/late.png" class="stat-icon">
</div>

<div class="stat-card">
<div class="stat-left">
<p class="stat-label">ABSENT</p>
<p class="stat-value"><?= number_format($absent) ?></p>
</div>
<img src="../img/teacher_img/absent.png" class="stat-icon">
</div>

</div>

<div class="dashboard-main">

<div class="remarks-panel">

<div class="remarks-header">
<img src="../img/teacher_img/remarks.png" class="remarks-icon">

<span>
<span class="remarks-title">REMARKS</span> /
<span class="maintenance-title">MAINTENANCE</span>
</span>

</div>

<div class="remarks-table-header">
<span>PC NO:</span>
<span>COMLAB:</span>
<span>TYPE OF ISSUE:</span>
<span>DESCRIPTION OF ISSUE:</span>
<span>Action:</span>
</div>

<?php if (!empty($maintenanceData)): ?>

<?php foreach ($maintenanceData as $row): 

$id = (int)$row['id'];
$pc = htmlspecialchars($row['pc_no']);
$comlab = strtoupper(htmlspecialchars($row['comlab']));
$type = strtoupper(htmlspecialchars($row['issue_type']));
$desc = strtoupper(htmlspecialchars($row['description']));

?>

<div class="remarks-row">

<span><?= $pc ?></span>
<span><?= $comlab ?></span>
<span><?= $type ?></span>
<span><?= $desc ?></span>

<?php if ($row['teacher_action'] === 'pending'): ?>

<form method="post" action="maintenance_action.php">
<input type="hidden" name="id" value="<?= $id ?>">
<button type="submit" class="approve-btn" name="approve">APPROVE</button>
<button type="submit" class="reject-btn" name="reject">REJECT</button>
</form>

<?php else:

$status = "<span class='status-pending'>PENDING MIS...</span>";

if ($row['teacher_action'] === 'rejected') {
$status = "<span class='status-rejected'>REJECTED</span>";
}
elseif ($row['admin_action'] === 'rejected') {
$status = "<span class='status-rejected'>REJECTED BY ADMIN</span>";
}
elseif ($row['resolved'] === 'yes') {
$status = "<span class='status-resolved'>RESOLVED</span>";
}
elseif ($row['admin_action'] === 'approved') {
$status = "<span class='status-progress'>IN PROGRESS</span>";
}

echo $status;

endif; ?>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="remarks-empty">
<img src="../img/teacher_img/maintenance.png">
<p>No maintenance requests yet</p>
<span>Maintenance requests submitted by students will appear here.</span>
</div>

<?php endif; ?>

</div>

<div class="notif-panel">

<div class="notif-header">
<img src="../img/teacher_img/notif.png" class="notif-icon">
<span>NOTIFICATION</span>
</div>

<?php if (!empty($notifications)): ?>

<?php foreach ($notifications as $n):

$name = strtoupper(htmlspecialchars($n['student_name']));
$lab = strtoupper(htmlspecialchars($n['comlab_no']));
$pic = htmlspecialchars($n['profile_pic']);
$time = date("h:i A", strtotime($n['time_in']));

?>

<div class="notif-item auto-remove">

<?php
$pic = !empty($n['profile_pic']) ? htmlspecialchars($n['profile_pic']) : 'profile.png';
?>

<img src="../uploads/<?= $pic ?>" class="notif-avatar">

<div class="notif-body">

<div class="notif-top">
<span class="notif-name"><?= $name ?></span>
<span class="notif-lab"><?= $lab ?></span>
</div>

<div class="notif-bottom">
HAS <span class="scanned">ALREADY SCANNED.</span>
</div>

</div>

<div class="notif-time"><?= $time ?></div>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="notif-empty">
<img src="../img/teacher_img/notification.png">
<p>No notifications yet</p>
<span>New scan notifications will appear here.</span>
</div>

<?php endif; ?>

</div>

</div>

</div>

<div class="dot-panel" id="dotPanel">

<div class="dot-item" id="myClassBtn">
<img src="../img/teacher_img/class.png">
<span>My Class</span>
</div>

</div>

<?php include "dashboard_modal.php"; ?>

<script>
const username = "<?= strtoupper($welcomeName) ?>";
const SESSION_ACTIVE_SECTION = "<?= $_SESSION['teacher_section'] ?? '' ?>";
const SESSION_ACTIVE_SUBJECT = "<?= $_SESSION['teacher_subject'] ?? '' ?>";
const SETUP_REQUIRED = <?= $setupRequired ? 'true' : 'false' ?>;
</script>

<script src="../js/teacher_dashboard.js"></script>

<script>
const timeOverlay = document.getElementById('timeOverlay');
const saveTime = document.getElementById('saveTime');

document.addEventListener('input', e => {

    if (e.target.classList.contains('subjectInput')) {

        let value = e.target.value;

        if (value.length > 0) {
            e.target.value =
                value.charAt(0).toUpperCase() +
                value.slice(1).toLowerCase();
        }

        const wrapper = e.target.closest('.subject-input');
        if (wrapper) {
            const icon = wrapper.querySelector('.time-icon');
            if (icon) {
                icon.style.display = e.target.value.trim() !== '' ? 'block' : 'none';
            }
        }
    }

});

if (saveTime) {

    saveTime.addEventListener('click', ()=>{

        const start = document.getElementById('startTime').value;
        const end = document.getElementById('endTime').value;

        if(!start || !end){
            alert("Select time first");
            return;
        }

        window.activeSubjectInput.dataset.start = start;
        window.activeSubjectInput.dataset.end = end;

        timeOverlay.style.display='none';

    });

}

document.addEventListener("DOMContentLoaded",()=>{

    const panel = document.querySelector(".notif-panel");

    document.querySelectorAll(".auto-remove").forEach((item,index)=>{

        setTimeout(()=>{

            item.style.opacity="0";
            item.style.transition="opacity .5s";

            setTimeout(()=>{

                item.remove();
                checkEmpty();

            },500);

        },6000+(index*500));

    });

    function checkEmpty(){

        const remaining = panel.querySelectorAll(".notif-item");

        if(remaining.length === 0){

            panel.insertAdjacentHTML("beforeend",`

                <div class="notif-empty">
                <img src="../img/teacher_img/notification.png">
                <p>No notifications yet</p>
                <span>New scan notifications will appear here.</span>
                </div>

            `);

        }

    }

});
</script>

</body>
</html>