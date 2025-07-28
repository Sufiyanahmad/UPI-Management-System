<?php
session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
  http_response_code(403);
  die("Unauthorized access. Please login first.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== 'mock-csrf-token') {
  http_response_code(403);
  die("Invalid CSRF token.");
}

$sender_id = $_SESSION['user_id'];
$method = filter_var($_POST['method'], FILTER_SANITIZE_STRING);
$receiver_input = filter_var($_POST['receiver'], FILTER_SANITIZE_STRING);
$amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

if (!$method || !$receiver_input || !$amount || $amount <= 0) {
  http_response_code(400);
  die("Invalid input data.");
}

$receiver_id = null;
if ($method !== 'bank') {
  $stmt = $conn->prepare($method === 'upi' ? 
    "SELECT id FROM users WHERE upi_id = ?" : 
    "SELECT id FROM users WHERE phone = ?"
  );
  $stmt->bind_param("s", $receiver_input);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows !== 1) {
    $stmt->close();
    die("Receiver not found.");
  }
  $receiver = $result->fetch_assoc();
  $receiver_id = $receiver['id'];
  $stmt->close();
}

$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $sender_id);
$stmt->execute();
$result = $stmt->get_result();
$sender = $result->fetch_assoc();
$stmt->close();

if ($sender['balance'] < $amount) {
  die("Insufficient balance.");
}

$conn->begin_transaction();
try {
  $stmt = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
  $stmt->bind_param("di", $amount, $sender_id);
  $stmt->execute();
  $stmt->close();

  if ($method !== 'bank') {
    $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $receiver_id);
    $stmt->execute();
    $stmt->close();
  }

  $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, method) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("iids", $sender_id, $receiver_id, $amount, $method);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  echo "Payment of ₹" . number_format($amount, 2) . " successful!";
} catch (Exception $e) {
  $conn->rollback();
  echo "Transaction failed: " . $e->getMessage();
}
?>