<?php
require_once  '../vendor/autoload.php';
require_once  '../config/config.php';

function getAmphures() {
    global $conn;
    $sql = "SELECT * FROM amphures";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getTambonsByAmphure($id_amphures) {
    global $conn;
    $sql = "SELECT * FROM tambons WHERE id_amphures = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_amphures);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getCustomerById($id_customer) {
    global $conn;
    $sql = "SELECT c.*, a.info_address, a.id_amphures, a.id_tambons, am.name_amphures, t.name_tambons 
            FROM customers c 
            JOIN address a ON c.id_address = a.id_address 
            JOIN amphures am ON a.id_amphures = am.id_amphures 
            JOIN tambons t ON a.id_tambons = t.id_tambons 
            WHERE c.id_customer = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_customer);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function createCustomer($data) {
    global $conn;

    // ตรวจสอบว่า id_address มีอยู่ในตาราง address หรือไม่
    $checkAddressSql = "SELECT id_address FROM address WHERE id_address = ?";
    $checkStmt = $conn->prepare($checkAddressSql);
    $checkStmt->bind_param("i", $data['id_address']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        throw new Exception("Invalid id_address: Address does not exist.");
    }

    // เพิ่มข้อมูลลูกค้า
    $sql = "INSERT INTO customers (name_customer, type_customer, phone_customer, status_customer, id_address, create_at, update_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $data['name_customer'], $data['type_customer'], $data['phone_customer'], $data['status_customer'], $data['id_address']);
    return $stmt->execute();
}

function updateCustomer($id_customer, $data) {
    global $conn;

    // อัปเดตข้อมูลในตาราง customers
    $sql = "UPDATE customers SET name_customer = ?, type_customer = ?, phone_customer = ?, status_customer = ?, id_address = ?, update_at = NOW() WHERE id_customer = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $data['name_customer'], $data['type_customer'], $data['phone_customer'], $data['status_customer'], $data['id_address'], $id_customer);
    $stmt->execute();

    // อัปเดตข้อมูลในตาราง address
    $sql = "UPDATE address SET id_amphures = ?, id_tambons = ?, info_address = ? WHERE id_address = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisi", $data['id_amphures'], $data['id_tambons'], $data['info_address'], $data['id_address']);
    return $stmt->execute();
}

function deleteCustomer($id_customer) {
    global $conn;

    // ดึง id_address ของลูกค้าที่จะลบ
    $sql = "SELECT id_address FROM customers WHERE id_customer = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_customer);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Customer not found.");
    }

    $customer = $result->fetch_assoc();
    $id_address = $customer['id_address'];

    // ลบข้อมูลลูกค้าในตาราง customers
    $sql = "DELETE FROM customers WHERE id_customer = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_customer);
    $stmt->execute();

    // ลบข้อมูลที่อยู่ในตาราง address
    $sql = "DELETE FROM address WHERE id_address = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_address);
    return $stmt->execute();
}

function setupMailer() {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->setFrom(SMTP_USER, 'Send OTP');
    return $mail;
}

function sendOTP($email, $otp) {
    $mail = setupMailer();
    $mail->addAddress($email);
    $mail->Subject = 'OTP for Registration';
    $mail->Body = "Your OTP is: $otp";
    return $mail->send();
}

function generateOTP() {
    return rand(100000, 999999); // สร้าง OTP 6 หลัก
}

function verifyOTP($email, $otp) {
    global $conn;
    $stmt = $conn->prepare("SELECT otp, otp_expiry FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if ($user['otp'] == $otp && strtotime($user['otp_expiry']) > time()) {
            return true;
        }
    }
    return false;
}

function resetOTPAndRedirect($conn, $email) {
    // Reset OTP attempts, set OTP to null
    $stmt = $conn->prepare("UPDATE users SET name = NULL, password = NULL, otp_attempts = 0, otp = NULL, otp_expiry = NULL WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Redirect to register
    echo "<script>alert('คุณใช้ OTP เกินจำนวนครั้งที่กำหนด กรุณาลงทะเบียนใหม่'); window.location.href = 'register.php';</script>";
    exit;
}

function checkCustomerName($name_customer) {
    global $conn;
    $sql = "SELECT id_customer FROM customers WHERE name_customer = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $name_customer);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}
function createService($data) {
    global $conn;
    $sql = "INSERT INTO service_customer (code_service, type_service, type_gadget, status_service, id_bill, create_at, update_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $data['code_service'], $data['type_service'], $data['type_gadget'], $data['status_service'], $data['id_bill']);
    return $stmt->execute();
}

function updateService($data) {
    global $conn;
    $sql = "UPDATE service_customer SET code_service = ?, type_service = ?, type_gadget = ?, status_service = ?, update_at = NOW() WHERE id_service = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $data['code_service'], $data['type_service'], $data['type_gadget'], $data['status_service'], $data['id_service']);
    return $stmt->execute();
}

function deleteService($id_service) {
    global $conn;
    $sql = "DELETE FROM service_customer WHERE id_service = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_service);
    return $stmt->execute();
}
function createGedget($data) {
    global $conn;
    $sql = "INSERT INTO gedget (name_gedget, quantity_gedget) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $data['name_gedget'], $data['quantity_gedget']);
    return $stmt->execute();
}

function updateGedget($data) {
    global $conn;
    $sql = "UPDATE gedget SET name_gedget = ?, quantity_gedget = ? WHERE id_gedget = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $data['name_gedget'], $data['quantity_gedget'], $data['id_gedget']);
    return $stmt->execute();
}

function deleteGedget($id_gedget) {
    global $conn;
    $sql = "DELETE FROM gedget WHERE id_gedget = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_gedget);
    return $stmt->execute();
}

function getGedgetById($id_gedget) {
    global $conn;
    $sql = "SELECT * FROM gedget WHERE id_gedget = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_gedget);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

?>