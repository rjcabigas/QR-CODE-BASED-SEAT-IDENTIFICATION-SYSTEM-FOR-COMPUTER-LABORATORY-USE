<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../include/db.php";
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

require "../PHPMailer/src/PHPMailer.php";
require "../PHPMailer/src/SMTP.php";
require "../PHPMailer/src/Exception.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendStudentEmail($name, $email, $sid)

{
    if (empty($email)) return;

    $mail = new PHPMailer(true);

    try {

$mail->isSMTP();
$mail->Host = "smtp.gmail.com";
$mail->SMTPAuth = true;
$mail->Username = "qrseat.system@gmail.com";
$mail->Password = "vuagtmrwjcrcgtil";
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;

$mail->setFrom("qrseat.system@gmail.com", "BPC COMLAB");
$mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "Student Portal Account";

        $password = "@Student01";
        $loginLink = "https://qrlabseat.online/auth/student_login.php";

        $mail->Body = '
        <div style="padding:10px 0;font-family:Arial,sans-serif;">

        <div style="
        max-width:520px;
        margin:auto;
        background:#c9d7e4;
        padding:30px 40px 40px 40px;
        border-radius:40px;
        text-align:left;
        ">

        <h2 style="color:#0d6efd;margin:0;font-weight:700;">
            BPC COM-LAB
        </h2>

        <div style="
            display:inline-block;
            background:#0d6efd;
            color:white;
            padding:6px 14px;
            border-radius:6px;
            font-size:13px;
            margin-top:5px;
            margin-bottom:25px;
        ">
            Student Portal
        </div>

        <p style="font-size:16px;margin:0 0 15px 0;">
            Dear: <b>'.$name.'</b>,
        </p>

        <p style="color:#444;font-size:14px;line-height:1.6;">
            Your student portal account has been successfully created. 
            You may now access the system using the login credentials below.
        </p>

        <div style="
            border:2px solid #0d6efd;
            border-radius:20px;
            padding:20px;
            margin:25px 0;
            background:#ffffff;
        ">

        <p style="margin:0 0 10px 0;">
        <span style="color:#0d6efd;font-weight:600;">Username:</span><br>
        <span style="color:#ffffff;">'.$sid.' or '.$email.'</span>
        </p>

            <p style="margin:10px 0 0 0;">
                <span style="color:#0d6efd;font-weight:600;">Default Password:</span><br>
                '.$password.'
            </p>

        </div>

        <a href="'.$loginLink.'" style="
            display:block;
            text-align:center;
            background:#0d6efd;
            color:white;
            padding:14px;
            border-radius:8px;
            text-decoration:none;
            font-weight:600;
            margin-bottom:20px;
        ">
            Login your account here
        </a>

        <p style="
            font-size:12px;
            color:#555;
            text-align:center;
        ">
            For security purposes, please change your password after your first login.
        </p>

    </div>

</div>
';

        $mail->send();

    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
    }
}

function createUser($conn, $name, $email)
{
    if (empty($email)) return;

    $defaultPass = password_hash("@Student01", PASSWORD_DEFAULT);
    $role = "student";
    $statusUser = "active";

    $check = $conn->prepare("SELECT id FROM users WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 0) {

        $stmt = $conn->prepare("
        INSERT INTO users (fullname,email,password,role,status,created_at)
        VALUES (?,?,?,?,?,NOW())
        ");

        $stmt->bind_param("sssss", $name, $email, $defaultPass, $role, $statusUser);
        $stmt->execute();
    }
}

function studentExists($conn, $sid)
{
    $stmt = $conn->prepare("SELECT id FROM students WHERE student_id=?");
    $stmt->bind_param("s", $sid);
    $stmt->execute();
    $stmt->store_result();

    return $stmt->num_rows > 0;
}

function insertStudent($conn,$name,$sid,$course,$year,$section,$gender,$email,$semester)
{
    $status = "active";

    $stmt = $conn->prepare("
    INSERT INTO students
    (student_name,student_id,course,year,section,gender,email,semester,status)
    VALUES (?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "sssssssss",
        $name,$sid,$course,$year,$section,$gender,$email,$semester,$status
    );

    $stmt->execute();
}

function updateStudentStatus($conn,$id,$status)
{
    $stmt = $conn->prepare("UPDATE students SET status=? WHERE id=?");
    $stmt->bind_param("si",$status,$id);
    $stmt->execute();
}

function deleteStudent($conn,$id)
{
    $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
}

if(!empty($_FILES['excel_file']['tmp_name'])){

    $file = $_FILES['excel_file']['tmp_name'];

    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    unset($rows[0]);

    foreach($rows as $data){

$name     = strtoupper(trim($data[0] ?? ''));
$sid      = strtoupper(trim($data[1] ?? ''));
$course   = strtoupper(trim($data[2] ?? ''));
$year     = strtoupper(trim($data[3] ?? ''));
$section  = strtoupper(trim($data[4] ?? ''));
$gender   = strtoupper(trim($data[5] ?? ''));
$semester = strtoupper(trim($data[6] ?? ''));
$email    = strtolower(trim($data[7] ?? '')); // email lowercase

        if(empty($sid)) continue;

        if(!studentExists($conn,$sid)){

            insertStudent(
                $conn,
                $name,$sid,$course,$year,$section,$gender,$email,$semester
            );

            createUser($conn,$name,$email);

            if(!empty($email)){
                sendStudentEmail($name,$email,$sid);
            }

        }

    }

    header("Location: manage_student.php?saved=1");
    exit;
}

if(isset($_POST['confirm_delete'])){

    $id = (int)$_POST['delete_id'];

    if($id > 0){
        updateStudentStatus($conn,$id,"archived");
    }

    header("Location: manage_student.php?archived=1");
    exit;
}

if(isset($_POST['archive_selected'])){

    if(empty($_POST['selected_students'])){
        header("Location: manage_student.php");
        exit;
    }

    foreach($_POST['selected_students'] as $id){

        $id = (int)$id;

        if($id > 0){
            updateStudentStatus($conn,$id,"archived");
        }

    }

    header("Location: manage_student.php?deleted=1");
    exit;
}

if(isset($_POST['update_student'])){

    $id       = (int)$_POST['edit_id'];
    $name     = trim($_POST['student_name']);
    $sid      = trim($_POST['student_id']);
    $course   = trim($_POST['course']);
    $year     = trim($_POST['year']);
    $section  = trim($_POST['section']);
    $gender   = trim($_POST['gender']);
    $email    = trim($_POST['email']);
    $semester = trim($_POST['semester']);

    $stmt = $conn->prepare("
    UPDATE students
    SET student_name=?,student_id=?,course=?,year=?,section=?,gender=?,email=?,semester=?
    WHERE id=?
    ");

    $stmt->bind_param(
        "ssssssssi",
        $name,$sid,$course,$year,$section,$gender,$email,$semester,$id
    );

    $stmt->execute();

    header("Location: manage_student.php?updated=1");
    exit;
}

if(isset($_POST['save_student'])){

    $name     = trim($_POST['student_name']);
    $sid      = trim($_POST['student_id']);
    $course   = trim($_POST['course']);
    $year     = trim($_POST['year']);
    $section  = trim($_POST['section']);
    $gender   = trim($_POST['gender']);
    $email    = trim($_POST['email']);
    $semester = trim($_POST['semester']);

if(!studentExists($conn,$sid)){

    insertStudent(
        $conn,
        $name,$sid,$course,$year,$section,$gender,$email,$semester
    );

    createUser($conn,$name,$email);

    if(!empty($email)){
        sendStudentEmail($name,$email,$sid);
    }

}

    header("Location: manage_student.php?saved=1");
    exit;
}

if(isset($_GET['restore'])){

    $id = (int)$_GET['restore'];

    if($id > 0){
        updateStudentStatus($conn,$id,"active");
    }

    header("Location: archive.php?restored=1");
    exit;
}

if(isset($_GET['delete'])){

    $id = (int)$_GET['delete'];

    if($id > 0){

        // 🔥 kunin muna email ng student
        $stmt = $conn->prepare("SELECT email FROM students WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($row = $result->fetch_assoc()){

            $email = $row['email'];

            // ❌ delete sa users table
            if(!empty($email)){
                $delUser = $conn->prepare("DELETE FROM users WHERE email=?");
                $delUser->bind_param("s",$email);
                $delUser->execute();
            }
        }

        // ❌ delete sa students table
        deleteStudent($conn,$id);
    }

    header("Location: archive.php?deleted=1");
    exit;
}