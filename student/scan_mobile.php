<?php
session_start();

if(!isset($_SESSION['student_id'])){
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Scan PC QR</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://unpkg.com/html5-qrcode"></script>

<style>
body{
    font-family:Poppins, Arial, sans-serif;
    text-align:center;
}

#reader{
    width:300px;
    margin:auto;
}
</style>

</head>

<body>

<h3>Scan PC QR</h3>

<div id="reader"></div>

<script>

let scanned = false;
let scanner;

function onScanSuccess(decodedText){

    if(scanned) return;
    scanned = true;

    scanner.clear();

    fetch("scan_qr.php",{
        method:"POST",
        headers:{
            'Content-Type':'application/x-www-form-urlencoded'
        },
        body:"qr="+encodeURIComponent(decodedText)
    })
    .then(res=>res.text())
    .then(data=>{

        if(data.trim()=="success"){
            alert("Attendance recorded!");
            window.location="attendance_mobile.php";
        }else{
            alert("Already scanned today");
            scanned=false;
        }

    })
    .catch(()=>{
        alert("Network error. Try again.");
        scanned=false;
    });

}

scanner = new Html5QrcodeScanner(
    "reader",
    { fps:10, qrbox:250 }
);

scanner.render(onScanSuccess);

</script>

</body>
</html>