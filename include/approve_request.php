<?php
include "db.php";

if(isset($_POST['target'])){

$target = $_POST['target'];
$requester = ($target === 'admin') ? 'mis' : 'admin';

$stmt = $conn->prepare("
UPDATE access_requests 
SET status='approved' 
WHERE requester_role=? 
AND target_role=? 
AND status='pending'
ORDER BY id DESC
LIMIT 1
");

$stmt->bind_param("ss", $requester, $target);

if($stmt->execute()){
echo "success";
}else{
echo "error";
}

}
?>