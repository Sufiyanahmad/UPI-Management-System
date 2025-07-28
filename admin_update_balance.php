<?php
session_start();
include("config.php");

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
  http_response_code(403);
  die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== 'mock-csrf-token') {
  http_response_code(403);
  die("Invalid CSRF token.");
}

$stmt = $conn->prepare("UPDATE users SET balance = ? WHERE balance = ?");
$balance = 100000;
$zero = 0;
$stmt->bind_param("dd", $balance, $zero);

if ($stmt->execute()) {
  echo "✅ Successfully updated balance to ₹100000 for all users with ₹0.";
} else {
  echo "❌ Error updating balance: " . $conn->error;
}
$stmt->close();
?>