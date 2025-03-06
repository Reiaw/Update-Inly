<?php
session_start();
require_once '../config/config.php';
require_once '../function/functions.php'; // เรียกใช้ไฟล์ functions.php
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['email'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['email'];

if (isset($_POST['resend_otp'])) {
    $stmt = $conn->prepare("SELECT otp_attempts, last_otp_sent FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Check if attempts exceed limit
    if ($user['otp_attempts'] >= MAX_OTP_ATTEMPTS) {
        resetOTPAndRedirect($conn, $email); // เรียกใช้ฟังก์ชันจาก functions.php
    }

    $otp = generateOTP(); // เรียกใช้ฟังก์ชันจาก functions.php
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+'.OTP_EXPIRY_MINUTES.' minutes'));

    // Increment attempts only on resend
    $stmt = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = ?, otp_attempts = otp_attempts + 1, last_otp_sent = NOW() WHERE email = ?");
    $stmt->bind_param("sss", $otp, $otp_expiry, $email);
    
    if ($stmt->execute() && sendOTP($email, $otp)) { // เรียกใช้ฟังก์ชันจาก functions.php
        $_SESSION['otp_expiry'] = $otp_expiry;
        
        // Check if this update made attempts reach the limit
        $stmt = $conn->prepare("SELECT otp_attempts FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user['otp_attempts'] >= MAX_OTP_ATTEMPTS) {
            resetOTPAndRedirect($conn, $email); // เรียกใช้ฟังก์ชันจาก functions.php
        }
        
        echo "<script>alert('OTP ใหม่ถูกส่งไปยังอีเมลของคุณแล้ว');</script>";
    } else {
        echo "<script>alert('ไม่สามารถส่ง OTP ได้');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['otp'])) {
    // Check attempts but don't increment on verification
    $stmt = $conn->prepare("SELECT otp_attempts, otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user['otp_attempts'] >= MAX_OTP_ATTEMPTS) {
        resetOTPAndRedirect($conn, $email); // เรียกใช้ฟังก์ชันจาก functions.php
    }

    $otp = $_POST['otp'];
    if (verifyOTP($email, $otp)) { // เรียกใช้ฟังก์ชันจาก functions.php
        // Success - verify user
        $stmt = $conn->prepare("UPDATE users SET verify = 1, otp = NULL, otp_expiry = NULL, otp_attempts = 0 WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        header('Location: ../page/login.php');
    } else {
        // Failed attempt - but don't increment otp_attempts
        echo "<script>alert('OTP ไม่ถูกต้อง');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
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
        }

        @media (min-width: 768px) {
            .container {
                justify-content: flex-end;
                padding-right: 10%;
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
            margin: 10px;
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

        .message {
            font-size: 14.5px;
            color: #fff;
            margin-bottom: 20px;
        }

        .form label {
            position: relative;
        }

        .form .input {
            background-color: #333;
            color: #fff;
            width: 100%;
            padding: 20px 5px 5px 10px;
            outline: 0;
            border: 1px solid rgba(105, 105, 105, 0.397);
            border-radius: 10px;
            font-size: 16px;
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

        .submit {
            border: none;
            outline: none;
            padding: 12px;
            border-radius: 10px;
            color: #000;
            font-size: 16px;
            background-color: rgb(237, 229, 87);
            cursor: pointer;
            margin-top: 10px;
            width: 100%;
        }

        .submit:hover {
            opacity: 0.9;
        }

        .resend-button {
            background-color: transparent;
            border: 1px solid rgb(237, 229, 87);
            color: rgb(237, 229, 87);
            padding: 12px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }

        .resend-button:hover {
            background-color: rgba(237, 229, 87, 0.1);
        }

        #timer {
            font-size: 1.2em;
            color: rgb(237, 229, 87);
            text-align: center;
            margin: 10px 0;
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

        @media (max-width: 480px) {
            .form {
                padding: 15px;
                max-width: 100%;
            }
            
            .title {
                font-size: 24px;
            }
            
            .input {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <img class="absolute top-0 left-0 w-full h-full object-cover" src="https://www.ntplc.co.th/images/default-source/nt_broadband/home-banner_main.png?sfvrsn=b04ed25b_1">
    <div class="hero bg-base-200 min-h-screen">
        <div class="container">
            <form method="POST" class="form">
                <p class="title">ยืนยัน OTP</p>
                <p class="message">กรุณากรอกรหัส OTP ที่ส่งไปยังอีเมลของคุณ</p>
                <div id="timer"></div>

                <div class="form-control">
                    <label>
                        <input type="text" name="otp" class="input" placeholder="" required />
                        <span>OTP Code</span>
                    </label>
                </div>

                <button type="submit" class="submit">ยืนยัน OTP</button>

                <button type="submit" name="resend_otp" class="resend-button">
                    ส่ง OTP อีกครั้ง
                </button>
            </form>
        </div>
    </div>

    <script>
        // Your existing JavaScript code
        let otpExpiry = new Date("<?php echo $_SESSION['otp_expiry']; ?>").getTime();

        function updateTimer() {
            const now = new Date().getTime();
            const timeLeft = Math.max(0, Math.floor((otpExpiry - now) / 1000));

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('timer').innerHTML = `เวลาเหลือ: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            if (timeLeft > 0) {
                setTimeout(updateTimer, 1000);
            } else {
                document.getElementById('timer').innerHTML = "OTP หมดอายุแล้ว";
                // Send request to server to reset OTP
                fetch('reset_otp.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email: "<?php echo $email; ?>" })
                }).then(response => {
                    if (response.ok) {
                        window.location.href = 'register.php';
                    }
                });
            }
        }

        window.onload = updateTimer;
    </script>
</body>
</html>