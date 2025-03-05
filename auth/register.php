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
        echo "<script>alert('Error sending OTP email'); window.history.back();</script>";
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
        padding-left:800px; 
       }
        
        .form {
            display: flex;
            flex-direction: column;
            gap: 10px;
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
            color:rgb(237, 229, 87);
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
            background-color:rgb(237, 229, 87);
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
            color:rgb(237, 229, 87);
        }

        .flex {
            display: flex;
            width: 100%;
            gap: 6px;
        }

        .form label {
            position: relative;
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
            color:rgb(237, 229, 87);
            top: 0px;
            font-size: 0.7em;
            font-weight: 600;
        }

        .input {
            font-size: medium;
        }

        .submit {
            border: none;
            outline: none;
            padding: 10px;
            border-radius: 10px;
            color: #000;
            font-size: 16px;
            transform: .3s ease;
            background-color:rgb(237, 229, 87);
        }

        .submit:hover {
            background-color:rgb(237, 229, 87);
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
    </style>
</head>
<body>
    <img class="absolute top-0 left-0 w-full h-full object-cover" src="https://www.ntplc.co.th/images/default-source/nt_broadband/home-banner_main.png?sfvrsn=b04ed25b_1">
    <div class="hero bg-base-200 min-h-screen">
        <div class="hero-content flex-col lg:flex-row-reverse">
        </div>
        <div class="container">
            <form method="POST" class="form">
                <p class="title">Sign Up</p>
                <p class="message">Signup now and get full access to our web app.</p>
                <div class="form-control">
                    <label>
                        <input class="input" type="email" name="email" placeholder="" required />
                        <span>Email</span>
                    </label>
                </div>
                <div class="form-control">
                    <label>
                        <input class="input" type="text" name="name" placeholder="" required />
                        <span>Username</span>
                    </label>
                </div>
                <div class="form-control">
                    <label>
                        <input id="password" class="input" type="password" name="password" placeholder="" required />
                        <span>Password</span>
                    </label>
                </div>
                <div class="form-control">
                    <label>
                        <input class="input" type="password" name="confirm_password" placeholder="" required />
                        <span>Confirm Your Password</span>
                    </label>
                </div>
                <button type="submit" class="submit">Submit</button>
                <p class="signin">Have you sign in alredy? <a href="../page/login.php">login</a></p>
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