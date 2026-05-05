<?php 
include "attendance_data.php";

date_default_timezone_set('Asia/Manila');

// AUTO INSERT ABSENT (ONLY last 5 mins before end)

if(!empty($_SESSION['teacher_subject']) && !empty($_SESSION['teacher_section'])){

    // kunin subject time
    $stmt = $conn->prepare("
        SELECT start_time, end_time 
        FROM teacher_subjects 
        WHERE teacher_id=? AND subject=? AND section=? 
        LIMIT 1
    ");

    $stmt->bind_param(
        "iss",
        $_SESSION['user_id'],
        $_SESSION['teacher_subject'],
        $_SESSION['teacher_section']
    );

    $stmt->execute();
    $res = $stmt->get_result();

    $subjectEnd = '';

    if($row = $res->fetch_assoc()){
        $subjectEnd = $row['end_time'];
    }

    $stmt->close();

    $now = date("H:i:s");

    // 👉 condition: last 5 minutes before end
    if($subjectEnd && strtotime($now) >= strtotime($subjectEnd) - (5 * 60)){

        // kunin course/year/section
        $course='';
        $year='';
        $section='';

        $full = $_SESSION['teacher_section'];
        $parts = explode("-",$full);

        if(count($parts)===2){

            $course = trim($parts[0]);
            $yearSection = trim($parts[1]);

            $yearNumber = preg_replace('/[^0-9]/','',$yearSection);
            $section    = preg_replace('/[^A-Za-z]/','',$yearSection);

            $yearMap = [
                "1"=>"1ST YEAR",
                "2"=>"2ND YEAR",
                "3"=>"3RD YEAR",
                "4"=>"4TH YEAR"
            ];

            $year = $yearMap[$yearNumber] ?? $yearNumber."TH YEAR";
        }

        $stmt = $conn->prepare("
            INSERT INTO attendance (student_id, teacher_subject_id, status, date)
            SELECT 
                s.student_id,
                ts.id,
                'ABSENT',
                CURDATE()
            FROM students s
            JOIN teacher_subjects ts 
                ON ts.teacher_id = ?
                AND ts.subject = ?
                AND ts.section = ?
            WHERE s.course = ?
            AND s.year = ?
            AND s.section = ?
            AND NOT EXISTS (
                SELECT 1 FROM attendance a
                WHERE a.student_id = s.student_id
                AND a.teacher_subject_id = ts.id
                AND a.date = CURDATE()
            )
        ");

        $stmt->bind_param(
            "isssss",
            $_SESSION['user_id'],
            $_SESSION['teacher_subject'],
            $_SESSION['teacher_section'],
            $course,
            $year,
            $section
        );

        $stmt->execute();
        $stmt->close();
    }
}

$conn->query("
UPDATE attendance a
JOIN teacher_subjects ts 
ON a.teacher_subject_id = ts.id
SET a.time_out = CONCAT(a.date,' ',ts.end_time)
WHERE a.time_out IS NULL
AND a.date = CURDATE()
AND NOW() >= TIMESTAMP(a.date, ts.end_time)
AND TIME(NOW()) >= ts.end_time
");
?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Teacher Attendance</title>

<link rel="stylesheet" href="../css/teacher/teacher_attendance.css">
<link rel="stylesheet" href="../css/teacher/teacher_time_modal.css">
<link rel="stylesheet" href="../css/teacher_sidebar.css">
</head>

<body>

<?php include "../include/teacher_sidebar.php"; ?>

<div class="main-content">

<div class="header">

<div class="search-box">
<img src="../img/teacher_img/search.png">
<input type="text" id="searchInput" placeholder="Search student..">
</div>

<div class="top-inputs">

<div class="subject-wrapper" id="subjectWrapper">

<input type="text" id="subjectInput" placeholder="Select Subject" readonly>

<img src="../img/teacher_img/drop_down.png">

<div class="subject-dropdown">

<?php

$stmt = $conn->prepare("
SELECT DISTINCT subject 
FROM teacher_subjects 
WHERE teacher_id=?
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

$res = $stmt->get_result();

while($s = $res->fetch_assoc()):
?>

<div class="subject-item" data-value="<?= htmlspecialchars($s['subject']) ?>">
<?= strtoupper(htmlspecialchars($s['subject'])) ?>
</div>

<?php endwhile; ?>

</div>

</div>

<div class="section-wrapper" id="sectionWrapper">

<input type="text" id="sectionInput" placeholder="Select Section" readonly>

<img src="../img/teacher_img/drop_down.png">

<div class="section-dropdown">

<?php

$stmt = $conn->prepare("
SELECT DISTINCT section
FROM teacher_sections
WHERE teacher_id=?
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

$res = $stmt->get_result();

while($x = $res->fetch_assoc()):
?>

<div class="section-item" data-full="<?= htmlspecialchars($x['section']) ?>">
<?= strtoupper(formatLabel($x['section'])) ?>
</div>

<?php endwhile; ?>

</div>

</div>

</div>

<div class="header-right">

<img src="../img/teacher_img/dot.png" class="dot-menu" id="dotMenu">

<div class="dot-dropdown" id="dotDropdown">

<div class="dot-item <?= empty($attendanceRows) ? 'disabled' : '' ?>" id="downloadReport">
<img src="../img/teacher_img/download.png">
Download
</div>

<div class="dot-item" id="setTimeBtn">
<img src="../img/teacher_img/time.png">
Set Time
</div>

</div>

</div>

</div>

<table class="attendance-table">

<thead>

<tr>
<th>PC NO</th>
<th>STUDENT NAME</th>
<th>COURSE</th>
<th>YEAR</th>
<th>SECTION</th>
<th>COMLAB</th>
<th>PC STATUS</th>
<th>STATUS</th>
<th>TIME IN</th>
<th>TIME OUT</th>
<th>DATE</th>
</tr>

</thead>

<tbody>

<?php if(!empty($attendanceRows)): ?>

<?php foreach($attendanceRows as $r): 

$status = strtoupper($r['status']);

$class = "absent";

if($status === "PRESENT") $class="present";
elseif($status === "LATE") $class="late";

?>

<tr>

<td>
<?= $status === "ABSENT" ? '-' : htmlspecialchars($r['pc_no']) ?>
</td>

<td><?= htmlspecialchars($r['student_name']) ?></td>

<td><?= htmlspecialchars($r['course']) ?></td>

<td><?= htmlspecialchars($r['year']) ?></td>

<td><?= htmlspecialchars($r['section']) ?></td>

<td>
<?= $status === "ABSENT" ? '-' : htmlspecialchars($r['comlab_no']) ?>
</td>

<?php

$pcStatus = strtolower(trim($r['pc_status'] ?? ''));

$pcClass = "";

if($pcStatus === "working") $pcClass = "pc-working";
elseif($pcStatus === "not working") $pcClass = "pc-notworking";
elseif($pcStatus === "defective") $pcClass = "pc-defective";
else $pcClass = "pc-others";

?>

<td>
<?php if($status === "ABSENT"): ?>
    <span class="pc-badge pc-others">-</span>
<?php else: ?>
    <span class="pc-badge <?= $pcClass ?>">
        <?= strtoupper($r['pc_status'] ?? '') ?>
    </span>
<?php endif; ?>
</td>

<td>
<span class="status-badge <?= $class ?>">
<?= $status ?>
</span>
</td>

<td>
<?= $status === "ABSENT" ? '-' : date('h:i A',strtotime($r['time_in'])) ?>
</td>

<td>
<?php if($status === "ABSENT"): ?>
    -
<?php else: ?>
    <?= !empty($r['time_out']) ? date('h:i A',strtotime($r['time_out'])) : '' ?>
<?php endif; ?>
</td>

<td><?= date('M d, Y',strtotime($r['date'])) ?></td>

</tr>

<?php endforeach; ?>
<tr id="noResultRow" style="display:none;">
    <td colspan="11">No student found</td>
</tr>
<?php else: ?>

<tr id="emptyRow">
<td colspan="11">No attendance yet</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>


<?php include "attendance_time_modal.php"; ?>


<script>

function setupDropdown(id){

const wrapper = document.getElementById(id);
const input = wrapper.querySelector("input");
const arrow = wrapper.querySelector("img");

const dropdown =
id === "subjectWrapper"
? wrapper.querySelector(".subject-dropdown")
: wrapper.querySelector(".section-dropdown");

const items =
id === "subjectWrapper"
? wrapper.querySelectorAll(".subject-item")
: wrapper.querySelectorAll(".section-item");


input.onclick = () => {

const open = dropdown.style.display === "block";

dropdown.style.display = open ? "none" : "block";

arrow.style.transform = open
? "translateY(-50%) rotate(0deg)"
: "translateY(-50%) rotate(90deg)";

};


items.forEach(item => {

item.onclick = () => {

input.value = item.textContent;

dropdown.style.display="none";

arrow.style.transform="translateY(-50%) rotate(0deg)";


if(id==="sectionWrapper"){
location.href="attendance.php?section="+encodeURIComponent(item.dataset.full);
}


if(id==="subjectWrapper"){

fetch("set_active_subject.php",{

method:"POST",

headers:{
'Content-Type':'application/x-www-form-urlencoded'
},

body:"subject="+encodeURIComponent(item.dataset.value)

}).then(()=>location.reload());

}

};

});


document.addEventListener("click",e=>{

if(!wrapper.contains(e.target)){

dropdown.style.display="none";
arrow.style.transform="translateY(-50%) rotate(0deg)";

}

});

}


setupDropdown("subjectWrapper");
setupDropdown("sectionWrapper");

const dotMenu = document.getElementById("dotMenu");
const dotDropdown = document.getElementById("dotDropdown");

dotMenu.onclick = () => {

dotDropdown.style.display =
dotDropdown.style.display==="block" ? "none" : "block";

};

document.addEventListener("click",e=>{

if(!dotMenu.contains(e.target) && !dotDropdown.contains(e.target)){
dotDropdown.style.display="none";
}

});

const hasData = <?= !empty($attendanceRows) ? 'true' : 'false' ?>;

const downloadBtn = document.getElementById("downloadReport");

downloadBtn.onclick = () => {

    dotDropdown.style.display = "none";

    if(!hasData){

        const toast = document.createElement("div");
        toast.className = "toast toast-late show";
        toast.innerText = "No data to download";

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 1500);

        return;
    }

    dotDropdown.style.display = "none";

    const loading = document.createElement("div");
    loading.id = "downloadLoading";

    loading.innerHTML = `
        <div class="loading-box">
            <img src="../img/teacher_img/loading.png" class="loading-img">
            <h2>Preparing your Attendance</h2>
            <p>
                Downloading please wait<span class="loading-dots"></span>
            </p>
        </div>
    `;

    document.body.appendChild(loading);

    setTimeout(() => {
        window.location.href = "download_attendance.php";

        loading.remove();

    }, 800);
};

window.onload = () => {

const activeSection="<?= $_SESSION['teacher_section'] ?? '' ?>";
const activeSubject="<?= $_SESSION['teacher_subject'] ?? '' ?>";

if(activeSection !== ""){

document.getElementById("sectionInput").value =
"<?= isset($_SESSION['teacher_section']) && $_SESSION['teacher_section'] ? formatLabel($_SESSION['teacher_section']) : '' ?>";

}

if(activeSubject !== ""){
document.getElementById("subjectInput").value = activeSubject.toUpperCase();
}

};

const searchInput = document.getElementById("searchInput");

searchInput.addEventListener("keyup", function () {

    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll(".attendance-table tbody tr");
    const noResultRow = document.getElementById("noResultRow");

if (!noResultRow) return;

    let hasVisible = false;

rows.forEach(row => {

    if (row.id === "noResultRow" || row.id === "emptyRow") return;

    const text = row.innerText.toLowerCase();

    if (text.includes(filter)) {
        row.style.display = "";
        hasVisible = true;
    } else {
        row.style.display = "none";
    }

});

    noResultRow.style.display = hasVisible ? "none" : "";

});
</script>

</body>
</html>