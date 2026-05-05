<?php
session_start();
include "../include/db.php";

$conn->query("SET time_zone = '+08:00'"); 

$teacherId = $_SESSION['user_id'] ?? 0;

if(!$teacherId){
    header("Location: ../auth/login.php");
    exit();
}

$stmt = $conn->prepare("SELECT id FROM teacher_subjects WHERE teacher_id=? LIMIT 1");
$stmt->bind_param("i",$teacherId);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows === 0){
    $_SESSION['teacher_subject'] = '';
}

$stmt->close();

$stmt = $conn->prepare("SELECT id FROM teacher_sections WHERE teacher_id=? LIMIT 1");
$stmt->bind_param("i",$teacherId);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows === 0){
    $_SESSION['teacher_section'] = '';
}

$stmt->close();

if(isset($_GET['section'])){
    $_SESSION['teacher_section'] = $_GET['section'];
}

$_SESSION['teacher_section'] = $_SESSION['teacher_section'] ?? '';
$_SESSION['teacher_subject'] = $_SESSION['teacher_subject'] ?? '';

$subjectStart = '';
$subjectEnd   = '';
$lateMinutes  = 0;

if($_SESSION['teacher_subject']){

    $stmt = $conn->prepare("
        SELECT start_time,end_time,late_minutes
        FROM teacher_subjects
        WHERE teacher_id=? AND subject=?
        LIMIT 1
    ");

    $stmt->bind_param("is",$teacherId,$_SESSION['teacher_subject']);
    $stmt->execute();

    $res = $stmt->get_result();

    if($row = $res->fetch_assoc()){
        $subjectStart = $row['start_time'];
        $subjectEnd   = $row['end_time'];
        $lateMinutes  = (int)$row['late_minutes'];
    }

    $stmt->close();
}

$displayRange = "00:00 PM - 00:00 PM";

if($subjectStart && $subjectEnd){

    $displayRange =
        date("h:i A",strtotime($subjectStart)).
        " - ".
        date("h:i A",strtotime($subjectEnd));
}

$lateHour   = floor($lateMinutes / 60);
$lateMinute = $lateMinutes % 60;

function formatLabel($text){

    $parts = explode(" - ",$text);

    if(count($parts)==3){
        preg_match('/\d+/',$parts[1],$m);
        return $parts[0]."-".$m[0].$parts[2];
    }

    return $text;
}

$course='';
$year='';
$section='';

if($_SESSION['teacher_section']){

    $full = $_SESSION['teacher_section'];
    $parts = explode("-",$full);

    if(count($parts)===2){

        $course = trim($parts[0]);
        $yearSection = trim($parts[1]);

        $yearNumber = preg_replace('/[^0-9]/','',$yearSection);
        $section    = preg_replace('/[^A-Za-z]/','',$yearSection);

        $yearMap = [
            "1"=>"1ST YEAR",
            "2"=>"2ND YEAR",
            "3"=>"3RD YEAR",
            "4"=>"4TH YEAR"
        ];

        $year = $yearMap[$yearNumber] ?? $yearNumber."TH YEAR";
    }
}

$attendanceRows = [];

if($_SESSION['teacher_subject'] && $_SESSION['teacher_section']){

    $stmt = $conn->prepare("
        SELECT
            a.pc_no,
            s.student_name,
            s.course,
            s.year,
            s.section,
            a.comlab_no,
            a.pc_status,
            a.status,
            a.time_in,
            a.time_out,
            a.date
        FROM attendance a
        JOIN students s ON s.student_id = a.student_id
        JOIN teacher_subjects ts ON ts.id = a.teacher_subject_id
        WHERE ts.teacher_id=?
        AND ts.subject=?
        AND ts.section=?
        AND a.date = CURDATE()
        ORDER BY a.id DESC
    ");

    $stmt->bind_param(
        "iss",
        $teacherId,
        $_SESSION['teacher_subject'],
        $_SESSION['teacher_section']
    );

    $stmt->execute();

    $res = $stmt->get_result();

    while($row = $res->fetch_assoc()){
        $attendanceRows[] = $row;
    }

    $stmt->close();
}
?>