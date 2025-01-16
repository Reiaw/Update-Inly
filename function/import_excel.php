<?php
// import_excel.php

require_once '../config/config.php';
require_once 'functions.php';

// ตรวจสอบว่ามีไฟล์ถูกอัปโหลดหรือไม่ (สำหรับการนำเข้าข้อมูลจากไฟล์ Excel)
if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or file upload error.']);
    exit;
}

// เรียกใช้ไลบรารี PhpSpreadsheet
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = $_FILES['excelFile']['tmp_name'];

try {
    // อ่านไฟล์ Excel
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // ตรวจสอบหัวคอลัมน์
    $header = array_shift($rows);
    $expectedHeader = [
        'Name', 'Type', 'Phone', 'Status', 'Address', 'Tambon', 'Amphure'
    ];

    if ($header !== $expectedHeader) {
        echo json_encode(['success' => false, 'message' => 'Invalid Excel format.']);
        exit;
    }

    // วนลูปข้อมูลใน Excel
    foreach ($rows as $row) {
        $name = $row[0]; // ชื่อลูกค้า (ไม่จำเป็นต้องกรอก)
        $type = $row[1];
        $phone = $row[2];
        $status = $row[3];
        $address = $row[4]; // info_address สามารถเป็น null ได้
        $tambon = $row[5];
        $amphure = $row[6];

        // ตรวจสอบคอลัมน์ที่จำเป็นต้องมีค่า (ไม่เป็น null)
        if (empty($type) || empty($phone) || empty($status) || empty($tambon) || empty($amphure)) {
            echo json_encode(['success' => false, 'message' => 'Required fields are missing in the Excel file.']);
            exit;
        }

        // ตรวจสอบรูปแบบเบอร์โทรศัพท์ (ต้องมีตัวเลข 9-10 หลัก)
        if (!preg_match('/^\D*\d{9,10}\D*$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Invalid phone number format in the Excel file. Phone must contain 9-10 digits.']);
            exit;
        }

        // ตรวจสอบค่าของ status ต้องเป็น "ใช้งาน" หรือ "ไม่ได้ใช้งาน" เท่านั้น
        if ($status !== 'ใช้งาน' && $status !== 'ไม่ได้ใช้งาน') {
            echo json_encode(['success' => false, 'message' => 'Invalid status value. Status must be either "ใช้งาน" or "ไม่ได้ใช้งาน".']);
            exit;
        }

        // ตรวจสอบชื่อลูกค้าซ้ำ
        $checkName = $conn->prepare("SELECT id_customer FROM customers WHERE name_customer = ?");
        $checkName->bind_param("s", $name); // ผูกพารามิเตอร์ชื่อลูกค้า
        $checkName->execute(); // ประมวลผลคำสั่ง SQL
        $checkName->store_result(); // บันทึกผลลัพธ์

        // ถ้าพบชื่อซ้ำ
        if ($checkName->num_rows > 0) {
            // ส่งข้อความแจ้งเตือนว่ามีข้อมูลซ้ำ
            echo json_encode([
                'success' => false,
                'message' => 'ชื่อลูกค้าซ้ำกับข้อมูลที่มีอยู่ ไม่สามารถนำเข้าข้อมูลได้',
                'duplicate' => true, // ส่งค่ากลับเพื่อระบุว่าพบข้อมูลซ้ำ
                'name' => $name // ส่งชื่อลูกค้าที่ซ้ำกลับไปด้วย
            ]);
            exit; // หยุดการทำงานและส่งข้อความกลับไปยังผู้ใช้
        }

        // ดึง id_amphures และ id_tambons จากชื่ออำเภอและตำบล
        $amphureQuery = $conn->prepare("SELECT id_amphures FROM amphures WHERE name_amphures = ?");
        $amphureQuery->bind_param("s", $amphure);
        $amphureQuery->execute();
        $amphureResult = $amphureQuery->get_result();
        $amphureData = $amphureResult->fetch_assoc();
        $id_amphures = $amphureData['id_amphures'] ?? null;

        if (!$id_amphures) {
            echo json_encode(['success' => false, 'message' => 'Invalid Amphure in the Excel file.']);
            exit;
        }

        $tambonQuery = $conn->prepare("SELECT id_tambons FROM tambons WHERE name_tambons = ? AND id_amphures = ?");
        $tambonQuery->bind_param("si", $tambon, $id_amphures);
        $tambonQuery->execute();
        $tambonResult = $tambonQuery->get_result();
        $tambonData = $tambonResult->fetch_assoc();
        $id_tambons = $tambonData['id_tambons'] ?? null;

        if (!$id_tambons) {
            echo json_encode(['success' => false, 'message' => 'Invalid Tambon in the Excel file.']);
            exit;
        }

        // เพิ่มข้อมูลลงในตาราง address
        $addressQuery = $conn->prepare("INSERT INTO address (info_address, id_tambons, id_amphures) VALUES (?, ?, ?)");
        $addressQuery->bind_param("sii", $address, $id_tambons, $id_amphures);
        $addressQuery->execute();
        $id_address = $conn->insert_id;

        // เพิ่มข้อมูลลงในตาราง customers
        $customerQuery = $conn->prepare("INSERT INTO customers (name_customer, type_customer, phone_customer, status_customer, id_address, create_at, update_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $customerQuery->bind_param("ssssi", $name, $type, $phone, $status, $id_address);
        $customerQuery->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Data imported successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}