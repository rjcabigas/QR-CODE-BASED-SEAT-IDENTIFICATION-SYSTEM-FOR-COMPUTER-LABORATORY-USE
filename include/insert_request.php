<?php
include "db.php";

if(isset($_POST['target']) && isset($_POST['requester'])){

$target = $_POST['target'];
$requester = $_POST['requester'];

$stmt = $conn->prepare("INSERT INTO access_requests (requester_role, target_role) VALUES (?, ?)");
$stmt->bind_param("ss", $requester, $target);

if($stmt->execute()){
echo "success";
}else{
echo "error";
}

}




