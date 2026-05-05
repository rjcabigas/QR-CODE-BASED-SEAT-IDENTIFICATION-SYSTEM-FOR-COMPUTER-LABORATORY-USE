<?php
session_start();
include "../include/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../auth/login.php");
    exit();
}

$teacher_id = (int)$_SESSION['user_id'];
$currentFolderName = "";

$active_section = isset($_SESSION['teacher_section']) 
    ? $conn->real_escape_string($_SESSION['teacher_section']) 
    : '';

$sections = $conn->query("
    SELECT section
    FROM teacher_sections
    WHERE teacher_id = $teacher_id
    ORDER BY section ASC
");

if(!$sections){
    die("Error fetching sections: " . $conn->error);
}

if(isset($_GET['action']) && $_GET['action'] == "get_deadline"){
    $id = (int)$_GET['folder_id'];

    $res = $conn->query("SELECT * FROM folder_deadlines WHERE folder_id=$id LIMIT 1");

    if($res && $res->num_rows > 0){
        echo json_encode($res->fetch_assoc());
    }else{
        echo json_encode(null);
    }
    exit;
}

if(isset($_POST['action'])){

if($_POST['action']=="set_deadline"){
    $id = (int)$_POST['id'];

    $start = ($_POST['start'] === "--:--") ? NULL : $conn->real_escape_string($_POST['start']);
    $end = ($_POST['end'] === "--:--") ? NULL : $conn->real_escape_string($_POST['end']);
    $duration = $_POST['duration'] !== "" ? (int)$_POST['duration'] : NULL;

    $check = $conn->query("SELECT id FROM folder_deadlines WHERE folder_id=$id");

    if($check && $check->num_rows > 0){
        $conn->query("
            UPDATE folder_deadlines 
            SET start_time=" . ($start ? "'$start'" : "NULL") . ",
                end_time=" . ($end ? "'$end'" : "NULL") . ",
                duration=" . ($duration !== NULL ? $duration : "NULL") . "
            WHERE folder_id=$id
        ");
    }else{
        $conn->query("
            INSERT INTO folder_deadlines (folder_id, start_time, end_time, duration)
            VALUES ($id, " . ($start ? "'$start'" : "NULL") . ", " . ($end ? "'$end'" : "NULL") . ", " . ($duration !== NULL ? $duration : "NULL") . ")
        ");
    }

    exit;
}

    if($_POST['action']=="create"){
        $name = trim($_POST['name']);
        $name = $conn->real_escape_string($name);

        if($name=="") exit;

        if($conn->query("INSERT INTO submission_folders(folder_name,parent_id,teacher_id,section,created_at) 
                 VALUES('$name',NULL,$teacher_id,'$active_section',NOW())")){

            $newFolderId = $conn->insert_id;

            $conn->query("INSERT INTO notifications(section, message, folder_id, created_at, is_read) 
                          VALUES('$active_section','Folder created',$newFolderId,NOW(),0)");

            echo $newFolderId;
        }
        exit;
    }

    if($_POST['action']=="rename"){
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $name = $conn->real_escape_string($name);

        if($name=="") exit;

        $conn->query("UPDATE submission_folders 
                      SET folder_name='$name' 
                      WHERE id=$id AND teacher_id=$teacher_id");
        exit;
    }

    if($_POST['action']=="delete"){
        $id = (int)$_POST['id'];

        $conn->query("DELETE FROM submission_folders 
                      WHERE id=$id AND teacher_id=$teacher_id");
        exit;
    }
}

if(isset($_GET['folder_id'])){
    $folder_id = (int)$_GET['folder_id'];

    $res = $conn->query("SELECT folder_name FROM submission_folders 
                         WHERE id=$folder_id AND teacher_id=$teacher_id");

    if($res && $res->num_rows > 0){
        $row = $res->fetch_assoc();
        $currentFolderName = $row['folder_name'];
    }

$folders = $conn->query("SELECT *, 
    IF(teacher_id != 0, 1, 0) as is_teacher_folder
    FROM submission_folders 
    WHERE parent_id = $folder_id 
    ORDER BY id ASC");

}else{
$folders = $conn->query("SELECT *, 
    IF(teacher_id != 0, 1, 0) as is_teacher_folder
    FROM submission_folders 
    WHERE parent_id IS NULL 
    AND teacher_id = $teacher_id 
    AND section = '$active_section'
    ORDER BY id ASC");
}

$fileCount = 0;

if(isset($_GET['folder_id'])){
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM submission_files
        WHERE folder_id = ? AND is_deleted = 0
    ");
    $stmt->bind_param("i", $folder_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $fileCount = $res['total'];
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../css/teacher_sidebar.css">
<link rel="stylesheet" href="../css/teacher/teacher_submission.css">
<link rel="stylesheet" href="../css/teacher/teacher_submission_modal.css">
<link rel="stylesheet" href="../css/teacher/teacher_submission_menu.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<?php include "../include/teacher_sidebar.php"; ?>

<div class="main-content">

<div class="submission-header">
<button onclick="goBack()" id="backBtn">
<img src="../img/teacher_img/back.png">
</button>

<span id="currentFolderTitle">
<?= $currentFolderName ? htmlspecialchars($currentFolderName) : "Submissions" ?>
</span>

<img src="../img/teacher_img/dot.png" id="dotToggle">

<?php include "submission_modal.php"; ?>
</div>

<div id="sectionPanel">
<div class="section-header">
<span>Your Section:</span>
</div>

<div id="sectionList">
<?php if($sections && $sections->num_rows > 0): ?>
<?php while($sec = $sections->fetch_assoc()): ?>
<?php $isActive = ($sec['section'] === $active_section) ? 'active' : ''; ?>
<div class="section-item <?= $isActive ?>">
<?= htmlspecialchars($sec['section']) ?>
</div>
<?php endwhile; ?>
<?php else: ?>
<div class="section-item">No sections found</div>
<?php endif; ?>
</div>
</div>

<div id="area">

<?php if($folders->num_rows==0 && $fileCount==0){ ?>
<?php if(isset($_GET['folder_id'])){ ?>
<div id="emptyState">
<img src="../img/teacher_img/no_files.png">
<h3>No files submitted yet</h3>
<p>Files will appear here once they have submitted.</p>
</div>
<?php } else { ?>
<div id="emptyState">
<img src="../img/teacher_img/no_folder.png">
<h3>No folders created yet</h3>
<p>Folders will appear here once created.</p>
</div>
<?php } ?>
<?php } ?>

<?php while($row=$folders->fetch_assoc()){ ?>
<div class="folder" data-id="<?=$row['id']?>">
    <img src="../img/teacher_img/folder.png">
    <span><?=htmlspecialchars($row['folder_name'])?></span>

    <?php if($row['is_teacher_folder'] == 1): ?>
        <img src="../img/teacher_img/detail.png" class="detail">
    <?php endif; ?>
</div>
<?php } ?>

<?php
if(isset($_GET['folder_id'])){

$stmt = $conn->prepare("
    SELECT file_name
    FROM submission_files
    WHERE folder_id = ? AND is_deleted = 0
    ORDER BY created_at DESC
");

$stmt->bind_param("i", $folder_id);
$stmt->execute();
$result = $stmt->get_result();

while($row = $result->fetch_assoc()){

    $f = $row['file_name'];

    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    $icon = "../img/teacher_img/files.png";

    if($ext=="pdf") $icon="../img/teacher_img/pdf.png";
    if($ext=="doc" || $ext=="docx") $icon="../img/teacher_img/word.png";
    if($ext=="xls" || $ext=="xlsx") $icon="../img/teacher_img/excel.png";

    $displayName = preg_replace('/^\d+_/', '', $f);

    echo "
    <div class='file-card' data-file='$f'>
        <img src='$icon'>
        <span>".htmlspecialchars($displayName)."</span>
    </div>
    ";
}

}
?>

</div>

<div id="instructionPanel">
<div id="instructionHeader">
<span>Instructions:</span>
<img src="../img/teacher_img/delete.png" id="deleteInstruction">
</div>

<div id="instructionContent" contenteditable="true" data-placeholder="Type your instructions here..."></div>
<button id="saveInstruction">Save</button>
</div>

</div>

<div id="confirmModal" class="modal">
<div class="modal-box">
<h3 id="confirmTitle">Delete this Assignment?</h3>
<p id="confirmMessage">
Are you sure you want to delete this folder?<br>
This action cannot be undone.
</p>
<div class="modal-actions">
<button id="confirmCancel">Cancel</button>
<button id="confirmYes">Confirm</button>
</div>
</div>
</div>

<div id="toast"></div>

</div>

</div>

<script>
    const activeSection = "<?= $active_section ?>";
</script>

<script src="../js/teacher_submission.js"></script>
<script src="../js/teacher_submission_modal.js"></script>
<script src="../js/teacher_instruction.js"></script>

</body>
</html>