<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../include/db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: ../login.php");
    exit;
}

function getNextNumber($numbers){
    $expected = 1;
    foreach($numbers as $n){
        if($n != $expected){
            break;
        }
        $expected++;
    }
    return $expected;
}

if(isset($_POST['lab'])){
    $lab = trim($_POST['lab']);
    if($lab == "") exit;

    $check = $conn->prepare("SELECT id FROM comlabs WHERE lab_name=?");
    $check->bind_param("s",$lab);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        echo "exists";
        exit;
    }

    $nums = [];
    $res = $conn->query("SELECT lab_number FROM comlabs ORDER BY lab_number ASC");

    while($r = $res->fetch_assoc()){
        $nums[] = (int)$r['lab_number'];
    }

    $labNumber = getNextNumber($nums);

    $stmt = $conn->prepare("INSERT INTO comlabs (lab_name, lab_number) VALUES (?,?)");
    $stmt->bind_param("si",$lab,$labNumber);
    $stmt->execute();

    echo $labNumber;
    exit;
}

if(isset($_POST['delete_lab'])){
    $labName = trim($_POST['delete_lab']);
    if($labName == "") exit;

    $stmt = $conn->prepare("SELECT id FROM comlabs WHERE lab_name=?");
    $stmt->bind_param("s",$labName);
    $stmt->execute();
    $res = $stmt->get_result();

    if($row = $res->fetch_assoc()){
        $labId = $row['id'];

        $stmt = $conn->prepare("DELETE FROM pcs WHERE comlab_id=?");
        $stmt->bind_param("i",$labId);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM comlabs WHERE id=?");
        $stmt->bind_param("i",$labId);
        $stmt->execute();

        $conn->query("ALTER TABLE comlabs AUTO_INCREMENT = 1");
    }
    exit;
}

if(isset($_POST['add_pc'])){
    $lab = intval($_POST['add_pc']);
    $nums = [];

    $stmt = $conn->prepare("SELECT pc_number FROM pcs WHERE comlab_id=? ORDER BY pc_number ASC");
    $stmt->bind_param("i",$lab);
    $stmt->execute();
    $res = $stmt->get_result();

    while($r = $res->fetch_assoc()){
        $nums[] = (int)$r['pc_number'];
    }

    $pcNumber = getNextNumber($nums);

    $stmt = $conn->prepare("INSERT INTO pcs (comlab_id, pc_number) VALUES (?,?)");
    $stmt->bind_param("ii",$lab,$pcNumber);
    $stmt->execute();

    echo $pcNumber;
    exit;
}

if(isset($_POST['restore_pc'])){
    $lab = intval($_POST['restore_pc']);
    $pc  = intval($_POST['pc_number']);

    $check = $conn->prepare("SELECT id FROM pcs WHERE comlab_id=? AND pc_number=?");
    $check->bind_param("ii",$lab,$pc);
    $check->execute();
    $check->store_result();

    if($check->num_rows == 0){
        $stmt = $conn->prepare("INSERT INTO pcs (comlab_id, pc_number) VALUES (?,?)");
        $stmt->bind_param("ii",$lab,$pc);
        $stmt->execute();
    }

    echo $pc;
    exit;
}

if(isset($_POST['load_pc'])){
    $lab = intval($_POST['load_pc']);

    $limit = 45;
    $page  = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    if($page < 1) $page = 1;

    $offset = ($page - 1) * $limit;

    $pcs = [];

    $stmt = $conn->prepare("
        SELECT pc_number 
        FROM pcs 
        WHERE comlab_id=? 
        ORDER BY pc_number ASC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii",$lab,$limit,$offset);
    $stmt->execute();
    $res = $stmt->get_result();

    while($r = $res->fetch_assoc()){
        $pcs[] = $r['pc_number'];
    }

    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM pcs WHERE comlab_id=?");
    $countStmt->bind_param("i",$lab);
    $countStmt->execute();
    $totalRes = $countStmt->get_result()->fetch_assoc();

    echo json_encode([
        "pcs" => $pcs,
        "total" => $totalRes['total']
    ]);
    exit;
}

if(isset($_POST['delete_pc'])){
    $lab = intval($_POST['delete_pc']);
    $pc  = intval($_POST['pc_number']);

    $stmt = $conn->prepare("DELETE FROM pcs WHERE comlab_id=? AND pc_number=?");
    $stmt->bind_param("ii",$lab,$pc);
    $stmt->execute();

    if($stmt->affected_rows > 0){
        echo "PC ".str_pad($pc,2,"0",STR_PAD_LEFT)." has been deleted.";
    } else {
        echo "PC deletion failed.";
    }

    exit;
}

if(isset($_GET['download_qr_all'])){

    include "../phpqrcode/qrlib.php";

    $lab = intval($_GET['download_qr_all']);
    $pcs = [];

    $stmt = $conn->prepare("SELECT pc_number FROM pcs WHERE comlab_id=? ORDER BY pc_number ASC");
    $stmt->bind_param("i",$lab);
    $stmt->execute();
    $res = $stmt->get_result();

    while($r = $res->fetch_assoc()){
        $pcs[] = $r['pc_number'];
    }

    $zip = new ZipArchive();

    $zipName = "COMLAB-".$lab."-ALL.zip";
    $zipPath = sys_get_temp_dir()."/".$zipName;

    if($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE)){
foreach($pcs as $pc){

    $data = "COMLAB-$lab-PC-$pc";

    ob_start();
    QRcode::png($data, null, QR_ECLEVEL_L, 5);
    $imageString = ob_get_contents();
    ob_end_clean();

    $zip->addFromString(
        "PC_".str_pad($pc,2,"0",STR_PAD_LEFT).".png",
        $imageString
    );
}

        $zip->close();

        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=$zipName");
        header("Content-Length: ".filesize($zipPath));

        readfile($zipPath);
        unlink($zipPath);
    }

    exit;
}

$comlabs = [];

$res = $conn->query("SELECT * FROM comlabs ORDER BY lab_number ASC");

while($row = $res->fetch_assoc()){
    $comlabs[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MIS Monitor Lab</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/mis_sidebar.css">
<link rel="stylesheet" href="../css/mis/mis_monitor.css">
<link rel="stylesheet" href="../css/mis/mis_monitor_modals.css">
</head>

<body>

<?php include "../include/mis_sidebar.php"; ?>

<div class="monitor-wrapper">

<div class="monitor-top">
<div class="monitor-icons">
<button id="qrBtn"><img src="../img/mis_img/qr.png"></button>
<button id="addPcBtn"><img src="../img/mis_img/add_pc.png"></button>
<button id="deletePcBtn" disabled><img src="../img/mis_img/delete.png"></button>
</div>

<div style="position:relative">
<div class="add-lab" id="comlabBtn">
<span id="comlabText">Select Comlab</span>
<img id="dropIcon" src="../img/mis_img/drop_down.png">
</div>

<div id="comlabList">
<img id="addComlabBtn" src="../img/mis_img/add_comlab.png">
<input type="text" id="comlabInput" placeholder="Comlab name" style="display:none">

<?php foreach($comlabs as $lab): ?>
<div class="comlabItem"
data-number="<?php echo $lab['lab_number']; ?>"
data-id="<?php echo $lab['id']; ?>">
<span><?php echo htmlspecialchars($lab['lab_name']); ?></span>
<img class="removeComlab" src="../img/mis_img/remove.png">
</div>
<?php endforeach; ?>

</div>
</div>
</div>

<div class="monitor-content">
<div id="pcContainer">
<div class="empty-wrapper pc-empty">
<img src="../img/mis_img/no_new_pc.png">
<h3>No pc created yet</h3>
<p>Newly added PCs will be displayed here.</p>
</div>
</div>

<div class="pagination" style="display:none;">
<button id="prevBtn">
    <img src="../img/mis_img/left_arrow.png">
    <span>Previous</span>
</button>

<span id="pageIndicator"></span>

<button id="nextBtn">
    <span>Next</span>
    <img src="../img/mis_img/right_arrow.png">
</button>
</div>

</div>

</div>

<div class="qr-modal" style="display:none">
<div class="qr-box">
<div class="qr-dropdown">
    <div class="qr-dropdown-selected" id="qrSelected">
        Select PC
        <span class="arrow"></span>
    </div>

<div class="qr-dropdown-menu" id="qrDropdownMenu">
    <div class="qr-option" data-value="all">All PCs</div>
    <div class="qr-option" data-value="0">No PC</div>
    <!-- JS will append PC list here -->
</div>
</div>
<div class="qr-preview"></div>
<button class="qr-generate" disabled>Generate</button>
</div>
</div>

<div class="delete-modal" id="comlabDeleteModal" style="display:none;">
    <div class="delete-box">
        <div class="delete-content">
            <div class="delete-title" id="deleteTitle">Delete Comlab?</div>
            <div class="delete-message">Are you sure you want to delete this?</div>
        </div>
        <div class="delete-footer">
            <button class="delete-cancel" id="deleteCancel">Cancel</button>
            <button class="delete-confirm" id="deleteConfirm">Yes</button>
        </div>
    </div>
</div>

<script src="../js/mis_pc_manager.js"></script>
<script src="../js/mis_qr_generator.js"></script>
<script src="../js/mis_comlab.js"></script>

</body>
</html>