<?php
session_start();
include("config.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== 'mock-csrf-token') {
  http_response_code(403);
  die("Invalid CSRF token.");
}

$name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
$phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$upi_id = filter_var($_POST['upi_id'], FILTER_SANITIZE_STRING);
$password = $_POST['password'];

if (!$name || !$phone || !$email || !$upi_id || !$password) {
  http_response_code(400);
  die("Invalid input data.");
}

if (!preg_match("/^[0-9]{10}$/", $phone)) {
  die("Invalid phone number.");
}
if (!preg_match("/^[a-zA-Z0-9]+@[a-zA-Z]+$/", $upi_id)) {
  die("Invalid UPI ID format.");
}
if (strlen($password) < 8) {
  die("Password must be at least 8 characters.");
}

$hashed_password = password_hash($password, PASSWORD_BCRYPT);
$stmt = $conn->prepare("INSERT INTO users (name, phone, email, upi_id, password, balance) VALUES (?, ?, ?, ?, ?, ?)");
$balance = 1000.00;
$stmt->bind_param("sssssd", $name, $phone, $email, $upi_id, $hashed_password, $balance);

if ($stmt->execute()) {
  echo "✅ Registration successful. <a href='index.html'>Login now</a>";
} else {
  echo "❌ Error: " . $conn->error;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
  <title>User Registration</title>
  <link rel="stylesheet" href="css/style.css">
  <meta name="csrf-token" content="mock-csrf-token">
</head>
<body>
  <div class="dashboard">
    <h2>Register</h2>
    <form method="POST" action="">
      <input type="text" name="name" placeholder="Full Name" required>
      <input type="text" name="phone" placeholder="Phone Number" required pattern="[0-9]{10}">
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="upi_id" placeholder="UPI ID" required pattern="[a-zA-Z0-9]+@[a-zA-Z]+">
      <input type="password" name="password" placeholder="Password" required minlength="8">
      <input type="hidden" name="csrf_token" value="mock-csrf-token">
      <button type="submit">Register</button>
    </form>
  </div>
</body>
</html>