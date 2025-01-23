<?php
session_start();
require_once '../config/config.php';
require_once 'functions.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}
// ลบการแจ้งเตือนที่เก่ากว่า 10 วัน
$sql_delete_old = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 10 DAY)";
$stmt_delete_old = $conn->prepare($sql_delete_old);
$stmt_delete_old->execute();

// ตรวจสอบสัญญาที่ใกล้หมดเวลา (ภายใน 60 วัน)
$sql = "SELECT COUNT(*) as near_expiry_count
        FROM bill_customer bc
        INNER JOIN customers c ON bc.id_customer = c.id_customer
        LEFT JOIN notifications n ON bc.id_bill = n.id_bill AND n.id_user = ?
        WHERE bc.end_date IS NOT NULL 
        AND bc.end_date != '0000-00-00'
        AND bc.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
        AND bc.contact_status != 'ยกเลิกสัญญา'
        AND bc.status_bill = 'ใช้งาน'
        AND (n.is_read = 0 OR n.is_read IS NULL)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$near_expiry_count = $result->fetch_assoc()['near_expiry_count'];

// Query รายละเอียดสัญญาที่ใกล้หมด
$sql_details = "SELECT 
            c.id_customer,
            c.name_customer,
            bc.id_bill,
            bc.end_date,
            bc.number_bill,
            bc.type_bill,
            n.id_notifications,
            n.is_read,
            DATEDIFF(bc.end_date, CURDATE()) as days_left
        FROM bill_customer bc
        INNER JOIN customers c ON bc.id_customer = c.id_customer
        LEFT JOIN notifications n ON bc.id_bill = n.id_bill AND n.id_user = ?
        WHERE bc.end_date IS NOT NULL 
        AND bc.end_date != '0000-00-00'
        AND bc.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
        AND bc.contact_status != 'ยกเลิกสัญญา'
        AND bc.status_bill = 'ใช้งาน'
        ORDER BY bc.end_date ASC";
$stmt_details = $conn->prepare($sql_details);
$stmt_details->bind_param("i", $user_id);
$stmt_details->execute();
$near_expiry_contracts = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);

// สร้างการแจ้งเตือนสำหรับสัญญาที่ใกล้หมดอายุ
foreach ($near_expiry_contracts as $contract) {
    $message = "สัญญาใกล้หมดอายุ: ลูกค้า {$contract['name_customer']} หมายเลขบิล {$contract['number_bill']} จะหมดอายุใน {$contract['days_left']} วัน";
    
    // ตรวจสอบว่ามีการแจ้งเตือนนี้แล้วหรือไม่
    $sql_check = "SELECT id_notifications FROM notifications WHERE id_user = ? AND id_bill = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $user_id, $contract['id_bill']);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        // ถ้ายังไม่มีแจ้งเตือนนี้ ให้สร้างใหม่
        $sql_insert = "INSERT INTO notifications (id_user, id_bill, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("iis", $user_id, $contract['id_bill'], $message);
        $stmt_insert->execute();
    }
}

echo json_encode([
    'near_expiry_count' => $near_expiry_count,
    'contracts' => $near_expiry_contracts
]);