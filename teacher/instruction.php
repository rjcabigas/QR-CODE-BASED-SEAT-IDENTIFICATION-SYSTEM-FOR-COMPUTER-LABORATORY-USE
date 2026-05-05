<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['user_id'])){
    exit();
}

$action = $_REQUEST['action'] ?? '';

if($action == "get"){

    $folder_id = (int)$_GET['folder_id'];

    $res = $conn->query("
        SELECT instructions 
        FROM submission_folders 
        WHERE id = $folder_id
    ");

    $row = $res->fetch_assoc();

    echo $row['instructions'] ?? "";

    exit;
}

if($action == "save"){

    $folder_id = (int)$_POST['folder_id'];

    $raw_text = $_POST['text'] ?? '';
    $text = trim($raw_text);
    $text = $conn->real_escape_string($text);

    if($text != ""){

        $conn->query("
            UPDATE submission_folders
            SET instructions = '$text',
                has_new_instruction = 1,
                toast_seen = 0
            WHERE id = $folder_id
        ");

    } else {

        $conn->query("
            UPDATE submission_folders
            SET instructions = '',
                has_new_instruction = 0,
                toast_seen = 1
            WHERE id = $folder_id
        ");
    }

    echo "ok";

    exit;
}
?>