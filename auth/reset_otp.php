<?php
// reset_otp.php
session_start();
require '../config/config.php';
require '../function/functions.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'];

// Reset OTP
$stmt = $conn->prepare("UPDATE users SET password = NULL, otp_attempts = 0, otp = NULL, otp_expiry = NULL WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// Clear session
session_unset();
session_destroy();

echo json_encode(['status' => 'success']);
?>