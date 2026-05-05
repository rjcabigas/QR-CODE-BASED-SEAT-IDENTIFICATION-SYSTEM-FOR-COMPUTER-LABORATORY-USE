<?php
require_once "student_actions.php";
require_once "student_data.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if(isset($_GET['download_format'])){

    require '../vendor/autoload.php';

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $headers = [
        'A1' => 'STUDENT NAME:',
        'B1' => 'STUDENT ID:',
        'C1' => 'COURSE:',
        'D1' => 'YEAR:',
        'E1' => 'SECTION:',
        'F1' => 'GENDER:',
        'G1' => 'SEMESTER:',
        'H1' => 'EMAIL ADDRESS:'
    ];

    foreach ($headers as $cell => $text) {
        $sheet->setCellValue($cell, $text);
    }

    $sheet->getStyle('A1:H1')->getFont()->setBold(true);

    foreach(range('A','H') as $col){
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    for($i = 2; $i <= 20; $i++){
        $sheet->setCellValue("A$i", '');
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="student_format.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage All Student</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/mis/mis_student_management.css">
<link rel="stylesheet" href="../css/mis/mis_student_modal.css">
<link rel="stylesheet" href="../css/mis/mis_password_modal.css">
</head>

<body>

<div class="layout">

<?php include "../include/mis_sidebar.php"; ?>

<div class="main-content">

<?php if(isset($_GET['saved'])): ?>
<div id="updateMsg" class="success-msg">Added Successfully</div>
<?php endif; ?>

<?php if(isset($_GET['updated'])): ?>
<div id="updateMsg" class="success-msg">Updated Successfully</div>
<?php endif; ?>

<?php if(isset($_GET['deleted']) || isset($_GET['archived'])): ?>
<div id="toastArchive" class="toast success">
Student moved to archive successfully
</div>
<?php endif; ?>

<div class="top-title">
<h2>Manage Students</h2>

<div class="search-box">
<input type="text" id="studentSearch" placeholder="Search...">
</div>
</div>

<form method="POST" id="studentForm">
<input type="hidden" name="archive_selected" id="archiveSelectedInput">

<div class="action-bar">

<button class="add-btn" id="openModal" type="button">
<img src="../img/mis_img/add.png">
ADD STUDENTS
</button>

<div class="filter-box custom-filter">

<span class="filter-title">MODIFIED</span>
<img src="../img/mis_img/drop_down.png">

<ul class="filter-menu">

<li class="has-sub">
By Semester <img src="../img/mis_img/right_arrow.png">
<ul class="sub-menu">
<li onclick="goFilter('semester','1ST SEMESTER')">1st Semester</li>
<li onclick="goFilter('semester','2ND SEMESTER')">2nd Semester</li>
</ul>
</li>

<li class="has-sub">
By Year <img src="../img/mis_img/right_arrow.png">
<ul class="sub-menu">
<li onclick="goFilter('year','1ST YEAR')">1st Year</li>
<li onclick="goFilter('year','2ND YEAR')">2nd Year</li>
<li onclick="goFilter('year','3RD YEAR')">3rd Year</li>
<li onclick="goFilter('year','4TH YEAR')">4th Year</li>
</ul>
</li>

<li class="has-sub">
By Section <img src="../img/mis_img/right_arrow.png">
<ul class="sub-menu">

<?php
$sec = $conn->query("SELECT DISTINCT section FROM students WHERE status='active'");
while($s = $sec->fetch_assoc()):
?>

<li onclick="goFilter('section','<?= htmlspecialchars($s['section']) ?>')">
<?= htmlspecialchars($s['section']) ?>
</li>

<?php endwhile; ?>

</ul>
</li>

<li class="has-sub">
By Course <img src="../img/mis_img/right_arrow.png">
<ul class="sub-menu">

<?php
$cor = $conn->query("SELECT DISTINCT course FROM students WHERE status='active'");
while($c = $cor->fetch_assoc()):
?>

<li onclick="goFilter('course','<?= htmlspecialchars($c['course']) ?>')">
<?= htmlspecialchars($c['course']) ?>
</li>

<?php endwhile; ?>

</ul>
</li>

</ul>

</div>

<button class="studentlist-btn" type="button">STUDENT LIST</button>

<div class="action-icons">
    <img src="../img/mis_img/delete_all.png" class="delete-all-btn" id="deleteSelected">
<div class="tooltip">
    <img src="../img/mis_img/download_format.png"
         class="download-btn"
         id="downloadFormat">
    <span class="tooltip-text">Download Format</span>
</div>

</div>

<a href="archive.php" class="archive-btn">
<span>STUDENT ARCHIVE</span>
</a>

</div>

<div class="table-wrapper <?= ($students->num_rows == 0) ? 'empty-table' : '' ?>">

<table class="student-table">

<thead>
<tr>
<th><input type="checkbox" id="selectAll"></th>
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

<tbody>

<?php if($students->num_rows > 0): ?>
<?php while($row = $students->fetch_assoc()): ?>

<tr>

<td>
<input type="checkbox" class="student-checkbox" name="selected_students[]" value="<?= $row['id'] ?>">
</td>

<td><?= htmlspecialchars($row['student_name']) ?></td>
<td><?= htmlspecialchars($row['student_id']) ?></td>
<td><?= htmlspecialchars($row['course']) ?></td>
<td><?= htmlspecialchars($row['year']) ?></td>
<td><?= htmlspecialchars($row['section']) ?></td>
<td><?= htmlspecialchars($row['gender']) ?></td>
<td><?= htmlspecialchars($row['semester']) ?></td>
<td><?= htmlspecialchars($row['email']) ?></td>

<td class="action-cell">

<a href="?edit=<?= $row['id'] ?>">
<img src="../img/mis_img/update_info.png">
</a>

<img src="../img/mis_img/delete.png"
onclick="openDelete(
<?= (int)$row['id'] ?>,
'<?= htmlspecialchars($row['student_name'], ENT_QUOTES) ?>'
)">

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr class="empty-row">
<td colspan="10">No student added yet</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</form>

<?php
$noData = ($students->num_rows == 0);
$disablePrev = ($page <= 1 || $noData);
$disableNext = ($page >= $total_pages || $noData);
?>

<div class="pagination">

<a class="page-btn prev <?= $disablePrev ? 'disabled' : '' ?>"
href="<?= $disablePrev ? '#' : '?page='.max(1,$page-1) ?>">

<img src="../img/mis_img/left_arrow.png">
Previous

</a>

<div class="page-numbers">

<?php if(!$noData): ?>

<?php

$start = max(1, $page - 2);
$end = min($total_pages, $page + 2);

if($start > 1){
echo '<a href="?page=1" class="page-number">01</a>';
if($start > 2){
echo '<span class="dots">...</span>';
}
}

for($i = $start; $i <= $end; $i++){
echo '<a href="?page='.$i.'" class="page-number '.($page==$i?'active':'').'">'.str_pad($i,2,"0",STR_PAD_LEFT).'</a>';
}

if($end < $total_pages){
if($end < $total_pages - 1){
echo '<span class="dots">...</span>';
}
echo '<a href="?page='.$total_pages.'" class="page-number">'.str_pad($total_pages,2,"0",STR_PAD_LEFT).'</a>';
}

?>

<?php endif; ?>

</div>

<a class="page-btn next <?= $disableNext ? 'disabled' : '' ?>"
href="<?= $disableNext ? '#' : '?page='.min($total_pages,$page+1) ?>">

Next
<img src="../img/mis_img/right_arrow.png">

</a>

</div>

</div>

<?php include "student_modal.php"; ?>

<script>
window.addEventListener("load", () => {

    const toastArchive = document.getElementById("toastArchive");

    if(toastArchive){
        toastArchive.classList.add("show");

        setTimeout(()=>{
            toastArchive.classList.remove("show");
        },3000);
    }

});
</script>

<script src="../js/admin_manage_student.js"></script>
<script src="../js/admin_modal.js"></script>

</body>
</html>