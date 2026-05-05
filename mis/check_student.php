<?php
require_once "../include/db.php";

if(isset($_POST['email'])){

$email = $_POST['email'];

$stmt = $conn->prepare("SELECT id FROM students WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();
$stmt->store_result();

echo $stmt->num_rows > 0 ? "exist" : "ok";
}
?>