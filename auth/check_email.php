<?php
require_once "../include/db.php";

if($_SERVER['REQUEST_METHOD']!=='POST') exit;

$email=strtolower(trim($_POST['email'] ?? ''));

if(!$email) exit;

$stmt=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s",$email);
$stmt->execute();
$stmt->store_result();

echo $stmt->num_rows ? "ok" : "notfound";

$stmt->close();
$conn->close();