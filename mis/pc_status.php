<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../include/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['realtime'])) {

    $labName = $_GET['lab'] ?? '';
    $last = intval($_GET['last'] ?? 0);

    set_time_limit(0);

    while (true) {

        $stmt = $conn->prepare("
            SELECT pc_no, pc_status, UNIX_TIMESTAMP(MAX(updated_at)) as ts
            FROM attendance
            WHERE comlab_no = ?
            AND pc_status IS NOT NULL
            GROUP BY pc_no
        ");

        $stmt->bind_param("s", $labName);
        $stmt->execute();
        $res = $stmt->get_result();

        $data = [];
        $latest = $last;

        while ($row = $res->fetch_assoc()) {

            $data[$row['pc_no']] = $row['pc_status'];

            if ($row['ts'] > $latest) {
                $latest = $row['ts'];
            }
        }

        if ($latest > $last) {

            echo json_encode([
                "time" => $latest,
                "status" => $data
            ]);

            exit;
        }

        sleep(1);
    }
}

$selectedLab = isset($_GET['lab']) ? intval($_GET['lab']) : 0;

$pcs = [];
$comlabs = [];

$res = $conn->query("SELECT * FROM comlabs ORDER BY lab_number ASC");

while ($row = $res->fetch_assoc()) {
    $comlabs[] = $row;
}

if ($selectedLab) {

    $labExists = false;

    foreach ($comlabs as $lab) {
        if ($lab['id'] == $selectedLab) {
            $labExists = true;
            break;
        }
    }

    if (!$labExists) {
        $selectedLab = 0;
    }
}

$labName = "";

foreach ($comlabs as $lab) {
    if ($lab['id'] == $selectedLab) {
        $labName = "COMLAB " . $lab['lab_number'];
        break;
    }
}

if ($selectedLab) {

    $stmt = $conn->prepare("SELECT pc_number FROM pcs WHERE comlab_id=? ORDER BY pc_number ASC");
    $stmt->bind_param("i", $selectedLab);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $pcs[] = $row['pc_number'];
    }

    $perPage = 45;
    $totalPCS = count($pcs);
    $totalPages = max(1, ceil($totalPCS / $perPage));

    $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;

    if ($currentPage < 1) $currentPage = 1;
    if ($currentPage > $totalPages) $currentPage = $totalPages;

    $startIndex = ($currentPage - 1) * $perPage;

    $pcs = array_slice($pcs, $startIndex, $perPage);
}

$statuses = [];

if ($selectedLab) {

    $stmt = $conn->prepare("
        SELECT pc_no, pc_status
        FROM attendance
        WHERE comlab_no=?
        AND date=CURDATE()
        AND pc_status IS NOT NULL
        GROUP BY pc_no
    ");

    $stmt->bind_param("s", $labName);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $statuses[$row['pc_no']] = strtolower(trim($row['pc_status']));
    }
}

?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Admin Monitoring Lab</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/mis/mis_pc_status.css">
<link rel="stylesheet" href="../css/mis/mis_password_modal.css">

</head>

<body>

<div class="container">

<?php include "../include/mis_sidebar.php"; ?>

<main class="content">

<div class="monitor-wrapper">

<div class="monitor-top">

<div style="position:relative">

<div class="add-lab" id="comlabBtn">

<span id="comlabText">
<?php
echo $selectedLab ? htmlspecialchars($labName) : "Select Comlab";
?>
</span>

<img id="dropIcon" src="../img/mis_img/drop_down.png">

</div>

<div id="comlabList">

<?php foreach ($comlabs as $lab): ?>

<div class="comlabItem" data-id="<?php echo $lab['id']; ?>">
<span><?php echo htmlspecialchars($lab['lab_name']); ?></span>
</div>

<?php endforeach; ?>

</div>

</div>

</div>

<div class="monitor-content">

<div id="pcContainer" class="<?php echo (empty($pcs) || !$selectedLab) ? 'is-empty' : ''; ?>">

<?php if (!$selectedLab): ?>

<div class="empty-wrapper pc-empty">
<img src="../img/mis_img/no_new_pc.png">
<h3>No pc created yet</h3>
<p>pcs added by the MIS will appear here.</p>
</div>

<?php elseif (empty($pcs)): ?>

<div class="empty-wrapper pc-empty">
<img src="../img/mis_img/no_new_pc.png">
<h3>No pc created yet</h3>
<p>pcs added by the MIS will appear here.</p>
</div>

<?php else: ?>

<?php foreach ($pcs as $pc):

$pcName = "PC " . $pc;
$statusClass = "";

if (isset($statuses[$pcName])) {

    $status = $statuses[$pcName];

    if ($status == "working") $statusClass = "pc-working";
    elseif ($status == "not working") $statusClass = "pc-notworking";
    elseif ($status == "defective") $statusClass = "pc-defective";
    else $statusClass = "pc-others";
}

?>

<div class="pc-box <?php echo $statusClass; ?>" data-pc="<?php echo $pcName; ?>">

<img src="../img/mis_img/pc.png">

<span>PC <?php echo str_pad($pc, 2, "0", STR_PAD_LEFT); ?></span>

</div>

<?php endforeach; ?>

<?php endif; ?>

</div>

<?php if ($selectedLab && $totalPages > 1): ?>
<div class="pagination-controls">

<?php if ($currentPage > 1): ?>
<a href="pc_status.php?lab=<?php echo $selectedLab; ?>&page=<?php echo $currentPage - 1; ?>" class="prev">
    <img src="../img/mis_img/left_arrow.png">
    <span>Previous</span>
</a>
<?php else: ?>
<div class="prev disabled">
    <img src="../img/mis_img/left_arrow.png">
    <span>Previous</span>
</div>
<?php endif; ?>

<span class="page-info">
<?php echo str_pad($currentPage, 2, "0", STR_PAD_LEFT); ?>
</span>

<?php if ($currentPage < $totalPages): ?>
<a href="pc_status.php?lab=<?php echo $selectedLab; ?>&page=<?php echo $currentPage + 1; ?>" class="next">
    <span>Next</span>
    <img src="../img/mis_img/right_arrow.png">
</a>
<?php else: ?>
<div class="next disabled">
    <span>Next</span>
    <img src="../img/mis_img/right_arrow.png">
</div>
<?php endif; ?>

</div>
<?php endif; ?>

<div id="pcDetailsPanel">

<h3 class="details-title">Details:</h3>

<div class="details-row">
<span>Type of issue:</span>
<p id="dType">-</p>
</div>

<div class="details-row">
<span>Description of issue:</span>
<p id="dDesc">-</p>
</div>

<div class="details-row">
<span>Date:</span>
<p id="dDate">-</p>
</div>

<div class="details-row">
<span>Time:</span>
<p id="dTime">-</p>
</div>

<div class="details-row">
<span>Status:</span>
<p id="dStatus">-</p>
</div>

</div>

</div>

</div>

</main>

</div>

<script>

const comlabBtn = document.getElementById("comlabBtn");
const comlabList = document.getElementById("comlabList");
const dropIcon = document.getElementById("dropIcon");

comlabBtn.onclick = function () {

    if (comlabList.style.display === "block") {
        comlabList.style.display = "none";
        dropIcon.style.transform = "rotate(0deg)";
    } else {
        comlabList.style.display = "block";
        dropIcon.style.transform = "rotate(90deg)";
    }
};

document.addEventListener("click", function (e) {

    if (!comlabBtn.contains(e.target)) {
        comlabList.style.display = "none";
        dropIcon.style.transform = "rotate(0deg)";
    }
});

document.querySelectorAll(".comlabItem").forEach(item => {

    item.onclick = function () {

        const labId = this.dataset.id;

        localStorage.setItem("selectedLab", labId);

        window.location = "pc_status.php?lab=" + labId;
    }
});

window.onload = function () {

    const savedLab = localStorage.getItem("selectedLab");

    if (savedLab && !window.location.search.includes("lab=")) {
        window.location = "pc_status.php?lab=" + savedLab;
    }
};

const pcBoxes = document.querySelectorAll(".pc-box");
const panel = document.getElementById("pcDetailsPanel");

let activePC = null;

pcBoxes.forEach(box => {

box.addEventListener("click", function(e){

e.stopPropagation();

if(activePC === this){
panel.style.display="none";
this.classList.remove("pc-selected");
activePC=null;
return;
}

pcBoxes.forEach(pc=>pc.classList.remove("pc-selected"));

this.classList.add("pc-selected");

const rect = this.getBoundingClientRect();

panel.style.top = rect.top + window.scrollY + "px";
panel.style.left = rect.right + 15 + "px";

panel.style.display="block";

activePC=this;

const pcName = this.dataset.pc;

let pcNumber = pcName.replace("PC ","");
pcNumber = parseInt(pcNumber);

const dbPC = "PC-" + pcNumber;

fetch("get_pc_issue.php?pc="+encodeURIComponent(dbPC))

.then(res=>res.json())

.then(data=>{

document.getElementById("dType").innerText = data.type;
document.getElementById("dDesc").innerText = data.desc;
document.getElementById("dDate").innerText = data.date;
document.getElementById("dTime").innerText = data.time;
document.getElementById("dStatus").innerText = data.status;

})

.catch(()=>{

document.getElementById("dType").innerText="-";
document.getElementById("dDesc").innerText="-";
document.getElementById("dTime").innerText="-";
document.getElementById("dStatus").innerText="-";

});

});

});

document.addEventListener("click",function(e){

if(!e.target.closest(".pc-box") && !e.target.closest("#pcDetailsPanel")){

panel.style.display="none";

pcBoxes.forEach(pc=>pc.classList.remove("pc-selected"));

activePC=null;

}

});

let lastUpdate = 0;

function watchPC() {

    fetch(`pc_status.php?realtime=1&lab=<?php echo urlencode($labName); ?>&last=${lastUpdate}`)

        .then(res => res.json())

        .then(data => {

            lastUpdate = data.time;

            const statuses = data.status;

            document.querySelectorAll(".pc-box").forEach(pc => {

                const name = pc.dataset.pc;

                pc.classList.remove(
                    "pc-working",
                    "pc-notworking",
                    "pc-defective",
                    "pc-others"
                );

                if (statuses[name]) {

                    let s = statuses[name].toLowerCase();

                    if (s == "working") pc.classList.add("pc-working");
                    else if (s == "not working") pc.classList.add("pc-notworking");
                    else if (s == "defective") pc.classList.add("pc-defective");
                    else pc.classList.add("pc-others");
                }
            });

            watchPC();
        })

        .catch(() => {
            setTimeout(watchPC, 1000);
        });
}

<?php if ($selectedLab && $totalPCS > 45): ?>
watchPC();
<?php endif; ?>

</script>

</body>
</html>