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

$date = date("F d, Y");

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT fullname,email FROM users WHERE id=?");
$stmt->bind_param("i",$userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

$email = $userData['email'] ?? "";

$parts = explode("@",$email);
$username = $parts[0] ?? "";
$domain = $parts[1] ?? "";

$visible = substr($username,0,3);
$hidden = str_repeat("*", max(strlen($username)-3,0));

$name = $visible.$hidden."@".$domain;

$feedbackQuery = $conn->query("
SELECT tf.*, 
t.fullname AS teacher_name,
s.student_name AS student_name
FROM teacher_feedback tf
LEFT JOIN users t ON tf.teacher_id = t.id
LEFT JOIN students s ON tf.student_id = s.student_id
ORDER BY tf.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<title>Teacher Feedback</title>

<link rel="stylesheet" href="../css/mis/mis_dashboard.css">
<link rel="stylesheet" href="../css/mis/mis_dashboard_layout.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<?php include "../include/mis_sidebar.php"; ?>

<div class="main-content">

<div class="dashboard-header">
<div>
<h1>Teacher’s Feedback</h1>
</div>
</div>

<div class="feedback-container-page">

<?php if($feedbackQuery && $feedbackQuery->num_rows > 0){ ?>

    <?php while($feedback = $feedbackQuery->fetch_assoc()){ ?>

    <div class="feedback-box">

        <div class="feedback-top">

            <div class="feedback-user">
                <img src="../img/mis_img/profile.png">
                <div>

                    <p class="feedback-name">
                    <?php
                    if($feedback['anonymous'] == 1){
                        echo "Anonymous";
                    }else{
                        if(!empty($feedback['student_name'])){
                            echo htmlspecialchars($feedback['student_name']);
                        }else{
                            echo "Student";
                        }
                    }
                    ?>
                    </p>

                    <p class="feedback-course">
                        <?= htmlspecialchars($feedback['section']) ?>
                    </p>

                </div>
            </div>

            <div class="feedback-status">
                <p class="feedback-teacher">
                    <?= htmlspecialchars($feedback['teacher_name']) ?>
                </p>

                <p class="feedback-date">
                    <?= date("F d, Y", strtotime($feedback['created_at'])) ?>
                </p>
            </div>

        </div>

        <div class="feedback-message-full">
            <?= htmlspecialchars($feedback['comment']) ?>
        </div>

    </div>

    <?php } ?>

<?php } else { ?>

    <div class="no-feedback">
        <img src="../img/mis_img/no_comment.png" alt="No Comment">
        <p>No comment submitted yet</p>
    </div>

<?php } ?>

</div>

</div>

</body>
</html>