<?php
include "../include/db.php";

session_start();

$user_id = $_SESSION['user_id'];
$status = isset($_POST['status']) ? intval($_POST['status']) : 0;

$stmt = $conn->prepare("
    INSERT INTO user_filters (user_id, feedback_enabled)
    VALUES (?, ?)
    ON DUPLICATE KEY UPDATE feedback_enabled=VALUES(feedback_enabled)
");
$stmt->bind_param("ii", $user_id, $status);
$stmt->execute();
$stmt->close();

echo "success";
?>