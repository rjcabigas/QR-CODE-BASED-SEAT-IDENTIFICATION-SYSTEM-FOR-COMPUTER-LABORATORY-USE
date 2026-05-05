<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['student_id'])){
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("
SELECT student_name, student_id, email, course, year, section, profile_pic
FROM students
WHERE student_id=?
LIMIT 1
");

$stmt->bind_param("s",$student_id);
$stmt->execute();

$student = $stmt->get_result()->fetch_assoc();

if(!$student){
    die("Student not found");
}

if($_SERVER['REQUEST_METHOD'] === "POST"){

$new = trim($_POST['new_password'] ?? '');
$confirm = trim($_POST['confirm_password'] ?? '');

if($new === "" || $confirm === ""){
    $error = "Please fill all password fields";
}

elseif($new !== $confirm){
    $error = "Passwords do not match";
}

elseif(strlen($new) < 6){
    $error = "Password must be at least 6 characters";
}

else{

$hash = password_hash($new, PASSWORD_DEFAULT);

$up = $conn->prepare("
UPDATE users
SET password=?
WHERE email=?
LIMIT 1
");

$up->bind_param("ss",$hash,$student['email']);
$up->execute();

if($up->affected_rows > 0){

header("Location: profile_mobile.php?success=1");
exit();

}else{
$error = "No changes made";
}

}

}

if(isset($_GET['success'])){
$success = "Password updated successfully";
}

preg_match('/\d+/', $student['year'], $matches);
$year_number = $matches[0] ?? '';

$full_section =
htmlspecialchars($student['course']."-".$year_number.$student['section']);

$masked_id =
substr($student['student_id'],0,2)."*****".
substr($student['student_id'],-3);

$email_mask =
substr($student['email'],0,3)."*****".
strstr($student['email'],"@");

$profile_pic =
!empty($student['profile_pic'])
? htmlspecialchars($student['profile_pic'])
: "profile.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Profile</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../cropper.min.css">
<link rel="stylesheet" href="../mobile_css/student_profile_view.css">
</head>

<body>

<form method="POST">

<div class="container" id="mainContent">

<div class="profile-header">

<img src="../img/student_img/back.png" class="back-btn" id="backBtn">

<div class="profile-left">

<div class="profile-pic-wrapper">

    <img src="../uploads/<?php echo !empty($student['profile_pic']) ? $student['profile_pic'] : 'profile.png'; ?>" 
    class="profile-pic" id="profilePic">

    <div class="profile-loading" id="profileLoading">
        <div class="spinner"></div>
    </div>

</div>

<img src="../img/student_img/camera.png" class="camera-icon" id="cameraBtn">

<div id="uploadForm">
<input type="file" name="profile" id="fileInput" accept="image/*" hidden>
</div>

</div>

<div class="profile-info">
<h3 class="student-name"><?php echo htmlspecialchars($student['student_name']); ?></h3>
<p><?php echo $masked_id; ?></p>
</div>

<img src="../img/student_img/qr.png" class="qr-icon" id="openQR">

</div>

<div class="section-title">
<img src="../img/student_img/student_info.png">
<span>My Personal Information</span>
<img src="../img/student_img/drop_down.png" class="dropdown-btn personal-toggle">
</div>

<div class="personal-info">

<div class="info-card">
<img src="../img/student_img/student_name.png">
<input type="text"
       value="<?php echo htmlspecialchars($student['student_name']); ?>"
       readonly>
</div>

<div class="info-card">
<img src="../img/student_img/student_id.png">
<input type="text" value="<?php echo $student['student_id']; ?>" readonly>
</div>

<div class="info-card">
<img src="../img/student_img/email.png">
<input type="email"
       value="<?php echo htmlspecialchars($student['email']); ?>"
       data-field="email"
       readonly>
       <img src="../img/student_img/update.png" class="edit-icon">
</div>

<div class="info-card">
<img src="../img/student_img/sections.png">
<input type="text"
       value="<?php echo $student['section']; ?>"
       readonly>
</div>

</div>

<div class="section-title">
<img src="../img/student_img/security.png">
<span>Security</span>
<img src="../img/student_img/drop_down.png" class="dropdown-btn security-toggle">
</div>

<div class="security-info">

<div class="info-card" id="newPassCard">
<img src="../img/student_img/password.png">
<input type="password" name="new_password" id="newPassword" placeholder="New Password:">
<div id="passwordMsg" class="pass-msg"></div>
</div>

<div class="info-card" id="confirmPassCard">
<img src="../img/student_img/key.png">
<input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm Password:">
<div id="confirmMsg" class="pass-msg"></div>
</div>

<div class="show-pass" id="togglePass">
<img src="../img/student_img/box.png" id="passIcon">
<label>Show Password</label>
</div>

</div>

<button type="submit" class="save-btn" id="updatePassBtn" disabled>
<span id="btnText">Update Password</span>
</button>

</div>

</form>

<div class="qr-overlay" id="qrOverlay">

<div class="qr-panel">

<img src="../img/student_img/back.png" class="qr-back" id="closeQR">

<img src="../uploads/<?php echo !empty($student['profile_pic']) ? $student['profile_pic'] : 'profile.png'; ?>" 
class="qr-profile">

<div class="qr-email"><?php echo $email_mask; ?></div>

<img src="../img/student_img/qr_code.png" class="qr-code">

<div class="qr-message">Save & use this to fast to LOGIN.</div>

</div>

<div class="qr-save-area">

<div class="download-box">
<img src="../img/student_img/download.png">
</div>

<div class="save-text">SAVE</div>

</div>

</div>

<div id="cropModal" class="crop-modal">
<img id="cropImage" class="crop-image">
<button id="cropBtn">Crop & Upload</button>
</div>

<div id="toastSuccess" class="toast-success">
Password updated successfully
</div>

<div id="toastSave" class="toast-success">
Saved successfully!
</div>
<script src="../cropper.min.js"></script>
<script src="../js/student_profile_view.js"></script>
<script src="../js/student_profile.js"></script>

</body>
</html>