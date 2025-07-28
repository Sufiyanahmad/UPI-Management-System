<?php
session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  die("Unauthorized access. Please login first.");
}

if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || $_SERVER['HTTP_X_CSRF_TOKEN'] !== 'mock-csrf-token') {
  http_response_code(403);
  echo "Invalid CSRF token.";
  exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
  SELECT t.*, u1.upi_id AS sender_upi, u2.upi_id AS receiver_upi
  FROM transactions t
  LEFT JOIN users u1 ON t.sender_id = u1.id
  LEFT JOIN users u2 ON t.receiver_id = u2.id
  WHERE t.sender_id = ? OR t.receiver_id = ?
  ORDER BY t.date DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rows = [];

while ($row = $result->fetch_assoc()) {
  $rows[] = $row;
}

header('Content-Type: application/json');
echo json_encode($rows);
$stmt->close();
?>