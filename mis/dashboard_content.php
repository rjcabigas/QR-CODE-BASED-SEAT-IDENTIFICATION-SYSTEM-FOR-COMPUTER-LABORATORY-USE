<?php
include "../include/db.php";

function plural($count, $singular, $plural){
    if($count == 0){
        return "No " . str_replace("Total ", "", $singular);
    }
    return ($count == 1) ? $singular : $plural;
}

$notifCount = 0;

$stmt = $conn->prepare("
SELECT COUNT(*) FROM access_requests 
WHERE target_role=? 
AND requester_role!=? 
AND status='pending'
");
$stmt->bind_param("ss", $role, $role);
$stmt->execute();
$stmt->bind_result($notifCount);
$stmt->fetch();
$stmt->close();

$targetRole = ($role === 'admin') ? 'mis' : 'admin';
$hasPending = $notifCount > 0;

// SAVE DATE FROM AJAX
if(isset($_POST['save_date'])){
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$user_id = $_SESSION['user_id'];
$date = $_POST['save_date'];

$stmt = $conn->prepare("
    INSERT INTO user_filters (user_id, selected_date, feedback_enabled)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE 
        selected_date=VALUES(selected_date),
        feedback_enabled=1
");
$stmt->bind_param("is", $user_id, $date);
$stmt->execute();
$stmt->close();

}

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT selected_date, feedback_enabled FROM user_filters WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

$selectedDate = $data['selected_date'] ?? "";
$feedback_enabled = $data['feedback_enabled'] ?? 0;
?>

<div class="main-content">

<div class="dashboard-header">
<div>
<h1>Dashboard</h1>
<p class="date">Today is <?= $date ?></p>
</div>

<div class="header-right">

<a href="<?= ($role==='admin' && !$hasAccess) ? '#' : 'notification.php' ?>" 
   class="notif-bell <?= ($role==='admin' && !$hasAccess) ? 'no-access' : '' ?>"><img src="../img/sidebar_img/notification.png" class="notif-icon">
<span id="notif-count" class="notif-count"></span>
</a>

<div class="profile">
<img src="../img/sidebar_img/drop_down.png" class="dropdown" onclick="toggleMenu()">

<div class="dropdown-menu" id="profileMenu">
<p class="dropdown-title">Others:</p>

<div class="menu-item" onclick="openPasswordModal()">
<img src="../img/mis_img/update.png">
<span>Update Password</span>
</div>

<div class="menu-item <?= !$hasPending ? 'disabled' : '' ?>" onclick="<?= $hasPending ? 'openAccessModal()' : '' ?>">
<img src="../img/sidebar_img/access.png">
<span>
Approve Access
<?php if($notifCount > 0): ?>
<span class="notif-badge">+<?= $notifCount ?></span>
<?php endif; ?>
</span>
</div>

</div>
</div>
</div>
</div>

<div class="dashboard-cards">

<div class="card">
<img src="../img/sidebar_img/students.png">
<div class="card-content">
<h2><?= $totalStudents ?></h2>
<p><?= plural($totalStudents, "Total Student", "Total Students") ?></p>
</div>
</div>

<div class="card">
<img src="../img/sidebar_img/comlab.png">
<div class="card-content">
<h2><?= $totalComlabs ?></h2>
<p><?= plural($totalComlabs, "Total Com-lab", "Total Com-labs") ?></p>
</div>
</div>

<div class="card">
<img src="../img/sidebar_img/courses.png">
<div class="card-content">
<h2><?= $totalCourses ?></h2>
<p><?= plural($totalCourses, "Total Course", "Total Courses") ?></p>
</div>
</div>

<div class="card">
<img src="../img/sidebar_img/teacher.png">
<div class="card-content">
<h2><?= $totalTeachers ?></h2>
<p><?= plural($totalTeachers, "Total Teacher", "Total Teachers") ?></p>
</div>
</div>

</div>

<div class="dashboard-panels">

<div class="panel-left">
<div class="pc-status-title">Pc Status</div>

<div class="pc-status-content">

<div class="pc-pie">
<canvas id="pcChart"></canvas>
</div>

<div class="pc-legend">

<div class="legend-item working">
<img src="../img/mis_img/working.png">
<span>Working</span>
<span class="legend-number"><?= $working ?></span>
</div>

<div class="legend-item notworking">
<img src="../img/mis_img/not_working.png">
<span>Not Working</span>
<span class="legend-number"><?= $notworking ?></span>
</div>

<div class="legend-item defective">
<img src="../img/mis_img/defective.png">
<span>Defective</span>
<span class="legend-number"><?= $defective ?></span>
</div>

<div class="legend-item others">
<img src="../img/mis_img/others.png">
<span>Not Use</span>
<span class="legend-number"><?= $others ?></span>
</div>

</div>
</div>
</div>

<div class="panel-right">

<div class="feedback-header">
    <div class="feedback-title">
        <span>Teacher’s Feedback</span>
        <label class="switch">
            <input type="checkbox" id="feedbackToggle" <?= $feedback_enabled == 1 ? 'checked' : '' ?>>
            <span class="slider"></span>
        </label>
    </div>
    <a href="teacher_feedback.php">See all</a>
</div>

<!-- DATE MODAL -->
<div id="dateModal" class="date-modal">

    <div class="date-box">

        <h2>Select Date</h2>

        <div class="calendar-header">
            <button id="prevMonth">&#8249;</button>
            <span id="monthYear"></span>
            <button id="nextMonth">&#8250;</button>
        </div>

        <div class="calendar-days">
            <div>SUN</div><div>MON</div><div>TUE</div>
            <div>WED</div><div>THU</div><div>FRI</div><div>SAT</div>
        </div>

        <div id="calendarDates" class="calendar-dates"></div>

        <button id="confirmDate">Confirm</button>

    </div>

</div>

<?php if($feedbackQuery && $feedbackQuery->num_rows > 0){ ?>

<div class="feedback-container">

<?php while($feedback = $feedbackQuery->fetch_assoc()){ ?>

<div class="feedback-box">

<div class="feedback-top">

<div class="feedback-user">
<img src="../img/mis_img/profile.png">
<div>

<p class="feedback-name">
<?php
if($feedback['anonymous'] == 1){
echo "Anonymous";
}else{
echo !empty($feedback['student_name']) ? htmlspecialchars($feedback['student_name']) : "Student";
}
?>
</p>

<p class="feedback-course">
<?= htmlspecialchars($feedback['section']) ?>
</p>

</div>
</div>

<div class="feedback-status">

<p class="feedback-teacher">
<?= htmlspecialchars($feedback['teacher_name']) ?>
</p>

<p class="feedback-date">
<?= date("F d, Y", strtotime($feedback['created_at'])) ?>
</p>

</div>

</div>

<div class="feedback-message">
<?php
$comment = strlen($feedback['comment']) > 80 
? substr($feedback['comment'],0,80) . "..." 
: $feedback['comment'];

echo htmlspecialchars($comment);
?>
</div>

</div>

<?php } ?>

</div>

<?php }else{ ?>

<div class="feedback-empty">
<img src="../img/mis_img/no_comment.png" class="no-comment-img">
<p class="no-comment-text">No comment submitted yet.</p>
</div>

<?php } ?>

</div>

<div class="panel-bottom">

<div class="recent-header">
<span>Recent user</span>
</div>

<div class="recent-box">

<div class="recent-user">

<img src="../img/mis_img/profile.png">

<div class="recent-info">
<p class="recent-name"><?= htmlspecialchars($fullname) ?></p>
<span>Full name:</span>
</div>

<div class="recent-role">
<p><?= strtoupper($role) ?></p>
<span>Role:</span>
</div>

<div class="recent-time">
<p><?= $loginTimeFormatted ?></p>
<span>Time:</span>
</div>

<div class="recent-date">
<p><?= $loginDateFormatted ?></p>
<span>Date:</span>
</div>

</div>

</div>

</div>

</div>

<div class="password-modal" id="passwordModal">
<div class="password-box">

<h3>CHANGE PASSWORD</h3>

<div class="email-field">
<img src="../img/mis_img/update_email.png" id="emailIcon">
<input type="email" id="emailInput" value="<?= htmlspecialchars($email) ?>" disabled>
</div>

<p id="emailStatus"></p>

<div class="otp-container" id="otpBoxes">
<input type="text" maxlength="1" disabled>
<input type="text" maxlength="1" disabled>
<input type="text" maxlength="1" disabled>
<input type="text" maxlength="1" disabled>
</div>

<div class="otp-row">
<p id="otpStatus"></p>
<p class="send-otp" id="resendOTP">Resend OTP</p>
</div>

<div class="password-field">
<input type="password" id="newPass" placeholder="New Password" disabled>
<span class="floating-error" id="newPassError"></span>
</div>

<div class="password-field">
<input type="password" id="confirmPass" placeholder="Confirm Password" disabled>
<span class="floating-error" id="confirmError"></span>
</div>

<div class="show-pass">
<img src="../img/mis_img/box.png" id="passToggleIcon">
<span>Show password</span>
</div>

<button class="update-btn" disabled>Update Password</button>

</div>
</div>

</div>

<div class="access-modal" id="accessModal">

    <div class="access-box">

        <h3>
        <?= $targetRole === 'admin' ? 'Admin Request Access' : 'MIS Request Access' ?>
        </h3>

        <p>
        <?= $role === 'admin' 
        ? 'You want to enable admin features?' 
        : 'You want to enable mis features?' ?>
        </p>

        <hr>

        <div class="access-actions">

            <button onclick="closeAccessModal()">Cancel</button>

            <button onclick="submitAccess()" class="confirm-btn">
                Yes
            </button>

        </div>

    </div>

</div>

<script>
function openAccessModal(){
document.getElementById("accessModal").style.display="flex";
}

function closeAccessModal(){
document.getElementById("accessModal").style.display="none";
}

function submitAccess(){
fetch("../include/approve_request.php",{
method:"POST",
headers:{"Content-Type":"application/x-www-form-urlencoded"},
body:"target=<?= $role ?>"
})
.then(res=>res.text())
.then(data=>{
if(data.trim()==="success"){
location.reload();
}else{
alert("Error");
}
});
}
</script>

<script>
const savedDateFromDB = "<?= $selectedDate ?>";
const feedbackEnabled = <?= $feedback_enabled ?>;
</script>