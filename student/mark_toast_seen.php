<?php
include("../include/db.php");

if(isset($_GET['id'])){

    $id = (int)$_GET['id'];

    mysqli_query($conn, "
        UPDATE submission_folders
        SET toast_seen = 1
        WHERE id = $id
    ");
}

exit;
?>