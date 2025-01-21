<?php
// delete_customer.php

require_once '../config/config.php';
require_once 'functions.php';

$id_customer = $_GET['id_customer'];

// ตรวจสอบว่ามีบิลที่เกี่ยวข้องกับลูกค้าหรือไม่
$check_bill_query = $conn->prepare("SELECT COUNT(*) as bill_count FROM bill_customer WHERE id_customer = ?");
$check_bill_query->bind_param("i", $id_customer);
$check_bill_query->execute();
$check_bill_result = $check_bill_query->get_result();
$bill_count = $check_bill_result->fetch_assoc()['bill_count'];

if ($bill_count > 0) {
    // ถ้ามีบิลที่เกี่ยวข้อง ให้ส่งข้อความแจ้งเตือนกลับ
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบลูกค้าได้ เนื่องจากมีบิลที่เกี่ยวข้อง']);
} else {
    // ถ้าไม่มีบิลที่เกี่ยวข้อง ให้ดำเนินการลบลูกค้า
    if (deleteCustomer($id_customer)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบลูกค้า']);
    }
}
?>