<?php
session_start();
include "../include/db.php";

$q = $conn->query("
SELECT COUNT(*) as total
FROM maintenance
WHERE teacher_action='approved'
AND admin_action IS NULL
AND resolved IS NULL
");

$data = $q->fetch_assoc();

echo $data['total'];