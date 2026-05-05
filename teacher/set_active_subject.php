<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher'){
    exit("unauthorized");
}

$teacher_id = (int)$_SESSION['user_id'];

if(!isset($_POST['subject']) || trim($_POST['subject']) === ''){
    exit("invalid");
}

$subject = trim($_POST['subject']);

$stmt = $conn->prepare("
SELECT id FROM teacher_subjects
WHERE teacher_id=? AND subject=?
LIMIT 1
");

$stmt->bind_param("is",$teacher_id,$subject);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows === 0){
    exit("not_found");
}

$_SESSION['teacher_subject'] = $subject;

$stmt->close();

echo "ok";
?>