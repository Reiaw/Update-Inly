<?php
// import_excel.php

require_once '../config/config.php';
require_once 'functions.php';

// ตรวจสอบว่ามีไฟล์ถูกอัปโหลดหรือไม่ (สำหรับการนำเข้าข้อมูลจากไฟล์ Excel)
if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีไฟล์ถูกอัปโหลดหรือเกิดข้อผิดพลาดในการอัปโหลดไฟล์']);
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
        echo json_encode(['success' => false, 'message' => 'รูปแบบไฟล์ Excel ไม่ถูกต้อง']);
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
            echo json_encode(['success' => false, 'message' => 'ข้อมูลที่จำเป็นหายไปในไฟล์ Excel']);
            exit;
        }

        // ตรวจสอบรูปแบบเบอร์โทรศัพท์ (ต้องมีตัวเลข 9-10 หลัก)
        if (!preg_match('/^\D*\d{9,10}\D*$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง เบอร์โทรต้องมี 9-10 หลัก']);
            exit;
        }

        // ตรวจสอบค่าของ status ต้องเป็น "ใช้งาน" หรือ "ไม่ได้ใช้งาน" เท่านั้น
        if ($status !== 'ใช้งาน' && $status !== 'ไม่ได้ใช้งาน') {
            echo json_encode(['success' => false, 'message' => 'ค่าสถานะไม่ถูกต้อง ต้องเป็น "ใช้งาน" หรือ "ไม่ได้ใช้งาน" เท่านั้น']);
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

        // ค้นหา id_customer_type จากประเภทลูกค้า
        $customerTypeQuery = $conn->prepare("SELECT id_customer_type FROM customer_types WHERE type_customer = ?");
        $customerTypeQuery->bind_param("s", $type);
        $customerTypeQuery->execute();
        $customerTypeResult = $customerTypeQuery->get_result();
        $customerTypeData = $customerTypeResult->fetch_assoc();
        $id_customer_type = $customerTypeData['id_customer_type'] ?? null;

        // ถ้าไม่พบประเภทลูกค้าในฐานข้อมูล
        if (!$id_customer_type) {
            echo json_encode(['success' => false, 'message' => 'ประเภทลูกค้าไม่ถูกต้องในไฟล์ Excel']);
            exit;
        }

        // ดึง id_amphures และ id_tambons จากชื่ออำเภอและตำบล
        $amphureQuery = $conn->prepare("SELECT id_amphures FROM amphures WHERE name_amphures = ?");
        $amphureQuery->bind_param("s", $amphure);
        $amphureQuery->execute();
        $amphureResult = $amphureQuery->get_result();
        $amphureData = $amphureResult->fetch_assoc();
        $id_amphures = $amphureData['id_amphures'] ?? null;

        if (!$id_amphures) {
            echo json_encode(['success' => false, 'message' => 'อำเภอไม่ถูกต้องในไฟล์ Excel']);
            exit;
        }

        $tambonQuery = $conn->prepare("SELECT id_tambons FROM tambons WHERE name_tambons = ? AND id_amphures = ?");
        $tambonQuery->bind_param("si", $tambon, $id_amphures);
        $tambonQuery->execute();
        $tambonResult = $tambonQuery->get_result();
        $tambonData = $tambonResult->fetch_assoc();
        $id_tambons = $tambonData['id_tambons'] ?? null;

        if (!$id_tambons) {
            echo json_encode(['success' => false, 'message' => 'ตำบลไม่ถูกต้องในไฟล์ Excel']);
            exit;
        }

        // เพิ่มข้อมูลลงในตาราง address
        $addressQuery = $conn->prepare("INSERT INTO address (info_address, id_tambons, id_amphures) VALUES (?, ?, ?)");
        $addressQuery->bind_param("sii", $address, $id_tambons, $id_amphures);
        $addressQuery->execute();
        $id_address = $conn->insert_id;

        // เพิ่มข้อมูลลงในตาราง customers รองรับ id_customer_type
        $customerQuery = $conn->prepare("INSERT INTO customers (name_customer, phone_customer, status_customer, id_address, id_customer_type, create_at, update_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        $customerQuery->bind_param("sssii", $name, $phone, $status, $id_address, $id_customer_type);
        $customerQuery->execute();
    }

    echo json_encode(['success' => true, 'message' => 'นำเข้าข้อมูลสำเร็จ']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}