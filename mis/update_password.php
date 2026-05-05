<?php
session_start();
require_once "../include/db.php";

if(!isset($_SESSION['user_id'])) exit;

$id = intval($_SESSION['user_id']);
$newPass = trim($_POST['password'] ?? "");
$otp = trim($_POST['otp'] ?? "");

if($newPass=="" || $otp==""){
    echo "missing";
    exit;
}

$stmt = $conn->prepare("
SELECT reset_token, reset_expire 
FROM users WHERE id=? LIMIT 1
");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows!=1){
    echo "invalid";
    exit;
}

$row = $res->fetch_assoc();

if($row['reset_token'] !== $otp){
    echo "wrong_otp";
    exit;
}

if(strtotime($row['reset_expire']) < time()){
    echo "expired";
    exit;
}

$hashed = password_hash($newPass, PASSWORD_DEFAULT);

$stmt = $conn->prepare("
UPDATE users 
SET password=?, reset_token=NULL, reset_expire=NULL, first_login=0
WHERE id=?
");
$stmt->bind_param("si",$hashed,$id);
$stmt->execute();

echo "success";
?>