<?php

require_once "../include/db.php";

$edit = null;

if(isset($_GET['edit']) && $_GET['edit'] !== ""){

    $id = (int)$_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM students WHERE id=? LIMIT 1");
    $stmt->bind_param("i",$id);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows){
        $edit = $result->fetch_assoc();
    }

    $stmt->close();
}

$where = ["status='active'"];
$params = [];
$types = "";

$filters = ["semester","year","section","course"];

foreach($filters as $field){

    if(!empty($_GET[$field])){

        $where[] = "$field=?";
        $params[] = trim($_GET[$field]);
        $types .= "s";

    }

}

if(!empty($_GET['search'])){

    $search = "%".trim($_GET['search'])."%";

    $where[] = "(student_name LIKE ? OR student_id LIKE ? OR email LIKE ?)";

    array_push($params,$search,$search,$search);
    $types .= "sss";

}

$whereSQL = "WHERE ".implode(" AND ",$where);

$limit = 8;
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$count_sql = "SELECT COUNT(*) total FROM students $whereSQL";

$count_stmt = $conn->prepare($count_sql);

if($types){
$count_stmt->bind_param($types,...$params);
}

$count_stmt->execute();

$total_students = $count_stmt->get_result()->fetch_assoc()['total'];

$count_stmt->close();

$total_pages = ceil($total_students / $limit);

$sql = "SELECT * FROM students $whereSQL ORDER BY id DESC LIMIT ?,?";

$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types,...$params);
$stmt->execute();

$students = $stmt->get_result();

$stmt->close();