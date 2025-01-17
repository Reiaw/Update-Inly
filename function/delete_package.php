<?php
require_once '../config/config.php';

$id_package = isset($_GET['id_package']) ? intval($_GET['id_package']) : 0;

if ($id_package > 0) {
    try {
        $conn->begin_transaction();

        // ลบข้อมูลในตาราง overide ที่เกี่ยวข้องกับ Product ของ Package นี้
        $sql = "DELETE o FROM overide o 
                JOIN product_list p ON o.id_product = p.id_product 
                WHERE p.id_package = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_package);
        $stmt->execute();

        // ลบข้อมูลในตาราง product_list ที่เกี่ยวข้องกับ Package นี้
        $sql = "DELETE FROM product_list WHERE id_package = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_package);
        $stmt->execute();

        // ลบข้อมูลในตาราง package_list
        $sql = "DELETE FROM package_list WHERE id_package = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_package);
        $stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Package ID']);
}