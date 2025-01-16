<?php
session_start();
require_once '../config/config.php';
require_once '../function/functions.php';
date_default_timezone_set('Asia/Bangkok');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    // ตรวจสอบอีเมลและการยืนยัน
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verify = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $otp = generateOTP();
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+' . OTP_EXPIRY_MINUTES . ' minutes'));

        // บันทึก OTP และเวลาหมดอายุในฐานข้อมูล
        $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?");
        $stmt->bind_param("sss", $otp, $otp_expiry, $email);
        $stmt->execute();

        // ส่ง OTP ไปยังอีเมล
        if (sendOTP($email, $otp)) {
            $_SESSION['reset_email'] = $email;
            header('Location: reset-password.php');
            exit;
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการส่ง OTP'); </script>";
        }
    } else {
        echo "<script>alert('อีเมลไม่ถูกต้องหรือยังไม่ได้ยืนยัน'); </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.23/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <html data-theme="cyberpunk"></html>
    <style>
        .forgot-password-form {
            position: absolute;
            top: 180px;
            right: 220px;
        }
    </style>
</head>
<body>
    <img class="absolute top-0 left-0 w-full h-full object-cover" src="https://www.ntplc.co.th/images/default-source/nt_broadband/home-banner_main.png?sfvrsn=b04ed25b_1">
    <div class="hero bg-base-200 min-h-screen">
        <div class="hero-content flex-col lg:flex-row-reverse">
        </div>
        <div class="card bg-base-100 w-full max-w-sm shrink-0 shadow-2xl forgot-password-form">
            <form method="POST" class="card-body">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">อีเมลล์</span>
                    </label>
                    <input type="email" name="email" placeholder="อีเมลล์" class="input input-bordered" required />
                </div>
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">ส่ง OTP</button>
                </div>
            </form>
            <p class="text-center mt-3">
                <a href="../page/login.php" class="link link-hover">กลับไปหน้าเข้าสู่ระบบ</a>
            </p>
        </div>
    </div>
</body>
</html> 