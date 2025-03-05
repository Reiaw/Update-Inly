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
    <style>
       
       .container {
            display: flex;
            justify-content: flex-end; /* ชิดขวา */
            align-items: center;
            height: 100vh;
            padding-left: 800px; /* ปรับระยะห่างจากขอบขวา */
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
            color: #fff;
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
            color: rgb(237, 229, 87);
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
            background-color: rgb(237, 229, 87);
        }

        .submit:hover {
            background-color: rgb(237, 229, 87);
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

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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
        <div class="container">
            <div class="card w-full max-w-sm shrink-0">
                <form method="POST" class="form">
                    <p class="title">Login</p>
                    <p class="message">Please enter your email & password</p>
                    <div class="form-control">
                        <label>
                            <input class="input" type="email" name="email" placeholder="" required />
                            <span>Email</span>
                        </label>
                    </div>
                    <div class="form-control">
                        <label>
                            <div class="password-container">
                                <input id="password" class="input" type="password" name="password" placeholder="" required />
                                <span>Password</span>
                                <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </label>
                    </div>
                    <div class="flex-row">
                        <div>
                            <input type="radio">
                            <label>Remember Me</label>
                        </div>
                        <a href="../auth/forgot-password.php" class="link link-hover">
                            <span class="span">Forgot password?</span>
                        </a>
                    </div>
                    <button type="submit" class="submit">เข้าสู่ระบบ</button>
                    <p class="signin">Dont'have any acount? <a href="../auth/register.php" class="link link-hover"><span class="span">Sign In</span></a></p>
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