<?php
session_start();
require_once "../include/db.php";

if (!isset($_SESSION['user_id'])) {
    echo "invalid";
    exit;
}

if (!isset($_POST['otp'])) {
    echo "invalid";
    exit;
}

$user_id = intval($_SESSION['user_id']);
$userOtp = trim($_POST['otp']);

$result = mysqli_query($conn, "
SELECT reset_token, reset_expire 
FROM users 
WHERE id = $user_id
");

if (!$result || mysqli_num_rows($result) == 0) {
    echo "invalid";
    exit;
}

$row = mysqli_fetch_assoc($result);

$dbOtp = $row['reset_token'];
$dbExpire = $row['reset_expire'];

if (strtotime($dbExpire) < time()) {
    echo "invalid";
    exit;
}

if ($userOtp === $dbOtp) {
    echo "valid";
} else {
    echo "invalid";
}
?>