<?php
session_start();
require '../config/config.php';
require '../function/functions.php';
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    exit;
}

$email = $_SESSION['reset_email'];

// Fetch OTP expiry time from the database
$stmt = $conn->prepare("SELECT otp_expiry FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$otp_expiry = isset($user['otp_expiry']) ? strtotime($user['otp_expiry']) : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $otp = trim($_POST['otp']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        echo "<script>alert('รหัสผ่านไม่ตรงกัน');</script>";
    }

    if (time() > $otp_expiry) {
        echo "<script>alert('OTP หมดอายุ กรุณาขอรหัส OTP ใหม่');</script>";
    }

    if (verifyOTP($email, $otp)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed_password, $email);
        if ($stmt->execute()) {
            unset($_SESSION['reset_email']);
            echo "<script>
                alert('รหัสผ่านถูกเปลี่ยนเรียบร้อยแล้ว');
                window.location.href = '../login.php';
            </script>";
            exit;
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('OTP ไม่ถูกต้อง กรุณาตรวจสอบและลองใหม่อีกครั้ง'); window.history.back();</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รีเซ็ตรหัสผ่าน</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.23/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <html data-theme="cyberpunk"></html>
    <style>
        .reset-password-form {
            position: absolute;
            top: 180px;
            right: 220px;
        }
        .timer-style {
            font-size: 1.5em;
            font-weight: bold;
            color: red;
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
        <div class="card bg-base-100 w-full max-w-sm shrink-0 shadow-2xl reset-password-form">
            <form method="POST" class="card-body">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">OTP</span>
                    </label>
                    <input type="text" name="otp" placeholder="OTP" class="input input-bordered" required />
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">รหัสผ่านใหม่</span>
                    </label>
                    <div class="password-container">
                        <input id="password" type="password" name="new_password" placeholder="รหัสผ่านใหม่" class="input input-bordered w-full" required />
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">ยืนยันรหัสผ่านใหม่</span>
                    </label>
                    <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" class="input input-bordered" required />
                </div>
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">รีเซ็ตรหัสผ่าน</button>
                </div>
            </form>
            <p class="text-center mt-3">
                <a href="../login.php" class="link link-hover">กลับไปหน้าเข้าสู่ระบบ</a>
            </p>
            <p class="timer-style text-center mt-3">เวลาที่เหลือ: <span id="timer"></span></p>
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
        function startTimer(duration, display) {
            let timer = duration;
            const interval = setInterval(() => {
                const minutes =  String(Math.floor(timer / 60)).padStart(2, '0');
                const seconds = String(timer % 60).padStart(2, '0');
                display.textContent = `${minutes}:${seconds}`;

                if (--timer < 0) {
                    clearInterval(interval);
                    alert('OTP หมดอายุแล้ว กรุณาลองใหม่อีกครั้ง');
                    window.location.href = '../login.php';
                }
            }, 1000);
        }

        window.onload = () => {
            const expiryTime = <?php echo max(0, $otp_expiry - time()); ?>;
            const display = document.querySelector('#timer');
            startTimer(expiryTime, display);
        };
    </script>
</body>
</html>