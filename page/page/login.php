<?php
session_start();
require_once  '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // ตรวจสอบอีเมลและการยืนยัน
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND verify = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; // เก็บ user_id ในเซสชัน
            $_SESSION['email'] = $email;
            $_SESSION['name'] = $user['name']; // เก็บชื่อใน session
            header('Location: index.php');
            exit;
        } else {
            echo "<script>alert('รหัสผ่านไม่ถูกต้อง');</script>";
        }
    } else {
        echo "<script>alert('อีเมลไม่ถูกต้องหรือยังไม่ได้ยืนยัน');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.23/dist/full.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <html data-theme="cyberpunk"></html>
    <style>
        .login-form {
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
        <div class="card bg-base-100 w-full max-w-sm shrink-0 shadow-2xl login-form">
            <form method="POST" class="card-body">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">อีเมลล์</span>
                    </label>
                    <input type="email" name="email" placeholder="อีเมลล์" class="input input-bordered" required />
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
                    <label class="label">
                        <a href="../auth/forgot-password.php" class="label-text-alt link link-hover">ลืมรหัสผ่าน?</a>
                    </label>
                </div>
                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
                </div>
            </form>
            <p class="text-center mt-3">
                ยังไม่ได้ลงทะเบียน? <a href="../auth/register.php" class="link link-hover">สมัครสมาชิก</a>
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