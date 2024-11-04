<?php
// เพิ่มฟังก์ชัน AJAX endpoint สำหรับดึงข้อมูลตัวอย่างการเบิก (preview_withdrawal.php)
session_start();
include('../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listproduct_id = $_POST['listproduct_id'];
    $withdraw_quantity = $_POST['withdraw_quantity'];
    $store_id = $_SESSION['store_id'];
    
    // ดึงข้อมูลสินค้าที่มีอยู่ทั้งหมดเรียงตามวันหมดอายุและสถานะ
    $query = "SELECT p.*, pi.product_name 
             FROM product p
             JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
             WHERE p.listproduct_id = ? 
             AND p.store_id = ?
             AND p.status IN ('nearing_expiration', 'in_stock')
             ORDER BY 
                CASE 
                    WHEN status = 'nearing_expiration' THEN 1
                    WHEN status = 'in_stock' THEN 2
                END,
                expiration_date ASC";
                
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $listproduct_id, $store_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $preview_items = array();
    $remaining = $withdraw_quantity;
    
    while ($product = $result->fetch_assoc()) {
        if ($remaining <= 0) break;
        
        $quantity_to_withdraw = min($product['quantity'], $remaining);
        $preview_items[] = array(
            'product_id' => $product['product_id'],
            'product_name' => $product['product_name'],
            'location' => $product['location'],
            'status' => $product['status'],
            'expiration_date' => $product['expiration_date'],
            'quantity_to_withdraw' => $quantity_to_withdraw,
            'available_quantity' => $product['quantity']
        );
        
        $remaining -= $quantity_to_withdraw;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'items' => $preview_items,
        'total_requested' => $withdraw_quantity,
        'total_available' => $withdraw_quantity - $remaining
    ]);
    exit;
}
?>