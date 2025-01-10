<?php

// config.php - Create this file for shared configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ntdb');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'rattapoomputtasri@gmail.com');
define('SMTP_PASS', 'haev huay zmfx pwje');
define('OTP_EXPIRY_MINUTES', 3);
define('MAX_OTP_ATTEMPTS', 5);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>