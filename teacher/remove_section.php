<?php
session_start();
include "../include/db.php";

if(isset($_POST['section']) && isset($_SESSION['user_id'])){
    
    $section = $_POST['section'];
    $teacher = $_SESSION['user_id'];

    $stmt = $conn->prepare("DELETE FROM teacher_sections WHERE teacher_id=? AND section=?");
    $stmt->bind_param("is",$teacher,$section);
    $stmt->execute();
    $stmt->close();

    if($_SESSION['teacher_section'] === $section){
        unset($_SESSION['teacher_section']);
    }

    echo "ok";
}