<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher'){
    exit("unauthorized");
}

$teacher_id = (int)$_SESSION['user_id'];

if(!isset($_POST['section']) || trim($_POST['section']) === ''){
    exit("invalid");
}

$section = trim($_POST['section']);

$stmt = $conn->prepare("
SELECT id FROM teacher_sections
WHERE teacher_id=? AND section=?
LIMIT 1
");

$stmt->bind_param("is",$teacher_id,$section);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows === 0){
    exit("not_found");
}

$_SESSION['teacher_section'] = $section;

$stmt->close();

echo "ok";
?>