<?php

require_once "../include/db.php";

$pc = $_GET['pc'] ?? '';

$stmt = $conn->prepare("
SELECT issue_type, description, approved_at, admin_action, resolved
FROM maintenance
WHERE pc_no = ?
AND teacher_action='approved'
ORDER BY approved_at DESC
LIMIT 1
");

$stmt->bind_param("s",$pc);
$stmt->execute();
$res = $stmt->get_result();

if($row = $res->fetch_assoc()){

$datetime = strtotime($row['approved_at']);

$date = date("F d Y",$datetime);
$time = date("h:i A",$datetime);

$status="PENDING";

if($row['resolved']=="yes"){
$status="RESOLVED";
}
elseif($row['admin_action']=="approved"){
$status="IN PROGRESS";
}
elseif($row['admin_action']=="rejected"){
$status="REJECTED";
}

echo json_encode([
"type"=>$row['issue_type'],
"desc"=>$row['description'],
"date"=>$date,
"time"=>$time,
"status"=>$status
]);

}else{

echo json_encode([
"type"=>"-",
"desc"=>"-",
"date"=>"-",
"time"=>"-",
"status"=>"-"
]);

}