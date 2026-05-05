<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['user_id']) || !isset($_SESSION['teacher_subject'])){
    exit("error");
}

if(isset($_POST['start_time']) && isset($_POST['end_time'])){

    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];

    $stmt = $conn->prepare("
        UPDATE teacher_subjects
        SET start_time = ?, end_time = ?
        WHERE teacher_id = ?
        AND subject = ?
    ");

    $stmt->bind_param(
        "ssis",
        $start,
        $end,
        $_SESSION['user_id'],
        $_SESSION['teacher_subject']
    );

    if($stmt->execute()){
        echo "ok";
    }else{
        echo "error";
    }

    $stmt->close();
}
?>