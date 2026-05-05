<?php include "dashboard_backend.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

<link rel="stylesheet" href="../css/mis/mis_dashboard.css">
<link rel="stylesheet" href="../css/mis/mis_dashboard_layout.css">
<link rel="stylesheet" href="../css/mis/mis_password_modal.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<?php include "../include/mis_sidebar.php"; ?>

<?php include "dashboard_content.php"; ?>

<div id="toast" class="toast">Date set successfully</div>

<script src="../js/mis_dashboard.js" defer></script>
<script src="../js/mis_dashboard_password.js" defer></script>

<script>
const working = <?= $working ?>;
const notworking = <?= $notworking ?>;
const defective = <?= $defective ?>;
const others = <?= $others ?>;

const realData = [working, notworking, defective, others];

const chartData =
(realData.reduce((a,b)=>a+b,0) === 0)
? [1,1,1,1]
: realData;

const ctx = document.getElementById('pcChart');

new Chart(ctx,{
type:'doughnut',
data:{
labels:['Working','Not Working','Defective','Others'],
datasets:[{
data: chartData,
backgroundColor:['#2ecc71','#f1c40f','#e74c3c','#7f8c8d'],
hoverBackgroundColor:['#2ecc71','#f1c40f','#e74c3c','#7f8c8d'],
borderColor:'#ffffff',
borderWidth:3,
hoverBorderColor:'#ffffff',
hoverBorderWidth:3,
hoverOffset:15
}]
},
options:{
cutout:'30%',
plugins:{
legend:{display:false},
tooltip:{
callbacks:{
label:function(context){
return context.label + ": " + realData[context.dataIndex];
}
}
}
}
}
});
</script>

<script>
window.addEventListener("load",function(){
const forceChange = <?= $forceChange ? 'true' : 'false' ?>;
forcePasswordChange = forceChange;

if(forceChange){
document.getElementById("passwordModal").style.display="flex";
}
});
</script>

<script>
function loadNotification(){
fetch("check_notif.php")
.then(res => res.text())
.then(count => {

let badge = document.getElementById("notif-count");
count = parseInt(count);

if(count > 0){
badge.style.display = "inline-block";

if(count > 99){
badge.innerText = "99+";
}else{
badge.innerText = "+" + count;
}
}else{
badge.style.display = "none";
}
});
}

setInterval(loadNotification,2000);
loadNotification();
</script>

</body>
</html>