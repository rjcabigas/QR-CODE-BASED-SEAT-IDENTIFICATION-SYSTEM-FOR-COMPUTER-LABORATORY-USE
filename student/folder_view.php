<?php
session_start();
include("../include/db.php");

if(isset($_POST['create_folder'])){
    $name = trim($_POST['folder_name']);
    $parent = intval($_POST['parent_id']);
    $student_id = $_SESSION['student_id'];

    if($name != ""){
        $safe = preg_replace("/[^a-zA-Z0-9 _-]/","",$name);

        $stmt = $conn->prepare("
            INSERT INTO submission_folders
            (folder_name, parent_id, student_id, created_at)
            VALUES (?, ?, ?, NOW())
        ");

        $stmt->bind_param("sii", $safe, $parent, $student_id);
        $stmt->execute();
    }

    exit();
}

if(isset($_POST['delete_file'])){
    $file = basename($_POST['delete_file']);
    $folder = intval($_POST['folder_id']);

$stmt = $conn->prepare("
    UPDATE submission_files
    SET is_deleted=1, deleted_at=NOW()
    WHERE folder_id=? AND file_name=? AND student_id=?
");
$stmt->bind_param("iss", $folder, $file, $_SESSION['student_id']);
    $stmt->execute();

    exit();
}

if(isset($_POST['rename_file'])){
    $old = basename($_POST['old_name']);
    $new = basename($_POST['new_name']);
    $folder = intval($_POST['folder_id']);

    $extOld = pathinfo($old, PATHINFO_EXTENSION);
    $extNew = pathinfo($new, PATHINFO_EXTENSION);

    if($extNew==""){
        $new .= ".".$extOld;
    }

    $new = preg_replace("/[^a-zA-Z0-9._-]/","",$new);

    $oldPath = "../uploads/".$folder."/".$old;
    $newPath = "../uploads/".$folder."/".$new;

    if(file_exists($oldPath)){
        rename($oldPath,$newPath);
    }

    exit();
}

if(isset($_FILES['files'])){
    if(!isset($_SESSION['student_id'])){
        exit();
    }

    $student_id = $_SESSION['student_id'];
    $folder = intval($_POST['folder_id']);
    $targetDir = "../uploads/".$folder."/";

    if(!is_dir($targetDir)){
        mkdir($targetDir,0777,true);
    }

    $allowed = ['pdf','doc','docx','xls','xlsx'];
    $maxSize = 10 * 1024 * 1024;

    foreach($_FILES['files']['name'] as $i=>$name){
        $tmp = $_FILES['files']['tmp_name'][$i];
        $size = $_FILES['files']['size'][$i];

        if($size > $maxSize){
            continue;
        }

        $ext = strtolower(pathinfo($name,PATHINFO_EXTENSION));

        if(!in_array($ext,$allowed)){
            continue;
        }

        $cleanName = preg_replace("/[^a-zA-Z0-9._-]/","",$name);
        $safeName = time()."_".$cleanName;
        $target = $targetDir.$safeName;

        if(move_uploaded_file($tmp,$target)){
            $file_path = "uploads/".$folder."/".$safeName;

            $stmt = $conn->prepare("
                INSERT INTO submission_files
                (folder_id, student_id, file_name, file_path, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->bind_param("iiss",
                $folder,
                $student_id,
                $safeName,
                $file_path
            );

            $stmt->execute();
        }
    }

    exit();
}

if(isset($_POST['delete_id'])){
    $delete_id = intval($_POST['delete_id']);

    // 1. Delete files inside folder (DB + actual file)
    $getFiles = $conn->prepare("
        SELECT file_name 
        FROM submission_files 
        WHERE folder_id=?
    ");
    $getFiles->bind_param("i",$delete_id);
    $getFiles->execute();
    $resFiles = $getFiles->get_result();

    while($f = $resFiles->fetch_assoc()){
        $filePath = "../uploads/".$delete_id."/".$f['file_name'];
        if(file_exists($filePath)){
            unlink($filePath);
        }
    }

    // 2. Delete files record in DB
    $delFiles = $conn->prepare("
        DELETE FROM submission_files 
        WHERE folder_id=?
    ");
    $delFiles->bind_param("i",$delete_id);
    $delFiles->execute();

    // 3. Delete folder record
    $stmt = $conn->prepare("
        DELETE FROM submission_folders 
        WHERE id=?
    ");
    $stmt->bind_param("i",$delete_id);
    $stmt->execute();

function deleteFolder($dir){
    if(!is_dir($dir)) return;

    $files = scandir($dir);

    foreach($files as $file){
        if($file != "." && $file != ".."){
            $full = $dir . "/" . $file;

            if(is_dir($full)){
                deleteFolder($full);
            }else{
                unlink($full);
            }
        }
    }

    rmdir($dir);
}

$dir = "../uploads/".$delete_id;
deleteFolder($dir);

    exit();
}

if(isset($_POST['rename_id'])){
    $id = intval($_POST['rename_id']);
    $name = trim($_POST['rename_name']);

    if($name!=""){
        $safe = preg_replace("/[^a-zA-Z0-9 _-]/","",$name);

        $stmt = $conn->prepare("
            UPDATE submission_folders
            SET folder_name=?
            WHERE id=?
        ");

        $stmt->bind_param("si",$safe,$id);
        $stmt->execute();
    }

    exit();
}

if(!isset($_GET['folder_id'])){
    header("Location: submit_file_mobile.php");
    exit();
}

$folder_id = intval($_GET['folder_id']);
$folder_name = "Folder";

$student_id = $_SESSION['student_id'];

$getStudent = $conn->prepare("
    SELECT student_name 
    FROM students 
    WHERE student_id=?
");
$getStudent->bind_param("s", $student_id);
$getStudent->execute();
$resStudent = $getStudent->get_result();

$student_name = "Student";

if($row = $resStudent->fetch_assoc()){

    $full = $row['student_name'];

    $parts = explode(" ", $full);

    $last = end($parts);
    $first = $parts[0];

    $last = ucfirst(strtolower($last));
    $first = ucfirst(strtolower($first));

    $student_name = $last . "_" . $first;
}

$stmt = $conn->prepare("
SELECT folder_name,parent_id
FROM submission_folders
WHERE id=?
");

$stmt->bind_param("i",$folder_id);
$stmt->execute();
$res = $stmt->get_result();

$parent_id = 0;

if($row = $res->fetch_assoc()){
    $folder_name = htmlspecialchars($row['folder_name']);
    $parent_id = intval($row['parent_id']);
}

$subs = $conn->prepare("
SELECT id,folder_name
FROM submission_folders
WHERE parent_id=?
ORDER BY id DESC
");

$subs->bind_param("i",$folder_id);
$subs->execute();
$subs = $subs->get_result();

$hasContent = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $folder_name; ?></title>

<link rel="stylesheet" href="../mobile_css/student_folder_mobile_view.css">
<link rel="stylesheet" href="../mobile_css/student_folder_upload.css">

</head>

<body>

<div class="submission-header">
    <img src="../img/student_img/back.png" class="back-btn" id="backFolder">
    <h3><?php echo $folder_name; ?></h3>
    <img src="../img/student_img/dot.png" class="header-dot" id="menuBtn">
</div>

<div class="menu-panel" id="menuPanel">

    <div class="menu-item" id="createBtn">
        <img src="../img/student_img/create_folder.png">
        <span>Create Folder</span>
    </div>

    <div class="menu-item" id="uploadBtn">
        <img src="../img/student_img/upload.png">
        <span>Upload</span>
    </div>

    <div class="menu-item" id="renameBtn">
        <img src="../img/student_img/rename.png">
        <span>Rename</span>
    </div>

    <div class="menu-item delete" id="deleteBtn">
        <img src="../img/student_img/delete.png">
        <span>Delete</span>
    </div>

</div>

<div class="submission-content">

<?php while($r=mysqli_fetch_assoc($subs)): 
$hasContent = true;
?>
<div class="folder"
     data-id="<?php echo $r['id']; ?>"
     data-name="<?php echo htmlspecialchars($r['folder_name']); ?>">

    <img src="../img/student_img/folder.png">
    <span><?php echo htmlspecialchars($r['folder_name']); ?></span>

</div>

<?php endwhile; ?>

<?php
$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("
    SELECT file_name, file_path
    FROM submission_files
    WHERE folder_id = ? AND student_id = ? AND is_deleted=0
    ORDER BY created_at DESC
");

$stmt->bind_param("is", $folder_id, $student_id);
$stmt->execute();

$result = $stmt->get_result();

while($row = $result->fetch_assoc()){

    $hasContent = true;

    $file = $row['file_name'];
    $path = "../" . $row['file_path'];

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $icon = "../img/student_img/files.png";

    if($ext=="pdf") $icon="../img/student_img/pdf.png";
    if($ext=="doc" || $ext=="docx") $icon="../img/student_img/word.png";
    if($ext=="xls" || $ext=="xlsx") $icon="../img/student_img/excel.png";

    $displayName = preg_replace('/^\d+_/', '', $file);

    echo "
    <div class='file-card' data-file='$file'>
        <img src='$icon'>
        <span>$displayName</span>
    </div>
    ";
}
?>

<?php if(!$hasContent): ?>

<div id="emptyState">
    <img src="../img/student_img/no_files.png">
    <h3>No files submitted yet</h3>
    <p>Your files will appear here once you've submitted.</p>
</div>

<?php endif; ?>

</div>

<div class="modal" id="modal">
    <div class="modal-box">
        <input type="text" id="subName" placeholder="Enter folder name">
        <button id="saveSub">Create</button>
    </div>
</div>

<div class="delete-modal" id="deleteModal">
    <div class="delete-box">

        <div class="delete-folder-name" id="deleteTitle"></div>

        <div class="delete-message">
            Do you want to delete this folder?
        </div>

        <div class="delete-actions">
            <span id="cancelDelete">Cancel</span>
            <span id="confirmDelete">Yes</span>
        </div>

    </div>
</div>

<div class="upload-panel" id="uploadPanel">
    <div class="upload-box">

        <h3>Upload Files</h3>

        <div class="upload-drop" id="dropZone">

<input type="file" id="fileInput" name="files[]" hidden multiple accept=".pdf,.doc,.docx,.xls,.xlsx">

            <img src="../img/student_img/files.png" class="upload-icon">

            <p>Drop file here or browse</p>

            <span class="file-types">
                <span class="pdf">PDF</span>,
                <span class="word">WORD</span>,
                <span class="excel">EXCEL</span>
            </span>

        </div>

        <div class="upload-file-name" id="noFile">NO CHOSEN FILE...</div>

        <div class="file-scroll">
            <div id="fileList"></div>
        </div>

        <button class="upload-btn" id="uploadNow" disabled>Upload</button>

    </div>
</div>

<script>
const PARENT_ID = <?php echo $parent_id ? $parent_id : 0; ?>;
const FOLDER_ID = <?php echo $folder_id ?>;
const STUDENT_NAME = "<?php echo addslashes($student_name); ?>";
</script>

<script src="../js/student_folder.js"></script>

</body>
</html>