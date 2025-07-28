<?php
session_start();
include("config.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== 'mock-csrf-token') {
  http_response_code(403);
  die("Invalid CSRF token.");
}

$upi_id = $_POST['upi_id'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM users WHERE upi_id = ?");
$stmt->bind_param("s", $upi_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
  $user = $result->fetch_assoc();
  if (password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['is_admin'] = $user['is_admin'] ?? false;
    header("Location: ../frontend/dashboard.html");
    exit;
  } else {
    echo "Invalid password.";
  }
} else {
  echo "User not found.";
}
$stmt->close();
?>