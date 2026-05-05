<?php
session_start();
include("../include/db.php");

if(isset($_POST['folder_id']) && isset($_POST['name'])){

    if(!isset($_SESSION['student_id'])){
        exit();
    }

    $student_id = $_SESSION['student_id'];

    $folder_id = intval($_POST['folder_id']);
    $name = trim($_POST['name']);

    if($name === ""){
        exit();
    }

    if(strlen($name) > 100){
        $name = substr($name,0,100);
    }

    $safeName = preg_replace("/[^a-zA-Z0-9 _-]/","",$name);

    if($safeName === ""){
        exit();
    }

    $studentQuery = mysqli_query($conn, "
        SELECT course, year, section FROM students 
        WHERE student_id = '$student_id'
    ");

    $studentData = mysqli_fetch_assoc($studentQuery);

    $section = '';

    if($studentData){
        $year = preg_replace('/[^0-9]/', '', $studentData['year']);
        $course = strtoupper(trim($studentData['course']));
        $sec = strtoupper(trim($studentData['section']));

        $section = $course . '-' . $year . $sec;
    }

    $parentQuery = mysqli_query($conn, "
        SELECT teacher_id FROM submission_folders 
        WHERE id = '$folder_id'
    ");

    $parentData = mysqli_fetch_assoc($parentQuery);
    $teacher_id = $parentData ? $parentData['teacher_id'] : 0;

    $check = $conn->prepare("
        SELECT id 
        FROM submission_folders 
        WHERE parent_id=? AND folder_name=? 
        LIMIT 1
    ");

    $check->bind_param("is",$folder_id,$safeName);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        exit();
    }

    $stmt = $conn->prepare("
        INSERT INTO submission_folders(folder_name,parent_id,teacher_id,section,student_id)
        VALUES(?,?,?,?,?)
    ");

    $stmt->bind_param("siisi",$safeName,$folder_id,$teacher_id,$section,$student_id);
    $stmt->execute();
}
?>