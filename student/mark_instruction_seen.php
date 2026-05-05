<?php
include("../include/db.php");

if(isset($_GET['id'])){

    $id = (int)$_GET['id'];

    mysqli_query($conn, "
        UPDATE submission_folders
        SET has_new_instruction = 0
        WHERE id = $id
    ");
}

exit;
?>