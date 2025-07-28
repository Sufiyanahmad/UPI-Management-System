<?php
session_start();
include(__DIR__ . "/config.php");

// Debug: Check session
if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  error_log("Session user_id not set");
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== 'mock-csrf-token') {
  http_response_code(403);
  error_log("Invalid CSRF token");
  echo json_encode(['error' => 'Invalid CSRF token']);
  exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT balance, upi_id FROM users WHERE id = ?");
if ($stmt === false) {
  error_log("Prepare failed: " . $conn->error);
  http_response_code(500);
  echo json_encode(['error' => 'Database prepare failed']);
  exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
  $row = $result->fetch_assoc();
  header('Content-Type: application/json');
  echo json_encode(['balance' => $row['balance'], 'upi_id' => $row['upi_id']]);
} else {
  error_log("No rows found for user_id: $user_id");
  http_response_code(500);
  echo json_encode(['error' => 'Error fetching balance or UPI ID']);
}
$stmt->close();
$conn->close();
?>