<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['user_id'])) exit;

$teacher_id = $_SESSION['user_id'];

if(!isset($_FILES['file'])) exit;

$fileName = $_FILES['file']['name'];

$conn->query("INSERT INTO quizzes (teacher_id,title,total_questions,timer)
VALUES ($teacher_id,'Generated from $fileName',10,30)");

$quiz_id = $conn->insert_id;

for($i=1;$i<=10;$i++){

    $question = "Sample Question $i from $fileName";

    $conn->query("INSERT INTO quiz_questions 
    (quiz_id,question,option_a,option_b,option_c,option_d,correct_answer)
    VALUES
    ($quiz_id,'$question','Choice A','Choice B','Choice C','Choice D','a')");
}

echo json_encode([
    "status"=>"success",
    "quiz_id"=>$quiz_id
]);