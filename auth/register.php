<?php
session_start();
require_once '../config/config.php';
require_once '../function/functions.php'; // เรียกใช้ไฟล์ functions.php
date_default_timezone_set('Asia/Bangkok');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "<script>alert('รหัสผ่านไม่ตรงกัน'); </script>";
    }

    $password = password_hash($password, PASSWORD_BCRYPT);

    if (strpos($email, '@ku.th') === false) {
        echo "<script>alert('กรุณาใช้อีเมล @ku.th'); </script>";
    }

    // Check for existing verified user
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verify = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo "<script>alert('อีเมลนี้ถูกใช้งานแล้ว'); </script>";
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
        echo "<script>alert('เกิดข้อผิดพลาดในการส่ง OTP email'); window.history.back();</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.23/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
   .container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    width: 100%;
    padding: 20px;
    box-sizing: border-box;
}

/* Desktop */
@media (min-width: 1024px) {
    .container {
        justify-content: flex-end;
        padding-right: 10%;
    }
}

/* Tablet */
@media (min-width: 768px) and (max-width: 1023px) {
    .container {
        justify-content: flex-end;
        padding-right: 5%;
    }
}

/* Mobile */
@media (max-width: 767px) {
    .container {
        padding: 15px;
    }
    
    .form {
        width: 100%;
        margin: 10px;
    }
    
    .title {
        font-size: 24px;
    }
    
    .message, 
    .signin {
        font-size: 14px;
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
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
    margin-bottom: 10px;
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

.title::after {
    animation: pulse 1s linear infinite;
}

.message, 
.signin {
    font-size: 14.5px;
    color: rgba(255, 255, 255, 0.7);
}

.signin {
    text-align: center;
    margin-top: 10px;
}

.signin a {
    color: rgb(237, 229, 87);
    text-decoration: none;
}

.signin a:hover {
    text-decoration: underline royalblue;
}

.flex {
    display: flex;
    width: 100%;
    gap: 6px;
    align-items: center;
    flex-wrap: wrap;
}

.form label {
    position: relative;
    width: 100%;
}

.form label .input {
    background-color: #333;
    color: #fff;
    width: 100%;
    padding: 20px 5px 5px 10px;
    outline: 0;
    border: 1px solid rgba(105, 105, 105, 0.397);
    border-radius: 10px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form label .input:focus {
    border-color: rgb(237, 229, 87);
}

.form label .input + span {
    color: rgba(255, 255, 255, 0.5);
    position: absolute;
    left: 10px;
    top: 0px;
    font-size: 0.9em;
    cursor: text;
    transition: 0.3s ease;
    pointer-events: none;
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

.submit {
    border: none;
    outline: none;
    padding: 12px;
    border-radius: 10px;
    color: #000;
    font-size: 16px;
    background-color: rgb(237, 229, 87);
    cursor: pointer;
    transition: opacity 0.3s ease;
    width: 100%;
    margin-top: 10px;
}

.submit:hover {
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

/* ปรับแต่งเพิ่มเติมสำหรับความสวยงาม */
.form label .input:hover {
    border-color: rgba(237, 229, 87, 0.5);
}

.form label .input:focus {
    box-shadow: 0 0 0 2px rgba(237, 229, 87, 0.2);
}
    </style>
</head>
<body>
    <img class="absolute top-0 left-0 w-full h-full object-cover" src="https://www.ntplc.co.th/images/default-source/nt_broadband/home-banner_main.png?sfvrsn=b04ed25b_1">
    <div class="hero bg-base-200 min-h-screen">
        <div class="hero-content flex-col lg:flex-row-reverse">
        </div>
        <div class="container">
            <form method="POST" class="form">
                <p class="title">สมัครสมาชิก</p>
                <p class="message">กรุณากรอกรายละเอียดเพื่อสมัครสมาชิก</p>
                <div class="form-control">
                    <label>
                        <input class="input" type="email" name="email" placeholder="" required />
                        <span>Email</span>
                    </label>
                </div>
                <div class="form-control">
                    <label>
                        <input class="input" type="text" name="name" placeholder="" required />
                        <span>ชื่อผู้ใช้</span>
                    </label>
                </div>
                <div class="form-control">
                    <label>
                        <input id="password" class="input" type="password" name="password" placeholder="" required />
                        <span>รหัสผ่าน</span>
                    </label>
                </div>
                <div class="form-control">
                    <label>
                        <input class="input" type="password" name="confirm_password" placeholder="" required />
                        <span>ยืนยันรหัสผ่านอีกครั้ง</span>
                    </label>
                </div>
                <button type="submit" class="submit">ส่ง</button>
                <p class="signin">หากเป็นสมาชิกแล้ว? <a href="../page/login.php">login</a></p>
             </form>
            </div>
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