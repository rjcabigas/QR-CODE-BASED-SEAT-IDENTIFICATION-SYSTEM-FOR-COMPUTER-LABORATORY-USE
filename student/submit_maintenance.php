<?php
session_start();
include "../include/db.php";

$conn->query("SET time_zone = '+08:00'");
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['student_id'])) {
    exit("not_logged");
}

$student_id = $_SESSION['student_id'];

if (
    !isset($_POST['pc_no']) ||
    !isset($_POST['comlab']) ||
    !isset($_POST['status'])
) {
    exit("missing");
}

$pc = trim($_POST['pc_no']);
$comlab = trim($_POST['comlab']);
$status = trim($_POST['status']);

$type = trim($_POST['issue_type'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($pc === "" || $comlab === "") {
    exit("missing_data");
}

$type = substr($type, 0, 50);
$desc = substr($desc, 0, 255);

$pc_no = intval(preg_replace('/[^0-9]/', '', $pc));

if (
    $pc_no != 0 &&
    $status !== "Working" &&
    $status !== "Not Used"
) {
    if ($status === "Defective") {
        if (empty($type)) {
            $type = "Defective";
        }

        if (empty($desc)) {
            $desc = "None";
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO maintenance
        (student_id, pc_no, comlab, status, issue_type, description)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssss",
        $student_id,
        $pc,
        $comlab,
        $status,
        $type,
        $desc
    );

    $stmt->execute();
    $stmt->close();
}

$current_time = date("H:i:s");

$stmt = $conn->prepare("
    SELECT id, start_time, late_minutes
    FROM teacher_subjects
    WHERE start_time IS NOT NULL
    AND end_time IS NOT NULL
    AND ? BETWEEN start_time AND end_time
    LIMIT 1
");

$stmt->bind_param("s", $current_time);
$stmt->execute();

$subject = $stmt->get_result()->fetch_assoc();

$stmt->close();

if (!$subject) {
    exit("no_active_subject");
}

$teacher_subject_id = intval($subject['id']);

$now = time();
$start = strtotime(date("Y-m-d") . " " . $subject['start_time']);
$lateLimit = $start + ($subject['late_minutes'] * 60);

$status_final = (
    $subject['late_minutes'] > 0 &&
    $now > $lateLimit
) ? "late" : "present";

$pc_status_final = $status;

$stmt = $conn->prepare("
    INSERT INTO attendance
    (
        student_id,
        teacher_subject_id,
        pc_no,
        comlab_no,
        status,
        pc_status,
        time_in,
        date
    )
    VALUES
    (
        ?, ?, ?, ?, ?, ?, NOW(), CURDATE()
    )
");

$stmt->bind_param(
    "siisss",
    $student_id,
    $teacher_subject_id,
    $pc_no,
    $comlab,
    $status_final,
    $pc_status_final
);

$stmt->execute();
$stmt->close();

echo "success";
?>