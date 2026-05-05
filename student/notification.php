<?php
session_start();
include "../include/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$stmtStudent = $conn->prepare("SELECT course, year, section FROM students WHERE student_id=? LIMIT 1");
$stmtStudent->bind_param("s", $student_id);
$stmtStudent->execute();
$student = $stmtStudent->get_result()->fetch_assoc();

if (!$student) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

$year = $student['year'] ?? '';
preg_match('/\d+/', $year, $matches);
$year_number = $matches[0] ?? '';

$course = $student['course'] ?? '';
$section_db = $student['section'] ?? '';

$section = $course . "-" . $year_number . $section_db;

if(isset($_GET['count'])){
    $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE section=? AND is_read=0");
    $stmtCount->bind_param("s", $section);
    $stmtCount->execute();
    $countResult = $stmtCount->get_result()->fetch_assoc();
    echo $countResult['total'] ?? 0;
    exit();
}

if(isset($_GET['fetch'])){

    $stmtUpdate = $conn->prepare("UPDATE notifications SET is_read=1 WHERE section=? AND is_read=0");
    $stmtUpdate->bind_param("s", $section);
    $stmtUpdate->execute();

    $stmtNotif = $conn->prepare("
        SELECT n.folder_id, u.fullname, f.folder_name
        FROM notifications n
        JOIN submission_folders f ON f.id = n.folder_id
        JOIN users u ON u.id = f.teacher_id
        WHERE n.section=?
        ORDER BY n.created_at DESC
    ");
    $stmtNotif->bind_param("s", $section);
    $stmtNotif->execute();
    $notifResult = $stmtNotif->get_result();

    if($notifResult && $notifResult->num_rows > 0){
        while($notif = $notifResult->fetch_assoc()){
            $folder_id = (int)$notif['folder_id'];
            $folder_name = htmlspecialchars($notif['folder_name']);
            $fullname = htmlspecialchars($notif['fullname']);
            ?>
            <div class="notification-card">
                <div class="notif-left">
                    <img src="../img/student_img/folder.png" class="folder-icon">
                    <div class="notif-text">
                        <h4><?php echo $fullname; ?></h4>
                        <p>Created a folder <?php echo $folder_name; ?></p>
                    </div>
                </div>
                <button class="view-btn"
                    onclick="window.location.href='folder_view.php?folder_id=<?php echo $folder_id; ?>&folder_name=<?php echo urlencode($notif['folder_name']); ?>'">
                    View now
                    <img src="../img/student_img/chevron.png">
                </button>
            </div>
            <?php
        }
    } else {
        ?>
        <div class="empty-state">
            <img src="../img/student_img/no_notif.png">
            <p>Don't have notifications yet.</p>
        </div>
        <?php
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notification</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../mobile_css/student_mobile_view_notification.css">
</head>

<body>

<div class="notification-header">
    <img src="../img/student_img/back.png" class="back-btn" id="backNotification">
    <h3>Notification</h3>
    <div class="header-icons">
        <img src="../img/student_img/dot.png">
    </div>
</div>

<div class="notification-container" id="notificationContainer"></div>

<script>

function loadNotifications(){
    fetch("notification.php?fetch=1")
    .then(res => res.text())
    .then(data => {
        document.getElementById("notificationContainer").innerHTML = data;
    });
}

loadNotifications();

document.getElementById("backNotification").addEventListener("click", function(){
    window.location.href = "dashboard.php";
});

</script>

</body>
</html>