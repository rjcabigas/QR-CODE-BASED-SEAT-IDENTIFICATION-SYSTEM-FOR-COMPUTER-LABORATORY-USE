<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['student_id'])) exit;

$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

$allowed = ["student_name","student_id","email","section"];

if(!in_array($field,$allowed)) exit;

$student_id = $_SESSION['student_id'];

if($field === "email"){

    $getOld = $conn->prepare("
        SELECT email
        FROM students
        WHERE student_id=?
        LIMIT 1
    ");

    $getOld->bind_param("s", $student_id);
    $getOld->execute();
    $result = $getOld->get_result();
    $oldData = $result->fetch_assoc();

    $old_email = $oldData['email'] ?? '';
}

$stmt = $conn->prepare("UPDATE students SET $field=? WHERE student_id=? LIMIT 1");
$stmt->bind_param("ss",$value,$student_id);
$stmt->execute();

if($field === "email" && !empty($old_email)){

    $userUpdate = $conn->prepare("
        UPDATE users
        SET email=?
        WHERE email=?
        LIMIT 1
    ");

    $userUpdate->bind_param("ss", $value, $old_email);
    $userUpdate->execute();
}