<?php
session_start();
include "../include/db.php";

if(isset($_POST['minutes']) 
   && isset($_SESSION['teacher_subject']) 
   && isset($_SESSION['teacher_section'])){

    $minutes = intval($_POST['minutes']);
    $teacher_id = $_SESSION['user_id'];
    $subject = $_SESSION['teacher_subject'];
    $section = $_SESSION['teacher_section'];

    $stmt = $conn->prepare("
        UPDATE teacher_subjects
        SET late_minutes = ?
        WHERE teacher_id = ?
        AND subject = ?
        AND section = ?
    ");

    $stmt->bind_param("iiss", 
        $minutes, 
        $teacher_id, 
        $subject,
        $section
    );

    $stmt->execute();

    echo "ok";
}
?>