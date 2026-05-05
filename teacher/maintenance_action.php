<?php
session_start();
include "../include/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_POST['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = (int)$_POST['id'];

$status = null;
$teacher_action = null;

if (isset($_POST['approve'])) {
    $status = "approved";
    $teacher_action = "approved";
}

if (isset($_POST['reject'])) {
    $status = "rejected";
    $teacher_action = "rejected";
}

if ($status && $teacher_action) {

    if ($status === "approved") {

        $stmt = $conn->prepare("
            UPDATE maintenance 
            SET status=?, teacher_action=?, approved_at=NOW() 
            WHERE id=?
        ");

        $stmt->bind_param("ssi", $status, $teacher_action, $id);

    } else {

        $stmt = $conn->prepare("
            UPDATE maintenance 
            SET status=?, teacher_action=? 
            WHERE id=?
        ");

        $stmt->bind_param("ssi", $status, $teacher_action, $id);
    }

    $stmt->execute();
    $stmt->close();
}

header("Location: dashboard.php");
exit();