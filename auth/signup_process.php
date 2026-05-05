<?php
session_start();
require_once "../include/db.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

function response($data) {
    echo json_encode($data);
    exit;
}

$fullname = strtoupper(trim($_POST['fullname'] ?? ''));
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';
$role = strtolower(trim($_POST['role'] ?? ''));

if (!$fullname || !$email || !$password || !$confirm || !$role) {
    response(["status" => "error", "message" => "All fields are required."]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    response(["status" => "error", "field" => "email", "message" => "Invalid email format."]);
}

if (strlen($password) < 6) {
    response(["status" => "error", "field" => "password", "message" => "Password must be at least 6 characters."]);
}

if ($password !== $confirm) {
    response(["status" => "error", "field" => "confirm", "message" => "Passwords do not match."]);
}

if ($role !== 'teacher') {
    response(["status" => "error", "message" => "Only teacher accounts can be registered."]);
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows) {
    $stmt->close();
    response(["status" => "error", "field" => "email", "message" => "Email already registered."]);
}
$stmt->close();

$hash = password_hash($password, PASSWORD_DEFAULT);
$status = "active";

$stmt = $conn->prepare("INSERT INTO users (fullname,email,password,role,status,created_at) VALUES (?,?,?,?,?,NOW())");
$stmt->bind_param("sssss", $fullname, $email, $hash, $role, $status);

if (!$stmt->execute()) {
    $stmt->close();
    $conn->close();
    response(["status" => "error", "message" => "Signup failed. Try again."]);
}

$stmt->close();
$conn->close();

response([
    "status" => "success",
    "redirect" => "login.php"
]);