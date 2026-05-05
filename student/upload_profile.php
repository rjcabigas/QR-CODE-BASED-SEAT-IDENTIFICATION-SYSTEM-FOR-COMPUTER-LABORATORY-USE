<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['student_id'])){
    exit("not_logged");
}

$student_id = $_SESSION['student_id'];

if(!isset($_FILES['profile'])){
    exit("no_file");
}

$img = $_FILES['profile'];

if($img['error'] !== 0){
    exit("upload_error");
}

$allowed = ["jpg","jpeg","png","webp"];

$ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));

if(!in_array($ext,$allowed)){
    exit("invalid_type");
}

$maxSize = 5 * 1024 * 1024;

if($img['size'] > $maxSize){
    exit("too_large");
}

$name = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;

$target = "../uploads/" . $name;

if(!move_uploaded_file($img['tmp_name'],$target)){
    exit("upload_failed");
}

$get = $conn->prepare("
SELECT profile_pic
FROM students
WHERE student_id=?
LIMIT 1
");

$get->bind_param("s",$student_id);
$get->execute();

$result = $get->get_result();

if($row = $result->fetch_assoc()){

    $old = $row['profile_pic'];

    if($old && $old !== "profile.png"){

        $oldPath = "../uploads/" . $old;

        if(file_exists($oldPath)){
            unlink($oldPath);
        }

    }

}

$stmt = $conn->prepare("
UPDATE students
SET profile_pic=?
WHERE student_id=?
");

$stmt->bind_param("ss",$name,$student_id);
$stmt->execute();

echo "success";
?>