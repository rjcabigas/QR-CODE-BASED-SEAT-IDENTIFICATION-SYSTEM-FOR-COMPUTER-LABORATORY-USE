<?php
session_start();
include "../include/db.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$dateQuery = mysqli_query($conn, "
    SELECT selected_date 
    FROM user_filters 
    WHERE feedback_enabled = 1 
    LIMIT 1
");

$allowedDate = "";
$canComment = false;

if ($rowDate = mysqli_fetch_assoc($dateQuery)) {
    $allowedDate = $rowDate['selected_date'];

    if (date("Y-m-d") >= $allowedDate) {
        $canComment = true;
    }
}

$studentQuery = mysqli_query($conn, "
    SELECT course, year, section FROM students 
    WHERE student_id = '$student_id'
");

if (!$studentQuery) {
    die("Student query failed: " . mysqli_error($conn));
}

$studentData = mysqli_fetch_assoc($studentQuery);

$student_section = '';

if ($studentData) {
    $year = preg_replace('/[^0-9]/', '', $studentData['year']);
    $course = strtoupper(trim($studentData['course']));
    $sec = strtoupper(trim($studentData['section']));
    $student_section = $course . '-' . $year . $sec;
}

if (isset($_POST['submit_comment'])) {

    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $section = isset($_POST['section']) ? $_POST['section'] : '';
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
    $anonymous = isset($_POST['anonymous']) ? (int)$_POST['anonymous'] : 0;

    if (empty($teacher_id) || empty($comment)) {
        die("Missing required fields.");
    }

    $existing = mysqli_query($conn, "
        SELECT id FROM teacher_feedback
        WHERE teacher_id = '$teacher_id'
        AND student_id = '$student_id'
        LIMIT 1
    ");

    if (mysqli_num_rows($existing) > 0) {
        header("Location: comment.php?success=1");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO teacher_feedback 
    (teacher_id, student_id, section, comment, anonymous)
    VALUES (?,?,?,?,?)");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isssi", $teacher_id, $student_id, $section, $comment, $anonymous);

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    $stmt->close();

    header("Location: comment.php?success=1");
    exit();
}

$query = "
SELECT ts.*, u.fullname
FROM teacher_subjects ts
JOIN users u ON ts.teacher_id = u.id
WHERE TRIM(UPPER(ts.section)) = TRIM(UPPER('$student_section'))
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Main query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Comment</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../mobile_css/student_comment_mobile_view.css">

</head>

<body>

<div class="evaluation-header">
<img src="../img/student_img/back.png" class="back-btn" onclick="window.location.href='dashboard.php'">
<h3 class="page-title">Comment</h3>
</div>

<div class="container">

<?php
if (mysqli_num_rows($result) > 0) {
while ($row = mysqli_fetch_assoc($result)) {

$teacher_id = $row['teacher_id'];

$checkComment = mysqli_query($conn, "
    SELECT id 
    FROM teacher_feedback
    WHERE teacher_id = '$teacher_id'
    AND student_id = '$student_id'
    LIMIT 1
");

$alreadyCommented = mysqli_num_rows($checkComment) > 0;
?>

<div class="teacher-card">

<div class="teacher-left">
<img src="../img/student_img/profile.png" class="profile-icon">

<div class="teacher-info">
<h4><?php echo htmlspecialchars($row['fullname']); ?></h4>
<p><?php echo htmlspecialchars($row['subject']); ?> (<?php echo htmlspecialchars($row['section']); ?>)</p>
</div>
</div>

<button class="comment-btn"
    onclick="<?= ($canComment && !$alreadyCommented) ? 'toggleEval(this)' : '' ?>"
    <?= (!$canComment || $alreadyCommented) ? 'disabled' : '' ?>>

<img src="../img/student_img/comment.png">

<?= $alreadyCommented ? 'Done' : 'Comment' ?>

</button>

<div class="evaluation-panel">

<div class="eval-header">
<h4>Comment</h4>
<div class="toggle-switch" onclick="toggleAnonymous(this)">
<div class="toggle-circle"></div>
</div>
</div>

<p class="anonymous-label">Anonymous</p>

<form method="POST">

<input type="hidden" name="teacher_id" value="<?php echo $row['teacher_id']; ?>">
<input type="hidden" name="section" value="<?php echo htmlspecialchars($row['section']); ?>">

<textarea 
name="comment"
class="auto-expand"
placeholder="Please provide your comments about this teacher."
oninput="handleInput(this)"
maxlength="150"
required
></textarea>

<input type="hidden" name="anonymous" class="anon-value" value="0">

<div class="submit-area">
<button type="submit" name="submit_comment" class="submit-btn">Submit</button>
</div>

</form>

</div>
</div>

<?php
}
} else {
?>

<div class="eval-wrapper">
<img src="../img/student_img/no_evaluation.png" class="empty-img">

<h4>No comment available yet.</h4>

<p>
No comment have been opened yet.<br>
They will appear here once teachers make them available.
</p>
</div>

<?php } ?>

</div>

<div id="toast">Comment submitted successfully</div>
<div id="limitToast">Up to 150 characters only</div>

<script>

function toggleEval(button) {
let card = button.closest('.teacher-card');
let panel = card.querySelector('.evaluation-panel');

if (panel.style.display === "block") {
panel.style.display = "none";
} else {
panel.style.display = "block";
}
}

function toggleAnonymous(toggle) {

let panel = toggle.closest('.evaluation-panel');
let label = panel.querySelector('.anonymous-label');
let hiddenInput = panel.querySelector('.anon-value');

toggle.classList.toggle("active");

if (toggle.classList.contains("active")) {
label.style.display = "block";
hiddenInput.value = 1;
} else {
label.style.display = "none";
hiddenInput.value = 0;
}

}

document.addEventListener("click", function(e) {

let panels = document.querySelectorAll(".evaluation-panel");

panels.forEach(panel => {
if (!panel.contains(e.target) && !panel.previousElementSibling.contains(e.target)) {
panel.style.display = "none";
}
});

});

function handleInput(textarea) {

autoResize(textarea);

if (textarea.value.length >= 150) {
showLimitToast();
}

}

function autoResize(textarea) {

textarea.style.height = "auto";
textarea.style.height = textarea.scrollHeight + "px";

if (textarea.scrollHeight > 150) {
textarea.style.height = "150px";
}

}

function showLimitToast() {
let toast = document.getElementById("limitToast");

toast.classList.add("show");

setTimeout(function() {
toast.classList.remove("show");
}, 2000);
}

<?php if (isset($_GET['success'])): ?>

window.onload = function() {

let toast = document.getElementById("toast");

toast.classList.add("show");

setTimeout(function() {
toast.classList.remove("show");
}, 3000);

if (window.history.replaceState) {
let url = window.location.href.split("?")[0];
window.history.replaceState(null, null, url);
}

}

<?php endif; ?>

</script>

</body>
</html>
