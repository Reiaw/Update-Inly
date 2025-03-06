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
    <title>Fogot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.23/dist/full.min.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
    .container {
    display: flex;
    justify-content: center; /* เริ่มต้นจัดกึ่งกลางสำหรับมือถือ */
    align-items: center;
    min-height: 100vh;
    width: 100%;
    padding: 20px;
}

/* Desktop และหน้าจอใหญ่ */
@media (min-width: 1024px) {
    .container {
        justify-content: flex-end;
        padding-right: 15%; /* ระยะห่างจากขอบขวา */
    }
}

/* Tablet */
@media (min-width: 768px) and (max-width: 1023px) {
    .container {
        justify-content: flex-end;
        padding-right: 10%;
    }
}

/* มือถือ */
@media (max-width: 767px) {
    .container {
        padding: 15px;
    }
    
    .form {
        width: 100%;
        max-width: 100%;
        margin: 10px;
    }
}

.form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
    max-width: 350px;
    padding: 20px;
    border-radius: 20px;
    position: relative;
    background-color: #1a1a1a;
    color: #fff;
    border: 1px solid #333;
}

.title {
    font-size: 28px;
    font-weight: 600;
    letter-spacing: -1px;
    position: relative;
    display: flex;
    align-items: center;
    padding-left: 30px;
    color: rgb(237, 229, 87);
}

.title::before {
    width: 18px;
    height: 18px;
}

.title::after {
    width: 18px;
    height: 18px;
    animation: pulse 1s linear infinite;
}

.title::before,
.title::after {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    border-radius: 50%;
    left: 0px;
    background-color: rgb(237, 229, 87);
}

.message, 
.signin {
    font-size: 14.5px;
    color: rgba(255, 255, 255, 0.7);
}

.signin {
    text-align: center;
}

.signin a:hover {
    text-decoration: underline royalblue;
}

.signin a {
    color: rgb(237, 229, 87);
}

.flex {
    display: flex;
    width: 100%;
    gap: 6px;
    flex-wrap: wrap; /* เพิ่มการรองรับการขึ้นบรรทัดใหม่บนมือถือ */
}

.form label {
    position: relative;
    width: 100%;
}

.form label .input {
    background-color: #333;
    color: #fff;
    width: 100%;
    padding: 20px 05px 05px 10px;
    outline: 0;
    border: 1px solid rgba(105, 105, 105, 0.397);
    border-radius: 10px;
}

.form label .input + span {
    color: rgba(255, 255, 255, 0.5);
    position: absolute;
    left: 10px;
    top: 0px;
    font-size: 0.9em;
    cursor: text;
    transition: 0.3s ease;
}

.form label .input:placeholder-shown + span {
    top: 12.5px;
    font-size: 0.9em;
}

.form label .input:focus + span,
.form label .input:valid + span {
    color: rgb(237, 229, 87);
    top: 0px;
    font-size: 0.7em;
    font-weight: 600;
}

.input {
    font-size: 16px; /* ปรับขนาดตัวอักษรให้อ่านง่ายบนมือถือ */
}

.submit {
    border: none;
    outline: none;
    padding: 12px; /* เพิ่มพื้นที่กดสำหรับมือถือ */
    border-radius: 10px;
    color: #000;
    font-size: 16px;
    transform: .3s ease;
    background-color: rgb(237, 229, 87);
    width: 100%;
    cursor: pointer;
}

.submit:hover {
    background-color: rgb(237, 229, 87);
    opacity: 0.9;
}

@keyframes pulse {
    from {
        transform: scale(0.9);
        opacity: 1;
    }
    to {
        transform: scale(1.8);
        opacity: 0;
    }
}

/* ปรับปรุงการแสดงผลบนมือถือ */
@media (max-width: 480px) {
    .title {
        font-size: 24px;
    }
    
    .message,
    .signin {
        font-size: 14px;
    }
    
    .form label .input {
        padding: 18px 05px 05px 10px;
    }
    
    .submit {
        padding: 14px;
    }
}
    </style>
</head>
<body>
    <img class="absolute top-0 left-0 w-full h-full object-cover" src="https://www.ntplc.co.th/images/default-source/nt_broadband/home-banner_main.png?sfvrsn=b04ed25b_1">
    <div class="hero bg-base-200 min-h-screen">
        <div class="hero-content flex-col lg:flex-row-reverse">
        </div>
        <div class="container">
        <div class="card  w-full max-w-sm shrink-0  forgot-password-form">
            <form method="POST" class="form">
                <p class="title">ลืมรหัสผ่าน</p>
                <p class="message">กรุณาใส่อีเมลเพื่อขอรับ OTP</p>
                <label>
                    <input class="input" type="email" name="email" placeholder="" required="">
                    <span>Email</span>
                </label>
                <button type="submit" class="submit">ส่ง OTP</button>
                <p class="signin">ต้องการกลับไปหน้า <a href="../page/login.php">Login</a></p>
            </form>
        </div>
    </div>
</div>
</body>
</html> 