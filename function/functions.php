<?php
require '../vendor/autoload.php';

function setupMailer() {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->setFrom(SMTP_USER, 'Send OTP');
    return $mail;
}

function sendOTP($email, $otp) {
    $mail = setupMailer();
    $mail->addAddress($email);
    $mail->Subject = 'OTP for Registration';
    $mail->Body = "Your OTP is: $otp";
    return $mail->send();
}

function generateOTP() {
    return rand(100000, 999999); // สร้าง OTP 6 หลัก
}

function verifyOTP($email, $otp) {
    global $conn;
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['otp'] == $otp && strtotime($user['otp_expiry']) > time()) {
            return true;
        }
    }
    return false;
}

function resetOTPAndRedirect($conn, $email) {
    // Reset OTP attempts, set OTP to null
    $stmt = $conn->prepare("UPDATE users SET name = NULL, password = NULL, otp_attempts = 0, otp = NULL, otp_expiry = NULL WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Redirect to register
    echo "<script>alert('คุณใช้ OTP เกินจำนวนครั้งที่กำหนด กรุณาลงทะเบียนใหม่'); window.location.href = 'register.php';</script>";
    exit;
}
?>