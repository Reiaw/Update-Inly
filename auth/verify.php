<?php
session_start();
require '../config/config.php';
require '../function/functions.php'; // เรียกใช้ไฟล์ functions.php
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
        echo "<script>alert('Error sending OTP');</script>";
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
        header('Location: ../login.php');
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
    <html data-theme="cyberpunk"></html>
    <style>
        .verify-form {
            position: absolute;
            top: 180px;
            right: 220px;
        }
        #timer {
            font-size: 1.5em;
            font-weight: bold;
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <img class="absolute top-0 left-0 w-full h-full object-cover" src="https://www.ntplc.co.th/images/default-source/nt_broadband/home-banner_main.png?sfvrsn=b04ed25b_1">
    <div class="hero bg-base-200 min-h-screen">
        <div class="hero-content flex-col lg:flex-row-reverse">
        </div>
        <div class="card bg-base-100 w-full max-w-sm shrink-0 shadow-2xl verify-form">
            <form method="POST" class="card-body">
                <h2 class="text-center text-2xl font-bold mb-4">ยืนยัน OTP</h2>
                <div id="timer"></div>

                <div class="form-control">
                    <label class="label">
                        <span class="label-text">OTP</span>
                    </label>
                    <input type="text" name="otp" placeholder="Enter OTP" class="input input-bordered" required />
                </div>
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">ยืนยัน</button>
                </div>
            </form>

            <form method="POST" class="card-body">
                <div class="form-control mt-6">
                    <button type="submit" name="resend_otp" class="btn btn-secondary">ส่ง OTP อีกครั้ง</button>
                </div>
            </form>
        </div>
    </div>
    <script>
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