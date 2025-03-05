<?php
session_start();
require_once '../config/config.php';
require_once '../function/functions.php';
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
                window.location.href = '../page/login.php';
            </script>";
            exit;
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'); </script>";
        }
    } else {
        echo "<script>alert('OTP ไม่ถูกต้อง กรุณาตรวจสอบและลองใหม่อีกครั้ง');</script>";   
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
    <style>
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
            color: rgb(237, 229, 87);
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
            color: rgb(237, 229, 87);
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

        .form label {
            position: relative;
        }

        .form label .input {
            background-color: #333;
            color: #fff;
            width: 100%;
            padding: 20px 5px 5px 10px;
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

        .submit {
            border: none;
            outline: none;
            padding: 10px;
            border-radius: 10px;
            color: black;
            font-size: 16px;
            background-color: rgb(237, 229, 87);
            cursor: pointer;
        }

        .submit:hover {
            background-color: rgb(255, 255, 100);
        }

        .reset-password-form {
            position: relative;
            margin-left: auto;
            margin-right: 200px;
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
        <div class="card w-full max-w-sm shrink-0 reset-password-form">
            <form method="POST" class="form">
                <p class="title">Reset Your Password</p>
                <p class="message">Enter OTP and set your password</p>
                <div class="form-control">
                    <label>
                        <input class="input" type="text" name="otp" placeholder="" required />
                        <span>OTP</span>
                    </label>
                </div>
                <div class="form-control">
                    <label>
                        <div class="password-container">
                            <input id="password" class="input" type="password" name="new_password" placeholder="" required />
                            <span>New Password</span>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </label>
                </div>
                <div class="form-control">
                    <label>
                        <input class="input" type="password" name="confirm_password" placeholder="" required />
                        <span>Confirm Your New Password</span>
                    </label>
                </div>
                <button type="submit" class="submit">Reset Password</button>
                <p class="signin">Bact to <a href="../page/login.php">Login</a></p>
                <p class="timer-style text-center mt-3">Timer: <span id="timer"></span></p>
            </form>
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