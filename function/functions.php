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

    // ตรวจสอบว่าชื่อลูกค้ามีอยู่แล้วหรือไม่
    if (checkCustomerName($data['name_customer'])) {
        throw new Exception("ชื่อลูกค้ามีอยู่ในระบบแล้ว");
    }

    // เพิ่มข้อมูลลูกค้า
    $sql = "INSERT INTO customers (name_customer, type_customer, phone_customer, status_customer, id_address, create_at, update_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $data['name_customer'], $data['type_customer'], $data['phone_customer'], $data['status_customer'], $data['id_address']);
    return $stmt->execute();
}

function updateCustomer($id_customer, $data) {
    global $conn;

    // ตรวจสอบว่าชื่อลูกค้ามีอยู่แล้วหรือไม่ (ยกเว้นลูกค้าที่กำลังแก้ไข)
    $existingCustomer = getCustomerById($id_customer);
    if ($existingCustomer['name_customer'] !== $data['name_customer'] && checkCustomerName($data['name_customer'])) {
        throw new Exception("ชื่อลูกค้ามีอยู่ในระบบแล้ว");
    }

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

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($data['name_gedget']) || empty($data['id_bill']) || empty($data['create_at']) || empty($data['quantity_gedget'])) {
        throw new Exception("กรุณากรอกข้อมูลให้ครบถ้วน");
    }

    $status_gedget = 'ใช้งาน'; // กำหนดให้เป็น 'ใช้งาน' โดยอัตโนมัติ
    $note = $data['note'] ?? null; // รับค่า note จากฟอร์ม (ถ้ามี)
    $quantity_gedget = $data['quantity_gedget']; // จำนวน gedget ที่ต้องการเพิ่ม

    // เพิ่มข้อมูล Gedget ตามจำนวนที่ระบุ
    for ($i = 1; $i <= $quantity_gedget; $i++) {
        $gedget_name = $data['name_gedget'] . " ตัวที่ " . $i; // เพิ่มลำดับที่ต่อท้ายชื่อ
        $sql = "INSERT INTO gedget (name_gedget, id_bill, create_at, status_gedget, note) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisss", $gedget_name, $data['id_bill'], $data['create_at'], $status_gedget, $note);
        $stmt->execute();
    }

    return true;
}

function updateGedget($data) {
    global $conn;
    $sql = "UPDATE gedget SET name_gedget = ?, status_gedget = ?, note = ? WHERE id_gedget = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $data['name_gedget'], $data['status_gedget'], $data['note'], $data['id_gedget']);
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

function createGroupWithItems($data) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // สร้างกลุ่ม
        $sql = "INSERT INTO group_service (id_bill, group_name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $data['id_bill'], $data['group_name']);
        $stmt->execute();
        $id_group = $stmt->insert_id;

        // เพิ่มบริการเข้าไปในกลุ่ม
        if (!empty($data['services'])) {
            $sql = "INSERT INTO group_servicedetail (id_group, id_service) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            foreach ($data['services'] as $id_service) {
                $stmt->bind_param("ii", $id_group, $id_service);
                $stmt->execute();
            }
        }

        // เพิ่มอุปกรณ์เข้าไปในกลุ่ม
        if (!empty($data['gedgets'])) {
            $sql = "INSERT INTO group_servicedetail (id_group, id_gedget) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            foreach ($data['gedgets'] as $id_gedget) {
                $stmt->bind_param("ii", $id_group, $id_gedget);
                $stmt->execute();
            }
        }

        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error in createGroupWithItems: " . $e->getMessage()); // บันทึกข้อผิดพลาด
        return false;
    }
}

function updateGroup($data) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // อัปเดตชื่อกลุ่ม
        $sql = "UPDATE group_service SET group_name = ? WHERE id_group = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $data['group_name'], $data['id_group']);
        $stmt->execute();
        
        // ลบรายการบริการและอุปกรณ์เดิมในกลุ่ม
        $sql = "DELETE FROM group_servicedetail WHERE id_group = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $data['id_group']);
        $stmt->execute();
        
        // เพิ่มบริการใหม่เข้าไปในกลุ่ม
        if (!empty($data['services'])) {
            $sql = "INSERT INTO group_servicedetail (id_group, id_service) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            foreach ($data['services'] as $id_service) {
                $stmt->bind_param("ii", $data['id_group'], $id_service);
                $stmt->execute();
            }
        }
        
        // เพิ่มอุปกรณ์ใหม่เข้าไปในกลุ่ม
        if (!empty($data['gedgets'])) {
            $sql = "INSERT INTO group_servicedetail (id_group, id_gedget) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            foreach ($data['gedgets'] as $id_gedget) {
                $stmt->bind_param("ii", $data['id_group'], $id_gedget);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function deleteGroup($id_group) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // ลบรายการบริการและอุปกรณ์ในกลุ่ม
        $sql = "DELETE FROM group_servicedetail WHERE id_group = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_group);
        $stmt->execute();
        
        // ลบกลุ่ม
        $sql = "DELETE FROM group_service WHERE id_group = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_group);
        $stmt->execute();
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}

function getGroupById($id_group) {
    global $conn;
    $sql = "SELECT * FROM group_service WHERE id_group = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_group);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getGroupDetails($id_group) {
    global $conn;
    $sql = "SELECT * FROM group_servicedetail WHERE id_group = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_group);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
function updatePackage($id_package, $data) {
    global $conn;
    $sql = "UPDATE package_list SET name_package = ?, info_package = ?, update_at = NOW() WHERE id_package = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $data['name_package'], $data['info_package'], $id_package);
    return $stmt->execute();
}

function deletePackage($id_package) {
    global $conn;
    $sql = "DELETE FROM package_list WHERE id_package = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_package);
    return $stmt->execute();
}

function getPackageById($id_package) {
    global $conn;
    $sql = "SELECT * FROM package_list WHERE id_package = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_package);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function updateProduct($id_product, $data) {
    global $conn;
    $sql = "UPDATE product_list SET name_product = ?, info_product = ?, update_at = NOW() WHERE id_product = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $data['name_product'], $data['info_product'], $id_product);
    return $stmt->execute();
}

function deleteProduct($id_product) {
    global $conn;
    $sql = "DELETE FROM product_list WHERE id_product = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_product);
    return $stmt->execute();
}

function getProductsByPackage($id_package) {
    global $conn;
    $sql = "SELECT * FROM product_list WHERE id_package = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_package);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getPackagesByServiceId($id_service) {
    global $conn;
    $sql = "SELECT * FROM package_list WHERE id_service = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_service);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getProductsByPackageId($id_package) {
    global $conn;
    $sql = "SELECT * FROM product_list WHERE id_package = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_package);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
function getAllUsers() {
    global $conn;
    $sql = "SELECT id, name FROM users WHERE verify = 1";
    $result = $conn->query($sql);
    $users = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    return $users;
}

// Add new task function
function addTask($taskData, $assignedUsers) {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert task
        $sql = "INSERT INTO task (name_task, detail_task, start_date, end_date, user_id, reminder_date) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", 
            $taskData['name_task'],
            $taskData['detail_task'],
            $taskData['start_date'],
            $taskData['end_date'],
            $_SESSION['user_id'], // Current user as creator
            $taskData['reminder_date']
        );
        $stmt->execute();
        $taskId = $conn->insert_id;
        
        // Add all assigned users to the task_group table
        foreach ($assignedUsers as $userId) {
            $sql2 = "INSERT INTO task_group (task_id, user_id) VALUES (?, ?)";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("ii", $taskId, $userId);
            $stmt2->execute();
        }
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        return false;
    }
}
?>