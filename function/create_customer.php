<?php
// create_customer.php

require_once '../config/config.php';
require_once 'functions.php';

// รับข้อมูลจากฟอร์ม
$name_customer = $_POST['name_customer'] ?? '';
$id_customer_type = $_POST['id_customer_type'] ?? ''; // เปลี่ยนจาก type_customer เป็น id_customer_type
$phone_customer = $_POST['phone_customer'] ?? '';
$status_customer = $_POST['status_customer'] ?? '';
$id_amphures = $_POST['id_amphures'] ?? '';
$id_tambons = $_POST['id_tambons'] ?? '';
$info_address = $_POST['info_address'] ?? ''; // ไม่บังคับ

// ตรวจสอบว่าข้อมูลทุกช่องถูกกรอกครบถ้วน (ยกเว้น info_address)
if (empty($name_customer) || empty($id_customer_type) || empty($phone_customer) || empty($status_customer) || empty($id_amphures) || empty($id_tambons)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลทุกช่องให้ครบถ้วน']);
    exit;
}

// ตรวจสอบว่าเบอร์โทรศัพท์มีรูปแบบที่ถูกต้อง (สามารถใส่ชื่อได้)
$phonePattern = '/^[0-9]{10}.*$/';
if (!preg_match($phonePattern, $phone_customer)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (สามารถใส่ชื่อได้)']);
    exit;
}

// ตรวจสอบว่าชื่อลูกค้าไม่ซ้ำกันในฐานข้อมูล
if (checkCustomerName($name_customer)) {
    echo json_encode(['success' => false, 'message' => 'ชื่อลูกค้านี้มีอยู่ในระบบแล้ว กรุณาใช้ชื่ออื่น']);
    exit;
}

// เพิ่มที่อยู่ใหม่ในตาราง address
$sql = "INSERT INTO address (id_amphures, id_tambons, info_address) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $id_amphures, $id_tambons, $info_address);

if ($stmt->execute()) {
    // หากสร้างที่อยู่สำเร็จ ให้รับ id_address ที่เพิ่งสร้าง
    $id_address = $stmt->insert_id;

    // เพิ่มลูกค้าใหม่โดยใช้ id_address ที่เพิ่งสร้าง
    $data = [
        'name_customer' => $name_customer,
        'id_customer_type' => $id_customer_type, // เปลี่ยนจาก type_customer เป็น id_customer_type
        'phone_customer' => $phone_customer,
        'status_customer' => $status_customer,
        'id_address' => $id_address
    ];

    if (createCustomer($data)) {
        echo json_encode(['success' => true, 'message' => 'เพิ่มลูกค้าสำเร็จ']);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มลูกค้า']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการเพิ่มที่อยู่']);
}
?>