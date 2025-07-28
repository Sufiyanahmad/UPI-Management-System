<?php
session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  echo "Unauthorized";
  exit;
}

if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== 'mock-csrf-token') {
  http_response_code(403);
  echo "Invalid CSRF token.";
  exit;
}

$method = filter_var($_GET['method'], FILTER_SANITIZE_STRING);
$input = filter_var($_GET['receiver'], FILTER_SANITIZE_STRING);

if (!$method || !$input) {
  http_response_code(400);
  echo "Invalid input";
  exit;
}

switch ($method) {
  case "upi":
    $stmt = $conn->prepare("SELECT name FROM users WHERE upi_id = ?");
    break;
  case "phone":
    $stmt = $conn->prepare("SELECT name FROM users WHERE phone = ?");
    break;
  case "bank":
    echo "Bank transfers don't store names.";
    exit;
  default:
    http_response_code(400);
    echo "Invalid method";
    exit;
}

$stmt->bind_param("s", $input);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
  $row = $result->fetch_assoc();
  echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
} else {
  echo "User not found";
}
$stmt->close();
?>