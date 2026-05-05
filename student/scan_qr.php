<?php
session_start();
include "../include/db.php";

$conn->query("SET time_zone = '+08:00'"); 

date_default_timezone_set('Asia/Manila');

$current_time = date("H:i:s");

$conn->query("
UPDATE attendance a
JOIN teacher_subjects ts 
ON a.teacher_subject_id = ts.id
SET a.time_out = CONCAT(a.date,' ',ts.end_time)
WHERE a.time_out IS NULL
AND a.date = CURDATE()
AND '$current_time' >= ts.end_time
");

if(!isset($_SESSION['student_id'])){
    exit("not_logged");
}

$student_id = $_SESSION['student_id'];

$qr = trim($_POST['qr'] ?? '');

if($qr === ''){
    exit("invalid_qr");
}

$parts = explode('-', $qr);

if(count($parts) < 4){
    exit("invalid_qr");
}

$comlab_no = "COMLAB " . intval($parts[1]);

$pc_raw = intval($parts[3]);
$pc_no  = $pc_raw;

$stmt = $conn->prepare("
    SELECT course, year, section
    FROM students
    WHERE student_id=?
    LIMIT 1
");

$stmt->bind_param("s",$student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$student){
    exit("invalid_student");
}

$year = preg_replace('/[^0-9]/','',$student['year']);
$section = $student['course']."-".$year.$student['section'];

$stmt = $conn->prepare("
    SELECT id, start_time, end_time, late_minutes
    FROM teacher_subjects
    WHERE section=?
    AND start_time IS NOT NULL
    AND end_time IS NOT NULL
    AND ? BETWEEN start_time AND end_time
    LIMIT 1
");

$stmt->bind_param("ss",$section,$current_time);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$subject){
    exit("no_active_subject");
}

$teacher_subject_id = intval($subject['id']);

$stmt = $conn->prepare("
    SELECT id, time_out
    FROM attendance
    WHERE student_id=?
    AND teacher_subject_id=?
    AND date=CURDATE()
    LIMIT 1
");

$stmt->bind_param("si",$student_id,$teacher_subject_id);
$stmt->execute();
$attendance = $stmt->get_result();
$stmt->close();

if($attendance->num_rows === 0){

    if($pc_no != 0){

        $stmt = $conn->prepare("
            SELECT id
            FROM attendance
            WHERE pc_no=?
            AND teacher_subject_id=?
            AND date=CURDATE()
            AND time_out IS NULL
            LIMIT 1
        ");

        $stmt->bind_param("ii",$pc_no,$teacher_subject_id);
        $stmt->execute();

        if($stmt->get_result()->num_rows > 0){
            $stmt->close();
            exit("occupied");
        }

        $stmt->close();
    }

    exit("ready");
}

$row = $attendance->fetch_assoc();

if($row['time_out'] === NULL){

    $stmt = $conn->prepare("
        UPDATE attendance
        SET time_out=NOW()
        WHERE id=?
    ");

    $stmt->bind_param("i",$row['id']);
    $stmt->execute();
    $stmt->close();

    exit("time_out");
}

exit("already");
?>