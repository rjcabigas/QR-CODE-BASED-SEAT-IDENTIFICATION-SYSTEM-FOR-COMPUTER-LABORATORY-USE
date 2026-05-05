<?php
include "dashboard_data.php";

$data = json_decode(file_get_contents("php://input"), true);

if(empty($student_id)){
    echo "not_logged";
    exit;
}

if(isset($data['delete_subject'])){

    $subject = trim($data['delete_subject']);

    if($subject !== ""){

        $stmt = $conn->prepare("
            DELETE FROM student_subjects 
            WHERE student_id = ? AND subject = ?
        ");
        $stmt->bind_param("ss", $student_id, $subject);

        if($stmt->execute()){
            echo "deleted";
        }else{
            echo "error";
        }

        $stmt->close();
        exit;
    }
}

if(!isset($data['subjects']) || !is_array($data['subjects'])){
    echo "invalid";
    exit;
}

$subjects = $data['subjects'];

$stmtDelete = $conn->prepare("
    DELETE FROM student_subjects 
    WHERE student_id = ?
");
$stmtDelete->bind_param("s", $student_id);
$stmtDelete->execute();

$stmtInsert = $conn->prepare("
    INSERT INTO student_subjects (student_id, subject) 
    VALUES (?, ?)
");

foreach($subjects as $sub){
    $clean = trim($sub);
    if($clean !== ""){
        $stmtInsert->bind_param("ss", $student_id, $clean);
        $stmtInsert->execute();
    }
}

echo "success";
?>