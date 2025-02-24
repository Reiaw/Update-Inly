<?php
// update_customer.php

require_once '../config/config.php';
require_once 'functions.php';

$id_customer = $_GET['id_customer'] ?? '';
$name_customer = $_POST['name_customer'] ?? '';
$id_customer_type = $_POST['id_customer_type'] ?? ''; // เปลี่ยนจาก type_customer เป็น id_customer_type
$phone_customer = $_POST['phone_customer'] ?? '';
$status_customer = $_POST['status_customer'] ?? '';
$id_amphures = $_POST['id_amphures'] ?? '';
$id_tambons = $_POST['id_tambons'] ?? '';
$info_address = $_POST['info_address'] ?? ''; // ไม่บังคับ
$id_address = $_POST['id_address'] ?? '';

// ตรวจสอบว่าข้อมูลทุกช่องถูกกรอกครบถ้วน (ยกเว้น info_address)
if (empty($name_customer) || empty($id_customer_type)  || empty($status_customer) || empty($id_amphures) || empty($id_tambons) || empty($id_address)) {
    echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลทุกช่องให้ครบถ้วน']);
    exit;
}

// ตรวจสอบว่าเบอร์โทรศัพท์มีรูปแบบที่ถูกต้อง (สามารถใส่ชื่อได้)
$phonePattern = '/^[0-9]{10}.*$/';
// ตรวจสอบว่ามีการกรอกเบอร์โทรศัพท์หรือไม่
if (!empty($phone_customer)) {
    if (!preg_match($phonePattern, $phone_customer)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง (สามารถใส่ชื่อได้)']);
        exit;
    }
}

// ตรวจสอบว่าชื่อลูกค้าไม่ซ้ำกันในฐานข้อมูล (เฉพาะเมื่อมีการเปลี่ยนชื่อ)
$currentCustomer = getCustomerById($id_customer);
if ($currentCustomer['name_customer'] !== $name_customer && checkCustomerName($name_customer)) {
    echo json_encode(['success' => false, 'message' => 'ชื่อลูกค้านี้มีอยู่ในระบบแล้ว กรุณาใช้ชื่ออื่น']);
    exit;
}

// อัปเดตข้อมูลลูกค้า
$data = [
    'name_customer' => $name_customer,
    'id_customer_type' => $id_customer_type, // เปลี่ยนจาก type_customer เป็น id_customer_type
    'phone_customer' => $phone_customer,
    'status_customer' => $status_customer,
    'id_address' => $id_address,
    'id_amphures' => $id_amphures,
    'id_tambons' => $id_tambons,
    'info_address' => $info_address
];

if (updateCustomer($id_customer, $data)) {
    echo json_encode(['success' => true, 'message' => 'อัปเดตลูกค้าสำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปเดตลูกค้า']);
}
?>