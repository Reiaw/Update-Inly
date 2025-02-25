<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['email'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id_bill'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id_bill = intval($_GET['id_bill']);

// Check for existing services
$check_sql = "SELECT COUNT(*) AS service_count FROM service_customer WHERE id_bill = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $id_bill);
$check_stmt->execute();
$result = $check_stmt->get_result();
$row = $result->fetch_assoc();

if ($row['service_count'] > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถลบบิลได้ เนื่องจากมีบริการที่เกี่ยวข้องอยู่'
    ]);
    exit;
}

// Delete bill
try {
    $delete_sql = "DELETE FROM bill_customer WHERE id_bill = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $id_bill);
    $delete_stmt->execute();

    if ($delete_stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'ลบบิลเรียบร้อยแล้ว'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'ไม่พบบิลที่ต้องการลบ'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}