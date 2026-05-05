<?php
session_start();
date_default_timezone_set('Asia/Manila');

include("../include/db.php");

if(!isset($_SESSION['student_id'])){
    header("location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['student_id'];

$studentQuery = mysqli_query($conn, "
    SELECT course, year, section FROM students 
    WHERE student_id = '$student_id'
");

$studentData = mysqli_fetch_assoc($studentQuery);

$student_section = '';

if($studentData){
    $year = preg_replace('/[^0-9]/', '', $studentData['year']);
    $course = strtoupper(trim($studentData['course']));
    $section = strtoupper(trim($studentData['section']));
    $student_section = $course . '-' . $year . $section;
}

$selected_subject = '';

if(isset($_GET['subject'])){
    $_SESSION['selected_subject'] = $_GET['subject'];
}

if(isset($_SESSION['selected_subject'])){
    $selected_subject = $_SESSION['selected_subject'];
}

$subjectQuery = mysqli_query($conn, "
    SELECT subject FROM student_subjects 
    WHERE student_id = '$student_id'
");

$valid = false;

$checkSubjects = mysqli_query($conn, "
    SELECT subject FROM student_subjects 
    WHERE student_id = '$student_id'
");

while($row = mysqli_fetch_assoc($checkSubjects)){
    if($row['subject'] == $selected_subject){
        $valid = true;
        break;
    }
}

if(!$valid){
    $selected_subject = '';
    unset($_SESSION['selected_subject']);
}

if($selected_subject != ''){
    $query = "
    SELECT 
        sf.id,
        sf.folder_name,
        sf.instructions,
        sf.has_new_instruction,
        sf.toast_seen,
        fd.start_time,
        fd.end_time,
        fd.duration,
        fd.created_at AS deadline_created_at

    FROM submission_folders sf

    LEFT JOIN folder_deadlines fd
    ON sf.id = fd.folder_id

    WHERE sf.parent_id IS NULL
    AND sf.section = '$student_section'

    AND sf.teacher_id IN (
        SELECT teacher_id
        FROM teacher_subjects
        WHERE TRIM(UPPER(section)) = TRIM(UPPER('$student_section'))
        AND TRIM(UPPER(subject)) = TRIM(UPPER('$selected_subject'))
    )

    AND sf.is_deleted = 0
    ORDER BY sf.created_at DESC
    ";
} else {
    $query = "SELECT id, folder_name FROM submission_folders WHERE 1=0";
}

$result = mysqli_query($conn, $query);

if(!$result){
    die("Database error.");
}

$show_toast = false;
$toast_folder_id = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Submission</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../mobile_css/student_submit_mobile_view.css">

</head>

<body>

<div class="submission-header">

    <img src="../img/student_img/back.png" class="back-btn" id="backSubmission" alt="Back">

    <h3>Submission</h3>

    <img src="../img/student_img/menu.png" class="menu-btn" id="menuBtn" alt="Menu">

</div>

<div class="submission-content">

<?php if($result && mysqli_num_rows($result) > 0): ?>

<?php while($row = mysqli_fetch_assoc($result)): ?>

<?php
$folder_id = intval($row['id']);
$folder_name = htmlspecialchars($row['folder_name']);
$folder_url = urlencode($row['folder_name']);
$instructions = htmlspecialchars($row['instructions']);
$has_new_instruction = intval($row['has_new_instruction']);

$isExpired = false;

/*
CHECK #1 → duration
example:
7 days = 10080 minutes
kapag lumagpas na sa duration → expired
*/
if($row['duration']){
    $created = strtotime($row['deadline_created_at']);
    $durationSeconds = intval($row['duration']) * 60;

    if(time() >= ($created + $durationSeconds)){
        $isExpired = true;
    }
}

/*
CHECK #2 → start_time + end_time
kapag outside allowed time → expired
*/
if($row['start_time'] && $row['end_time']){
    $now = date("H:i:s");
    $start = $row['start_time'];
    $end = $row['end_time'];

if($now > $end){
    $isExpired = true;
}
}

if($row['has_new_instruction'] == 1 && $row['toast_seen'] == 0){
    $show_toast = true;
    $toast_folder_id = $folder_id;
}
?>

<div>

<div class="folder <?php echo $isExpired ? 'disabled-folder' : ''; ?>"

<?php if(!$isExpired): ?>
ondblclick="openFolder(<?php echo $folder_id; ?>, '<?php echo $folder_url; ?>')"
<?php endif; ?>

>

<img src="../img/student_img/folder.png" alt="Folder">

<span><?php echo $folder_name; ?></span>

<?php if($isExpired): ?>
    <span class="deadline-badge">Deadline Reached</span>
<?php endif; ?>

<div class="detail-wrapper">

        <img src="../img/student_img/detail.png"
             class="detail-icon"
             onclick="toggleModal(<?php echo $folder_id; ?>)">

        <?php if($row['has_new_instruction'] == 1): ?>
            <span class="red-dot"></span>
        <?php endif; ?>

    </div>

</div>

<div class="inline-modal" id="modal-<?php echo $folder_id; ?>">
    <div class="inline-header">
        <span>Instructions:</span>
        <span class="close-inline" onclick="toggleModal(<?php echo $folder_id; ?>)">×</span>
    </div>

    <div class="inline-body">
        <?php 
        if(!empty(trim($instructions))){
            echo nl2br($instructions);
        } else {
            echo "<span class='no-instruction'>No instructions yet.</span>";
        }
        ?>
    </div>
</div>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="empty-state">

    <img src="../img/student_img/no_folder.png" class="empty-img" alt="No Folder">

    <h4>No submission folder yet</h4>

    <p>
        The teacher has not created a submission folder.
        Once available, folders will appear here.
    </p>

</div>

<?php endif; ?>

</div>

<div class="side-panel" id="sidePanel">

    <div class="panel-header">
        <img src="../img/student_img/sub_list.png" class="panel-icon">
        <span>Choose Subject:</span>
    </div>

    <div class="subject-list">

    <?php if(mysqli_num_rows($subjectQuery) > 0): ?>

        <?php while($sub = mysqli_fetch_assoc($subjectQuery)): ?>
            <div class="subject <?php echo ($selected_subject == $sub['subject']) ? 'active' : ''; ?>"
                 onclick="selectSubject('<?php echo $sub['subject']; ?>')">
                <?php echo htmlspecialchars($sub['subject']); ?>
            </div>
        <?php endwhile; ?>

    <?php else: ?>

        <div class="no-subject">
            <img src="../img/student_img/subjects.png" alt="No Subject">
            <p>No subject yet</p>
        </div>

    <?php endif; ?>

    </div>

</div>

<div class="overlay" id="overlay"></div>

<div
    id="toast"
    class="toast <?php echo $show_toast ? 'show' : ''; ?>">
    New Instruction
</div>

<script>

const backBtn = document.getElementById("backSubmission");

if(backBtn){
    backBtn.addEventListener("click", function(){
        window.location.href = "dashboard.php";
    });
}

const menuBtn = document.getElementById("menuBtn");
const sidePanel = document.getElementById("sidePanel");
const overlay = document.getElementById("overlay");

menuBtn.addEventListener("click", function(){
    sidePanel.classList.add("active");
    overlay.classList.add("active");
});

overlay.addEventListener("click", function(){
    sidePanel.classList.remove("active");
    overlay.classList.remove("active");
});

function selectSubject(subject){
    window.location.href = "submit_file_mobile.php?subject=" + encodeURIComponent(subject);
}

function openFolder(folderId, folderName){
    window.location.href = "folder_view.php?folder_id=" + folderId + "&folder_name=" + folderName;
}

function toggleModal(id){
    const modal = document.getElementById("modal-" + id);

    if(modal.style.display === "block"){
        modal.style.display = "none";
    } else {
        document.querySelectorAll(".inline-modal").forEach(m => m.style.display = "none");
        modal.style.display = "block";

        fetch("mark_instruction_seen.php?id=" + id)
        .then(() => {
            const redDot = modal.previousElementSibling.querySelector(".red-dot");

            if(redDot){
                redDot.remove();
            }
        });
    }
}

window.addEventListener("load", function () {
    const toast = document.getElementById("toast");
    const folderId = <?php echo intval($toast_folder_id); ?>;

    if (toast && toast.classList.contains("show") && folderId > 0) {

        fetch("mark_toast_seen.php?id=" + folderId);

        setTimeout(() => {
            toast.classList.remove("show");
        }, 5000);
    }
});

</script>

</body>
</html>