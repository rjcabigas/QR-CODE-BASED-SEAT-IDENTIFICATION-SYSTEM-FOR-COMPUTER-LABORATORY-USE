<?php
session_start();

date_default_timezone_set('Asia/Manila');

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['mis','admin'])){
header("Location: ../auth/login.php");
exit;
}

$conn = new mysqli("localhost","root","","mydatabase");

if($conn->connect_error){
die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT first_login FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

$forceChange = ($row['first_login'] == 1);
$stmt->close();

$studentQuery = $conn->query("SELECT COUNT(*) AS total FROM students");
$totalStudents = 0;
if($studentQuery){
$row = $studentQuery->fetch_assoc();
$totalStudents = $row['total'];
}

$comlabQuery = $conn->query("SELECT COUNT(*) AS total FROM comlabs");
$totalComlabs = 0;
if($comlabQuery){
$row = $comlabQuery->fetch_assoc();
$totalComlabs = $row['total'];
}

$courseQuery = $conn->query("SELECT COUNT(DISTINCT course) AS total FROM students");
$totalCourses = 0;
if($courseQuery){
$row = $courseQuery->fetch_assoc();
$totalCourses = $row['total'];
}

$teacherQuery = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='teacher'");
$totalTeachers = 0;
if($teacherQuery){
$row = $teacherQuery->fetch_assoc();
$totalTeachers = $row['total'];
}

$working = 0;
$notworking = 0;
$defective = 0;
$others = 0;

$res = $conn->query("
SELECT LOWER(TRIM(pc_status)) as status, COUNT(*) as total
FROM attendance
WHERE pc_status IS NOT NULL
GROUP BY LOWER(TRIM(pc_status))
");

if($res){
while($row = $res->fetch_assoc()){
$status = $row['status'];

if($status == "working") $working = $row['total'];
elseif($status == "not working") $notworking = $row['total'];
elseif($status == "defective") $defective = $row['total'];
else $others += $row['total'];
}
}

if(isset($_POST['update_password'])){
$password = trim($_POST['password'] ?? '');
$confirm = trim($_POST['confirm'] ?? '');

if($password === ""){
echo "empty";
exit;
}

if(strlen($password) < 8){
echo "short";
exit;
}

if($password !== $confirm){
echo "notmatch";
exit;
}

if(!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9]).{8,}$/',$password)){
echo "weak";
exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE users SET password=?, first_login=0 WHERE id=?");

if(!$stmt){
echo "sqlerror";
exit;
}

$stmt->bind_param("si",$hash,$userId);
$run = $stmt->execute();

if($run){
unset($_SESSION['force_change_password']);
echo "updated";
}else{
echo "failed";
}

$stmt->close();
exit;
}

if(isset($_POST['update_email'])){
$newEmail = trim($_POST['email'] ?? '');
$userId = $_SESSION['user_id'];

if($newEmail === ""){
echo "empty";
exit;
}

if(!filter_var($newEmail, FILTER_VALIDATE_EMAIL)){
echo "invalid";
exit;
}

$stmt = $conn->prepare("UPDATE users SET email=? WHERE id=?");

if(!$stmt){
echo "sqlerror";
exit;
}

$stmt->bind_param("si",$newEmail,$userId);
$run = $stmt->execute();

if($run){
$_SESSION['email'] = $newEmail;
echo "success";
}else{
echo "failed";
}

$stmt->close();
exit;
}

$date = date("F d, Y");

$loginTime = $_SESSION['login_time'] ?? time();
$loginTimeFormatted = date("h:i A",$loginTime);
$loginDateFormatted = date("F d, Y",$loginTime);

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname,email,role FROM users WHERE id=?");
$stmt->bind_param("i",$userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

$email = $userData['email'] ?? "";
$fullname = $userData['fullname'] ?? "";
$role = $userData['role'] ?? "";

$parts = explode("@",$email);
$username = $parts[0] ?? "";
$domain = $parts[1] ?? "";

$visible = substr($username,0,3);
$hiddenLength = max(strlen($username)-3,0);
$hidden = str_repeat("*",$hiddenLength);

$name = $visible.$hidden."@".$domain;

$feedbackQuery = $conn->query("
SELECT tf.*, 
t.fullname AS teacher_name,
s.student_name AS student_name
FROM teacher_feedback tf
LEFT JOIN users t ON tf.teacher_id = t.id
LEFT JOIN students s ON tf.student_id = s.student_id
ORDER BY tf.created_at DESC
LIMIT 3
");
?>