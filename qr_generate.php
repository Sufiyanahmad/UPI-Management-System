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

$upi_id = filter_var($_POST['upi_id'], FILTER_SANITIZE_STRING);
$amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT);

if (!$upi_id || !$amount || $amount <= 0) {
  http_response_code(400);
  die("Invalid UPI ID or amount.");
}

$upi_url = "upi://pay?pa=" . urlencode($upi_id) . "&am=" . urlencode($amount);
$qr_url = "https://chart.googleapis.com/chart?cht=qr&chs=250x250&chl=" . urlencode($upi_url);

echo "<img src='$qr_url' alt='QR Code'>";
echo "<p>Scan this QR to send ₹" . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . " to " . htmlspecialchars($upi_id, ENT_QUOTES, 'UTF-8') . "</p>";
?>