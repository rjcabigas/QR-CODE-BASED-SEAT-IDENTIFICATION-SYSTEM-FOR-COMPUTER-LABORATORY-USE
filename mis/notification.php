<?php
session_start();
include "../include/db.php";

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['mis','admin'])) {
    header("Location: ../auth/login.php");
    exit();
}

if(isset($_POST['action_type'])){

    $id = intval($_POST['id']);
    $action = $_POST['action_type'];

    $actions = [

        "approve"  => "UPDATE maintenance SET admin_action='approved' WHERE id=$id",

        "reject"   => "UPDATE maintenance SET admin_action='rejected' WHERE id=$id",

        "resolved" => "UPDATE maintenance SET resolved='yes' WHERE id=$id"

    ];

    if(isset($actions[$action])){
        $conn->query($actions[$action]);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>

<title>MIS Notification</title>

<link rel="stylesheet" href="../css/mis_sidebar.css">
<link rel="stylesheet" href="../css/mis/mis_notification.css">

</head>

<body>

<?php include "../include/mis_sidebar.php"; ?>

<div class="main-content">
<div class="notification-wrapper">

<table class="notif-table">

<thead>
<tr>
<th>PC NO</th>
<th>COMLAB</th>
<th>TYPE</th>
<th>DESCRIPTION</th>
<th>DATE</th>
<th>TIME</th>
<th>STATUS</th>
<th></th>
</tr>
</thead>

<tbody>

<?php
$q = $conn->query("
SELECT * FROM maintenance 
WHERE teacher_action='approved' 
ORDER BY approved_at DESC
");

if ($q->num_rows == 0):
?>

<tr>
<td colspan="8" style="text-align:center; padding:20px; font-weight:600; color:#777;">
No maintenance yet
</td>
</tr>

<?php
else:

while ($row = $q->fetch_assoc()):

$datetime = strtotime($row['approved_at']);
$date = date("F d Y", $datetime); 
$time = date("h:i A", $datetime);

$isLocked = (
    $row['resolved'] == 'yes' ||
    $row['admin_action'] == 'approved' ||
    $row['admin_action'] == 'rejected'
);
?>

<tr>

<td><?= htmlspecialchars($row['pc_no']) ?></td>
<td><?= htmlspecialchars(strtoupper($row['comlab'])) ?></td>
<td><?= htmlspecialchars(strtoupper($row['issue_type'])) ?></td>

<td>
<?php
$desc = strtoupper($row['description']);
echo strlen($desc) > 23 ? htmlspecialchars(substr($desc,0,23).'...') : htmlspecialchars($desc);
?>
</td>

<td><?= $date ?></td>
<td><?= $time ?></td>

<td>

<?php
if ($row['resolved'] == 'yes') {

echo "<span class='status-approved'>RESOLVED</span>";

}
elseif ($row['admin_action'] == 'approved') {

echo "<span class='status-approved'>IN PROGRESS</span>";

}
elseif ($row['admin_action'] == 'rejected') {

echo "<span class='status-rejected'>REJECTED</span>";

}
else{

echo "<span class='status-pending'>PENDING</span>";

}
?>

</td>

<td class="more-menu">

<div class="more-wrapper">

<img src="../img/mis_img/more.png" class="more-icon" onclick="toggleMenu(this)">

<div class="more-dropdown">

<button type="button"
class="action-view"
onclick="showDescription(event,'<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>')">
View Description
</button>

<form method="post">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<input type="hidden" name="action_type" value="approve">
<button class="action-approve" <?= $isLocked ? 'disabled' : '' ?>>
Approve
</button>
</form>

<form method="post">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<input type="hidden" name="action_type" value="reject">
<button class="action-reject" <?= $isLocked ? 'disabled' : '' ?>>
Reject
</button>
</form>

<form method="post">
<input type="hidden" name="id" value="<?= $row['id'] ?>">
<input type="hidden" name="action_type" value="resolved">
<button class="action-resolve" <?= $row['resolved']=='yes' ? 'disabled' : '' ?>>
Mark as Resolved
</button>
</form>

</div>
</div>

</td>

</tr>

<?php endwhile; endif; ?>

</tbody>
</table>

</div>
</div>

<div id="descCard" class="desc-card">
<div class="desc-title">DESCRIPTION OF ISSUE:</div>
<div id="descText" class="desc-text"></div>
</div>

<script>

function toggleMenu(el){

let dropdown = el.nextElementSibling;

document.querySelectorAll('.more-dropdown').forEach(menu=>{
if(menu !== dropdown){
menu.style.display="none";
}
});

dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";

}

function showDescription(e,text){

e.stopPropagation();

let card = document.getElementById("descCard");
let desc = document.getElementById("descText");

desc.innerText = text;

let row = e.target.closest("tr");
let descCell = row.children[3];

let rect = descCell.getBoundingClientRect();

card.style.top = (rect.bottom + window.scrollY + 10) + "px";
card.style.left = (rect.left + window.scrollX) + "px";

card.style.display = "block";

document.querySelectorAll('.more-dropdown').forEach(menu=>{
menu.style.display="none";
});

}

document.addEventListener("click",function(e){

if(!e.target.closest('.more-wrapper')){
document.querySelectorAll('.more-dropdown').forEach(menu=>{
menu.style.display="none";
});
}

if(!e.target.closest('.desc-card') && !e.target.classList.contains('action-view')){
let card = document.getElementById("descCard");
if(card) card.style.display="none";
}

});

</script>

</body>
</html>