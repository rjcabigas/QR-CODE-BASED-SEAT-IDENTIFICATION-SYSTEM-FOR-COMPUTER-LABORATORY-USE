<?php
session_start();
include "../include/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("
    SELECT student_name, course, year, section, profile_pic 
    FROM students 
    WHERE student_id=? 
    LIMIT 1
");

$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$student_name = "Student";
$profile_pic = "profile.png";
$section = "";

if ($student = $result->fetch_assoc()) {

    $fullname = $student['student_name'] ?? "Student";
    $student_name = explode(" ", $fullname)[0];

    $profile_pic = !empty($student['profile_pic']) ? $student['profile_pic'] : "profile.png";

    preg_match('/\d+/', $student['year'], $matches);
    $year_number = $matches[0] ?? '';

    $section = ($student['course'] ?? '') . "-" . $year_number . ($student['section'] ?? '');
}

$unreadCount = 0;

$stmtCount = $conn->prepare("
    SELECT COUNT(*) as total_unread
    FROM notifications
    WHERE section=? AND is_read=0
");

$stmtCount->bind_param("s", $section);
$stmtCount->execute();

$countResult = $stmtCount->get_result()->fetch_assoc();

if ($countResult) {
    $unreadCount = intval($countResult['total_unread']);
}

$last_pc = "";
$last_comlab = "";

$stmt2 = $conn->prepare("
    SELECT pc_no, comlab_no 
    FROM attendance 
    WHERE student_id=? 
    ORDER BY id DESC 
    LIMIT 1
");

$stmt2->bind_param("s", $student_id);
$stmt2->execute();

$r2 = $stmt2->get_result();

if ($row = $r2->fetch_assoc()) {
    $last_pc = $row['pc_no'] ?? "";
    $last_comlab = $row['comlab_no'] ?? "";
}

$present_count = 0;
$late_count = 0;
$absent_count = 0;

$stmt3 = $conn->prepare("
    SELECT 
        SUM(status='present') as present_total,
        SUM(status='late') as late_total,
        SUM(status='absent') as absent_total
    FROM attendance 
    WHERE student_id=?
    LIMIT 1
");

$stmt3->bind_param("s", $student_id);
$stmt3->execute();

$result3 = $stmt3->get_result()->fetch_assoc();

if ($result3) {
    $present_count = intval($result3['present_total'] ?? 0);
    $late_count = intval($result3['late_total'] ?? 0);
    $absent_count = intval($result3['absent_total'] ?? 0);
}

$recentFiles = [];

$stmtFiles = $conn->prepare("
SELECT file_name, file_path, created_at
FROM submission_files
WHERE student_id = ?
AND is_deleted = 0
ORDER BY created_at DESC
LIMIT 5
");

$stmtFiles->bind_param("s", $student_id);
$stmtFiles->execute();

$filesResult = $stmtFiles->get_result();

if ($filesResult && $filesResult->num_rows > 0) {

    while($file = $filesResult->fetch_assoc()){

        $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));

        $icon = "../img/student_img/files.png";

        switch($ext){
            case "pdf": $icon = "../img/student_img/pdf.png"; break;
            case "doc":
            case "docx": $icon = "../img/student_img/word.png"; break;
            case "xls":
            case "xlsx": $icon = "../img/student_img/excel.png"; break;
        }

        $fileName = htmlspecialchars(preg_replace('/^\d+_/', '', $file['file_name']));
        $fileDate = date("F d, Y", strtotime($file['created_at']));

        $baseDir = realpath(__DIR__ . "/../");
        $filePath = realpath($baseDir . "/" . $file['file_path']);
        $fileSize = "N/A";

        if($filePath && strpos($filePath, $baseDir) === 0 && file_exists($filePath)){
            $size = filesize($filePath);
            $fileSize = ($size >= 1048576) 
                ? round($size / 1048576,2)." MB" 
                : round($size / 1024,2)." KB";
        }

        $recentFiles[] = [
            'icon' => $icon,
            'name' => $fileName,
            'date' => $fileDate,
            'size' => $fileSize
        ];
    }
}

$stmtFiles->close();

$savedSubjects = [];

$stmtSaved = $conn->prepare("
    SELECT subject FROM student_subjects WHERE student_id = ?
");
$stmtSaved->bind_param("s", $student_id);
$stmtSaved->execute();
$resSaved = $stmtSaved->get_result();

while($row = $resSaved->fetch_assoc()){
    $savedSubjects[] = $row['subject'];
}

$availableSubjects = [];

$stmtSubjects = $conn->prepare("
    SELECT DISTINCT subject 
    FROM teacher_subjects 
    WHERE section = ?
");
$stmtSubjects->bind_param("s", $section);
$stmtSubjects->execute();
$subjectsResult = $stmtSubjects->get_result();

if($subjectsResult && $subjectsResult->num_rows > 0){
    while($sub = $subjectsResult->fetch_assoc()){
        if(!in_array($sub['subject'], $savedSubjects)){
            $availableSubjects[] = $sub['subject'];
        }
    }
}
?>