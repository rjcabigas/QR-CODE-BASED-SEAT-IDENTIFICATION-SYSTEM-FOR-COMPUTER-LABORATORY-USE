<?php
session_start();
include "../include/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['teacher_subject'])) {
    $_SESSION['teacher_subject'] = '';
}

if (!isset($_SESSION['teacher_section'])) {
    $_SESSION['teacher_section'] = '';
}

$welcomeName = $_SESSION['fullname'] ?? "TEACHER";

$setupRequired = false;

$checkSub = $conn->prepare("
    SELECT id FROM teacher_subjects WHERE teacher_id=?
");
$checkSub->bind_param("i", $_SESSION['user_id']);
$checkSub->execute();
$checkSub->store_result();
$hasSubject = $checkSub->num_rows > 0;
$checkSub->close();

$checkSec = $conn->prepare("
    SELECT id FROM teacher_sections WHERE teacher_id=?
");
$checkSec->bind_param("i", $_SESSION['user_id']);
$checkSec->execute();
$checkSec->store_result();
$hasSection = $checkSec->num_rows > 0;
$checkSec->close();

if (!$hasSubject && !$hasSection) {
    $setupRequired = true;
}

if (isset($_POST['section'])) {
    $_SESSION['teacher_section'] = $_POST['section'];
}

$sections = [];

$q = $conn->query("
    SELECT DISTINCT course, year, section
    FROM students
    WHERE status='active'
    ORDER BY course, year, section
");

while ($r = $q->fetch_assoc()) {
    $sections[] = $r['course'] . " - " . $r['year'] . " - " . $r['section'];
}

$totalStudents = 0;
$present = 0;
$late = 0;
$absent = 0;

$course = $year = $section = '';

if (
    $_SESSION['teacher_subject'] != '' &&
    $_SESSION['teacher_section'] != ''
) {

    $check = $conn->prepare("
        SELECT id FROM teacher_sections
        WHERE teacher_id=? AND section=?
    ");
    $check->bind_param("is", $_SESSION['user_id'], $_SESSION['teacher_section']);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {

        $full = $_SESSION['teacher_section'];
        $parts = explode("-", $full);

        if (count($parts) === 2) {

            $course = trim($parts[0]);
            $yearSection = trim($parts[1]);

            $yearNumber = preg_replace('/[^0-9]/', '', $yearSection);
            $section = preg_replace('/[^A-Za-z]/', '', $yearSection);

            $yearMap = [
                "1"=>"1ST YEAR",
                "2"=>"2ND YEAR",
                "3"=>"3RD YEAR",
                "4"=>"4TH YEAR"
            ];

            $year = $yearMap[$yearNumber] ?? ($yearNumber . "TH YEAR");

            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM students 
                WHERE course=? AND year=? AND section=? AND status='active'
            ");
            $stmt->bind_param("sss", $course, $year, $section);
            $stmt->execute();
            $stmt->bind_result($totalStudents);
            $stmt->fetch();
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM attendance a
                JOIN teacher_subjects ts ON ts.id = a.teacher_subject_id
                JOIN students s ON s.student_id = a.student_id
                WHERE ts.teacher_id = ?
                AND ts.subject = ?
                AND ts.section = ?
                AND a.date = CURDATE()
                AND a.status = 'present'
            ");
            $stmt->bind_param(
                "iss",
                $_SESSION['user_id'],
                $_SESSION['teacher_subject'],
                $_SESSION['teacher_section']
            );
            $stmt->execute();
            $stmt->bind_result($present);
            $stmt->fetch();
            $stmt->close();

            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM attendance a
                JOIN teacher_subjects ts ON ts.id = a.teacher_subject_id
                JOIN students s ON s.student_id = a.student_id
                WHERE ts.teacher_id = ?
                AND ts.subject = ?
                AND ts.section = ?
                AND a.date = CURDATE()
                AND a.status = 'late'
            ");
            $stmt->bind_param(
                "iss",
                $_SESSION['user_id'],
                $_SESSION['teacher_subject'],
                $_SESSION['teacher_section']
            );
            $stmt->execute();
            $stmt->bind_result($late);
            $stmt->fetch();
            $stmt->close();

            $absent = max(0, $totalStudents - ($present + $late));
        }
    }

    $check->close();
}

$maintenanceData = [];

$q = $conn->query("SELECT * FROM maintenance ORDER BY id DESC");

if ($q && $q->num_rows > 0) {
    while ($row = $q->fetch_assoc()) {
        $maintenanceData[] = $row;
    }
}

$notifications = [];
$idsToUpdate = [];

if (!empty($course) && !empty($year) && !empty($section)) {

    $notif = $conn->prepare("
        SELECT a.id,a.time_in,a.comlab_no,
        s.student_name,s.profile_pic
        FROM attendance a
        JOIN students s ON a.student_id = s.student_id
        WHERE a.date = CURDATE()
        AND a.notif_seen='no'
        AND s.course=?
        AND s.year=?
        AND s.section=?
        ORDER BY a.time_in DESC
        LIMIT 5
    ");

    $notif->bind_param("sss",$course,$year,$section);
    $notif->execute();

    $result = $notif->get_result();

    while ($n = $result->fetch_assoc()) {
        $notifications[] = $n;
        $idsToUpdate[] = (int)$n['id'];
    }

    if ($idsToUpdate) {
        $idList = implode(",", $idsToUpdate);
        $conn->query("UPDATE attendance SET notif_seen='yes' WHERE id IN ($idList)");
    }
}
?>