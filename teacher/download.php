<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['user_id'])){
    exit();
}

$folder_id = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : 0;

if($folder_id <= 0){
    exit();
}

if(isset($_GET['file'])){

    $file = basename($_GET['file']);

    $res = $conn->query("
        SELECT file_path
        FROM submission_files
        WHERE folder_id = $folder_id
        AND file_name = '$file'
        AND is_deleted = 0
        LIMIT 1
    ");

    if(!$res || $res->num_rows == 0){
        exit();
    }

    $row = $res->fetch_assoc();
    $path = "../" . $row['file_path'];

    if(!file_exists($path)){
        exit();
    }

    header("Content-Description: File Transfer");
    header("Content-Type: application/octet-stream");

    $clean = preg_replace('/^\d+_/', '', $file);

    header("Content-Disposition: attachment; filename=\"" . $clean . "\"");
    header("Content-Length: " . filesize($path));

    readfile($path);
    exit();
}

$res = $conn->query("
    SELECT folder_name
    FROM submission_folders
    WHERE id = $folder_id
    LIMIT 1
");

if(!$res || $res->num_rows == 0){
    exit();
}

$row = $res->fetch_assoc();

$zipname = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['folder_name']) . ".zip";
$tmp = sys_get_temp_dir() . "/" . $zipname;

$zip = new ZipArchive();

if($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE){

    $files = $conn->query("
        SELECT 
            sf.file_name,
            sf.file_path,
            COALESCE(f.folder_name, '') AS child_folder_name
        FROM submission_files sf
        LEFT JOIN submission_folders f
            ON sf.folder_id = f.id
        WHERE (
            sf.folder_id = $folder_id
            OR sf.folder_id IN (
                SELECT id
                FROM submission_folders
                WHERE parent_id = $folder_id
            )
        )
        AND sf.is_deleted = 0
    ");

    if($files){
        while($f = $files->fetch_assoc()){

            $fullPath = "../" . $f['file_path'];

            if(file_exists($fullPath)){

                $cleanName = preg_replace('/^\d+_/', '', $f['file_name']);

                if(!empty($f['child_folder_name'])){
                    $zipPath = $f['child_folder_name'] . "/" . $cleanName;
                }else{
                    $zipPath = $cleanName;
                }

                $zip->addFile($fullPath, $zipPath);
            }
        }
    }

    $zip->close();
}

if(!file_exists($tmp)){
    exit("ZIP creation failed");
}

header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"" . $zipname . "\"");
header("Content-Length: " . filesize($tmp));

readfile($tmp);

unlink($tmp);
exit();
?>