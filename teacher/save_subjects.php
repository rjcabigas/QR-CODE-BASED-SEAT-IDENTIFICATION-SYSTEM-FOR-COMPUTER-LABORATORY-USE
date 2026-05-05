<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['user_id'])){
    exit;
}

$teacher_id = (int)$_SESSION['user_id'];

$sections = array_values(array_unique($_POST['sections'] ?? []));
$subjects = $_POST['subjects'] ?? [];
$starts   = $_POST['start_times'] ?? [];
$ends     = $_POST['end_times'] ?? [];

$checkSub = $conn->prepare("
SELECT id FROM teacher_subjects
WHERE teacher_id=? AND subject=? AND section=?
");

$insertSub = $conn->prepare("
INSERT INTO teacher_subjects
(teacher_id,subject,start_time,end_time,section,late_minutes)
VALUES (?,?,?,?,?,0)
");

$checkSec = $conn->prepare("
SELECT id FROM teacher_sections
WHERE teacher_id=? AND section=?
");

$insertSec = $conn->prepare("
INSERT INTO teacher_sections (teacher_id,section)
VALUES (?,?)
");

foreach($sections as $sec){

    $sec = trim($sec);
    if($sec === "") continue;

    for($i=0;$i<count($subjects);$i++){

        $subject = trim($subjects[$i]);
        if($subject === "") continue;

        $start = !empty($starts[$i]) ? $starts[$i] : null;
        $end   = !empty($ends[$i])   ? $ends[$i]   : null;

        $checkSub->bind_param("iss", $teacher_id, $subject, $sec);
        $checkSub->execute();
        $checkSub->store_result();

        if($checkSub->num_rows == 0){

            $insertSub->bind_param(
                "issss",
                $teacher_id,
                $subject,
                $start,
                $end,
                $sec
            );

            $insertSub->execute();
        }
    }

    $checkSec->bind_param("is", $teacher_id, $sec);
    $checkSec->execute();
    $checkSec->store_result();

    if($checkSec->num_rows == 0){
        $insertSec->bind_param("is", $teacher_id, $sec);
        $insertSec->execute();
    }
}

$checkSub->close();
$insertSub->close();
$checkSec->close();
$insertSec->close();

echo "ok";
?>