<?php
session_start();
require_once('../../config/db.php');
require_once('./check_product.php');

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['role'])) {
    header('Location: ../../auth/login.php');
    exit();
}

try {
    // ตรวจสอบสินค้าที่มี status = 'check'
    $query = "SELECT product_id FROM product WHERE status IN ('in_stock', 'nearing_expiration')";
    $result = $conn->query($query);

    $all_notifications = [];
    
    while ($row = $result->fetch_assoc()) {
        $notifications = checkProductStatus($conn, $row['product_id']);
        if ($notifications) {
            $all_notifications = array_merge($all_notifications, $notifications);
        }
    }

    // ส่งผลลัพธ์กลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'notifications' => $all_notifications
    ]);

} catch (Exception $e) {
    // ส่งข้อผิดพลาดกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();