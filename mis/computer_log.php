<?php
if(session_status() === PHP_SESSION_NONE){
    session_start();
}

include __DIR__ . "/../include/db.php";

$role = $_SESSION['role'];
$targetCheck = ($role === 'mis') ? 'admin' : 'mis';

$hasAccess = true;

if($role === 'admin' && !$hasAccess){
    header("Location: dashboard.php");
    exit;
}

$search = $_GET['search'] ?? '';

function renderRows(){
    global $conn, $search;

    $query = "
        SELECT 
            a.*,
            s.student_name,
            s.course,
            s.year,
            s.section,
            ts.subject,
            u.fullname AS teacher_name
        FROM attendance a
        LEFT JOIN students s ON a.student_id = s.student_id
        LEFT JOIN teacher_subjects ts ON a.teacher_subject_id = ts.id
        LEFT JOIN users u ON ts.teacher_id = u.id
        WHERE 
            s.student_name LIKE '%$search%' OR
            u.fullname LIKE '%$search%' OR
            ts.subject LIKE '%$search%'
        ORDER BY a.id DESC
    ";

    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) > 0){
        while($row = mysqli_fetch_assoc($result)){
            echo '
            <div class="table-row">
                <span>'.htmlspecialchars($row['teacher_name']).'</span>
                <span>'.htmlspecialchars($row['student_name']).'</span>
                <span>'.htmlspecialchars($row['course'].'-'.preg_replace("/[^0-9]/", "", $row['year']).$row['section']).'</span>
                <span>'.htmlspecialchars($row['subject']).'</span>
                <span>'.htmlspecialchars($row['comlab_no']).'</span>
                <span>'.htmlspecialchars($row['pc_no']).'</span>
                <span class="status-'.strtolower(str_replace(' ', '-', $row['pc_status'])).'">
                    '.htmlspecialchars($row['pc_status']).'
                </span>
                <span>'.date("M d, Y", strtotime($row['date'])).'</span>
                <span>'.date("h:i A", strtotime($row['time_in'])).'</span>
                <span>'.(!empty($row['time_out']) ? date("h:i A", strtotime($row['time_out'])) : "--").'</span>
            </div>
            ';
        }
    } else {
        if(!empty($search)){
            $message = "No result found";
        } else {
            $message = "No student scan yet";
        }

        echo '
        <div class="table-row">
            <span class="no-data" style="grid-column: span 10;">
                '.$message.'
            </span>
        </div>';
    }
}

if(isset($_GET['ajax'])){
    renderRows();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Computer Log</title>

<link rel="stylesheet" href="../css/mis_sidebar.css">
<link rel="stylesheet" href="../css/mis/mis_computer_log.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<?php include "../include/mis_sidebar.php"; ?>

<div class="main-content">

<div class="top-bar">

<div class="search-box">
<img src="../img/mis_img/search.png" alt="Search">
<input type="text" name="search" placeholder="Search" value="<?php echo $_GET['search'] ?? ''; ?>">
</div>

</div>

<div class="table-box">

<div class="table-header">
<span>Teacher</span>
<span>Student name</span>
<span>Year & Section</span>
<span>Subject</span>
<span>Com-lab</span>
<span>Pc no</span>
<span>PC Status</span>
<span>Date</span>
<span>Time In</span>
<span>Time Out</span>
</div>

<div class="table-body" id="tableBody">
<?php renderRows(); ?>
</div>

</div>

</div>

<script>
const searchInput = document.querySelector("input[name='search']");
const tableBody = document.getElementById("tableBody");

let timeout = null;

function fetchData(){
    const search = searchInput.value;

    fetch(`?ajax=1&search=${encodeURIComponent(search)}`)
    .then(res => res.text())
    .then(data => {
        tableBody.innerHTML = data;
    });
}

searchInput.addEventListener("keyup", function(){
    clearTimeout(timeout);
    timeout = setTimeout(fetchData, 300);
});
</script>

</body>
</html>