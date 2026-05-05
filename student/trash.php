<?php 
session_start();
include "../include/db.php";

if (!isset($_SESSION['student_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

if(isset($_POST['restore_id'])){
    $id = intval($_POST['restore_id']);

    $stmt = $conn->prepare("
        UPDATE submission_files
        SET is_deleted=0, deleted_at=NULL
        WHERE id=? AND student_id=?
    ");
    $stmt->bind_param("is",$id,$student_id);
    $stmt->execute();

    exit();
}

if(isset($_POST['delete_forever'])){
    $id = intval($_POST['delete_forever']);

    $stmt = $conn->prepare("
        SELECT file_path FROM submission_files WHERE id=? AND student_id=?
    ");
    $stmt->bind_param("is",$id,$student_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if($row = $res->fetch_assoc()){
        $full = "../".$row['file_path'];
        if(file_exists($full)){
            unlink($full);
        }
    }

    $del = $conn->prepare("
        DELETE FROM submission_files WHERE id=? AND student_id=?
    ");
    $del->bind_param("is",$id,$student_id);
    $del->execute();

    exit();
}

$stmt = $conn->prepare("
    SELECT id, file_name, file_path, deleted_at
    FROM submission_files
    WHERE student_id=? AND is_deleted=1
    ORDER BY deleted_at DESC
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trash Files</title>
<link rel="stylesheet" href="../mobile_css/student_trash_mobile_view.css">
</head>

<body>

<div class="attendance-header">
<a href="dashboard.php">
<img src="../img/student_img/back.png" class="back-btn">
</a>
<h3>Trash Files</h3>
<div class="header-icons">
    <img src="../img/student_img/dot.png" class="header-dot" id="menuBtn">
</div>
</div>

<div class="menu-panel" id="menuPanel">
    <div class="menu-item" id="restoreBtn">
        <span>Restore</span>
    </div>
    <div class="menu-item delete" id="deleteForeverBtn">
        <span>Delete</span>
    </div>
</div>

<div class="container" id="fileContainer">

<?php if($result->num_rows > 0): ?>

    <?php while($row = $result->fetch_assoc()): 
        $file = $row['file_name'];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        $icon = "../img/student_img/files.png";
        if($ext=="pdf") $icon="../img/student_img/pdf.png";
        if($ext=="doc" || $ext=="docx") $icon="../img/student_img/word.png";
        if($ext=="xls" || $ext=="xlsx") $icon="../img/student_img/excel.png";

        $displayName = preg_replace('/^\d+_/', '', $file);
    ?>

    <div class="file-card" data-id="<?php echo $row['id']; ?>">
        <img src="<?php echo $icon; ?>">
        <span><?php echo $displayName; ?></span>
    </div>

    <?php endwhile; ?>

<?php else: ?>

<div class="trash-wrapper">
<img src="../img/student_img/no_deleted.png">
<h4>No Deleted Files Available</h4>
<p>There are currently no deleted files.</p>
</div>

<?php endif; ?>

</div>

<div class="delete-modal" id="deleteForeverModal">
    <div class="delete-box">

        <div class="delete-folder-name" id="deleteForeverTitle"></div>

        <div class="delete-message" id="deleteForeverMessage">
            Do you want to delete this file permanently?
        </div>

        <div class="delete-actions">
            <span id="cancelDeleteForever">Cancel</span>
            <span id="confirmDeleteForever">Yes</span>
        </div>

    </div>
</div>

<div id="toast" class="toast"></div>

<script>

const menuBtn = document.getElementById("menuBtn");
const menuPanel = document.getElementById("menuPanel");
const restoreBtn = document.getElementById("restoreBtn");
const deleteForeverBtn = document.getElementById("deleteForeverBtn");

menuBtn.onclick = e=>{
    e.stopPropagation();
    menuPanel.style.display =
        menuPanel.style.display === "block" ? "none" : "block";
};

document.addEventListener("click", ()=>{
    menuPanel.style.display = "none";
});

let selectedId = null;

function updateMenuState(){
    const active = selectedId !== null;

    restoreBtn.style.pointerEvents = active ? "auto" : "none";
    deleteForeverBtn.style.pointerEvents = active ? "auto" : "none";

    restoreBtn.style.opacity = active ? "1" : "0.4";
    deleteForeverBtn.style.opacity = active ? "1" : "0.4";
}

updateMenuState();

document.querySelectorAll(".file-card").forEach(card=>{
    card.onclick = e=>{
        e.stopPropagation();

        document.querySelectorAll(".file-card").forEach(x=>x.classList.remove("active"));
        card.classList.add("active");

        selectedId = card.dataset.id;

        updateMenuState();
    };
});

document.addEventListener("click", e=>{

    if(
        !e.target.closest(".file-card") &&
        !e.target.closest("#menuPanel") &&
        !e.target.closest("#deleteForeverModal")
    ){
        document.querySelectorAll(".file-card").forEach(x=>x.classList.remove("active"));

        selectedId = null;

        updateMenuState();
    }

});

function showToast(message, type="success"){
    const toast = document.getElementById("toast");

    toast.innerText = message;
    toast.className = "toast show " + type;

    setTimeout(()=>{
        toast.classList.remove("show");
    }, 2000);
}

function checkEmptyState(){
    const container = document.getElementById("fileContainer");
    const cards = container.querySelectorAll(".file-card");

    if(cards.length === 0){
        container.innerHTML = `
            <div class="trash-wrapper">
                <img src="../img/student_img/no_deleted.png">
                <h4>No Deleted Files Available</h4>
                <p>There are currently no deleted files.</p>
            </div>
        `;
    }
}

restoreBtn.onclick = ()=>{
    if(!selectedId) return;

    const card = document.querySelector(`.file-card[data-id='${selectedId}']`);

    fetch("",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"restore_id="+selectedId
    }).then(()=>{

        if(card){
            card.remove();
        }

        checkEmptyState();

        selectedId = null;
        updateMenuState();

        showToast("Restored successfully", "success");
    });
};

deleteForeverBtn.onclick = ()=>{
    if(!selectedId) return;

    const card = document.querySelector(`.file-card[data-id='${selectedId}']`);
    const name = card.querySelector("span").innerText;

    document.getElementById("deleteForeverTitle").innerText = name;

    document.getElementById("deleteForeverModal").classList.add("show");
    menuPanel.style.display = "none";
};

const deleteForeverModal = document.getElementById("deleteForeverModal");
const cancelDeleteForever = document.getElementById("cancelDeleteForever");
const confirmDeleteForever = document.getElementById("confirmDeleteForever");

cancelDeleteForever.onclick = ()=>{
    deleteForeverModal.classList.remove("show");
};

confirmDeleteForever.onclick = ()=>{

    const card = document.querySelector(`.file-card[data-id='${selectedId}']`);

    fetch("",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"delete_forever="+selectedId
    }).then(()=>{

        deleteForeverModal.classList.remove("show");

        if(card){
            card.remove();
        }

        checkEmptyState();

        selectedId = null;
        updateMenuState();

        showToast("Deleted successfully", "delete");
    });

};

deleteForeverModal.onclick = e=>{
    if(e.target === deleteForeverModal){
        deleteForeverModal.classList.remove("show");
    }
};

</script>

</body>
</html>