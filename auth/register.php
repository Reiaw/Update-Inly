<?php
session_start();
require '../config/config.php';
require '../function/functions.php'; // เรียกใช้ไฟล์ functions.php
date_default_timezone_set('Asia/Bangkok');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('รหัสผ่านไม่ตรงกัน'); window.history.back();</script>";
        exit;
    }

    $password = password_hash($password, PASSWORD_BCRYPT);

    if (strpos($email, '@ku.th') === false) {
        echo "<script>alert('กรุณาใช้อีเมล @ku.th'); window.history.back();</script>";
        exit;
    }

    // Check for existing verified user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verify = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "<script>alert('อีเมลนี้ถูกใช้งานแล้ว'); window.history.back();</script>";
        exit;
    }

    $otp = generateOTP(); // เรียกใช้ฟังก์ชันจาก functions.php
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+'.OTP_EXPIRY_MINUTES.' minutes'));

    // Reset OTP attempts when registering
    $stmt = $conn->prepare("INSERT INTO users (email, name, password, otp, otp_expiry, otp_attempts, last_otp_sent) 
                            VALUES (?, ?, ?, ?, ?, 1, NOW()) 
                            ON DUPLICATE KEY UPDATE 
                            otp = ?, otp_expiry = ?, password = ?, name = ?, otp_attempts = 1, last_otp_sent = NOW()");
    $stmt->bind_param("sssssssss", $email, $name, $password, $otp, $otp_expiry, $otp, $otp_expiry, $password, $name);
    
    if ($stmt->execute() && sendOTP($email, $otp)) { // เรียกใช้ฟังก์ชันจาก functions.php
        $_SESSION['email'] = $email;
        $_SESSION['otp_expiry'] = $otp_expiry;
        header('Location: verify.php');
    } else {
        echo "<script>alert('Error sending OTP email'); window.history.back();</script>";
    }
}
?>
<<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.23/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <html data-theme="cyberpunk"></html>
    <style>
        .register-form {
            position: absolute;
            top: 180px;
            right: 220px;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <img class="absolute top-0 left-0 w-full h-full object-cover" src="https://www.ntplc.co.th/images/default-source/nt_broadband/home-banner_main.png?sfvrsn=b04ed25b_1">
    <div class="hero bg-base-200 min-h-screen">
        <div class="hero-content flex-col lg:flex-row-reverse">
        </div>
        <div class="card bg-base-100 w-full max-w-sm shrink-0 shadow-2xl register-form">
            <form method="POST" class="card-body">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">อีเมลล์</span>
                    </label>
                    <input type="email" name="email" placeholder="อีเมลล์" class="input input-bordered" required />
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">ชื่อ</span>
                    </label>
                    <input type="text" name="name" placeholder="ชื่อ" class="input input-bordered" required />
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">รหัสผ่าน</span>
                    </label>
                    <div class="password-container">
                        <input id="password" type="password" name="password" placeholder="รหัสผ่าน" class="input input-bordered w-full" required />
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">ยืนยันรหัสผ่าน</span>
                    </label>
                    <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" class="input input-bordered" required />
                </div>
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">สมัครสมาชิก</button>
                </div>
            </form>
            <p class="text-center mt-3">
                ลงทะเบียนแล้ว? <a href="../login.php" class="link link-hover">เข้าสู่ระบบ</a>
            </p>
        </div>
    </div>
    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = document.querySelector('.toggle-password i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>