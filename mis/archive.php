<?php
require_once "student_actions.php";
require_once "../include/db.php";

$limit = 10;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
$page = 1;
}

$start = ($page - 1) * $limit;

$archived = $conn->query("
SELECT * FROM students
WHERE status='archived'
ORDER BY id DESC
LIMIT $start,$limit
");

$total_result = $conn->query("
SELECT COUNT(*) as total
FROM students
WHERE status='archived'
");

$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];

$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<title>Archived Students</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/mis/mis_archive.css">

</head>

<body>

<?php if(isset($_GET['restored'])): ?>
<div class="toast success" id="toastMsg">Student restored successfully</div>
<?php endif; ?>

<?php if(isset($_GET['deleted'])): ?>
<div class="toast delete" id="toastMsg">Student deleted permanently</div>
<?php endif; ?>

<div class="layout archive-page">

<?php include "../include/mis_sidebar.php"; ?>

<div class="main-content">

<div class="archive-header">

<h2>Archived Students</h2>

<input
type="text"
class="archive-search"
id="archiveSearch"
placeholder="Search..."
>

</div>

<div class="archive-table-wrapper">

<table class="archive-student-table">

<thead>
<tr>
<th>STUDENT</th>
<th>STUDENT ID</th>
<th>COURSE</th>
<th>YEAR</th>
<th>SECTION</th>
<th>GENDER</th>
<th>SEMESTER</th>
<th>EMAIL ADDRESS</th>
<th>ACTION</th>
</tr>
</thead>

<tbody id="archiveBody">

<tr id="noSearchResult" style="display:none;">
<td colspan="9" class="archive-empty-row">
No student found
</td>
</tr>

<?php if($archived->num_rows > 0): ?>
<?php while($row = $archived->fetch_assoc()): ?>

<tr>

<td><?= htmlspecialchars($row['student_name']) ?></td>
<td><?= htmlspecialchars($row['student_id']) ?></td>
<td><?= htmlspecialchars($row['course']) ?></td>
<td><?= htmlspecialchars($row['year']) ?></td>
<td><?= htmlspecialchars($row['section']) ?></td>
<td><?= htmlspecialchars($row['gender']) ?></td>
<td><?= htmlspecialchars($row['semester']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>

<td class="action-cell">

<a href="student_actions.php?restore=<?= $row['id'] ?>">
<img src="../img/mis_img/restore.png">
</a>

<a
href="#"
class="deleteBtn"
data-id="<?= $row['id'] ?>"
data-name="<?= htmlspecialchars($row['student_name']) ?>"
>
<img src="../img/mis_img/delete.png">
</a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="9" class="archive-empty-row">
No archived students
</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

<?php
$noData = ($archived->num_rows == 0);
$disablePrev = ($page <= 1 || $noData);
$disableNext = ($page >= $total_pages || $noData);
?>

<div class="pagination">

<a
class="page-btn prev <?= $disablePrev ? 'disabled' : '' ?>"
href="<?= $disablePrev ? '#' : '?page='.max(1,$page-1) ?>"
>

<img src="../img/mis_img/left_arrow.png">
Previous

</a>

<div class="page-numbers">

<?php if(!$noData): ?>

<?php

$start = max(1, $page - 2);
$end = min($total_pages, $page + 2);

for($i = $start; $i <= $end; $i++){
echo '<a href="?page='.$i.'" class="page-number '.($page==$i?'active':'').'">'.str_pad($i,2,"0",STR_PAD_LEFT).'</a>';
}

?>

<?php endif; ?>

</div>

<a
class="page-btn next <?= $disableNext ? 'disabled' : '' ?>"
href="<?= $disableNext ? '#' : '?page='.min($total_pages,$page+1) ?>"
>

Next
<img src="../img/mis_img/right_arrow.png">

</a>

</div>

</div>
</div>

<div id="deleteModal" class="archive-modal">

<div class="archive-modal-box">

<h3 id="deleteTitle"></h3>

<p>Are you sure you want to delete this permanently?</p>

<div class="archive-modal-actions">

<button id="cancelDelete">Cancel</button>

<a id="confirmDelete" class="delete-confirm-btn">Yes</a>

</div>

</div>

</div>

<script>

const toast = document.getElementById("toastMsg");

if (toast) {

    setTimeout(() => {
        toast.classList.add("show");
    }, 100);

    setTimeout(() => {
        toast.classList.remove("show");
    }, 3000);

    // ✅ ADD THIS (FIX SA REFRESH)
    if(window.history.replaceState){
        const cleanURL = window.location.pathname;
        window.history.replaceState({}, document.title, cleanURL);
    }

}

document.getElementById("archiveSearch").addEventListener("keyup", function(){

let filter = this.value.toLowerCase();
let rows = document.querySelectorAll("#archiveBody tr:not(#noSearchResult)");
let found = false;

rows.forEach(row => {

if(row.textContent.toLowerCase().includes(filter)){
row.style.display = "";
found = true;
}else{
row.style.display = "none";
}

});

document.getElementById("noSearchResult").style.display = found ? "none" : "";

});

const deleteModal = document.getElementById("deleteModal");
const deleteTitle = document.getElementById("deleteTitle");
const confirmDelete = document.getElementById("confirmDelete");
const cancelDelete = document.getElementById("cancelDelete");

document.querySelectorAll(".deleteBtn").forEach(btn => {

btn.addEventListener("click", function(){

const id = this.dataset.id;
const name = this.dataset.name;

deleteTitle.textContent = "Delete " + name + "?";

confirmDelete.href = "student_actions.php?delete=" + id;

deleteModal.style.display = "flex";

});

});

cancelDelete.onclick = () => {
deleteModal.style.display = "none";
};

window.onclick = function(e){
if (e.target == deleteModal) {
deleteModal.style.display = "none";
}
};

</script>

</body>
</html>