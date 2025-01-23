<?php
require_once '../config/config.php';
require_once 'functions.php';

$id = $_GET['id'];

// ตรวจสอบว่ามีข้อมูลที่เกี่ยวข้องในตาราง group_servicedetail หรือไม่
$sql_check_fk = "SELECT COUNT(*) AS count FROM group_servicedetail WHERE id_service = ?";
$stmt_check_fk = $conn->prepare($sql_check_fk);
$stmt_check_fk->bind_param("i", $id);
$stmt_check_fk->execute();
$result_check_fk = $stmt_check_fk->get_result();
$row_check_fk = $result_check_fk->fetch_assoc();

if ($row_check_fk['count'] > 0) {
    // หากมีข้อมูลที่เกี่ยวข้องใน group_servicedetail
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบข้อมูลได้ เนื่องจากมีข้อมูลที่เกี่ยวข้องอยู่ในกลุ่ม']);
    exit;
}

// ตรวจสอบว่ามีข้อมูลที่เกี่ยวข้องในตาราง package_list หรือไม่
$sql_check_package = "SELECT COUNT(*) AS count FROM package_list WHERE id_service = ? AND status_package = 'ใช้งาน'";
$stmt_check_package = $conn->prepare($sql_check_package);
$stmt_check_package->bind_param("i", $id);
$stmt_check_package->execute();
$result_check_package = $stmt_check_package->get_result();
$row_check_package = $result_check_package->fetch_assoc();

if ($row_check_package['count'] > 0) {
    // หากมีแพ็กเกจที่เกี่ยวข้อง
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบข้อมูลได้ เนื่องจากมีแพ็กเกจที่เกี่ยวข้องอยู่ในบริการนี้']);
    exit;
}

// หากไม่มีข้อมูลที่เกี่ยวข้อง ให้ทำการลบข้อมูล
$sql = "DELETE FROM service_customer WHERE id_service = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล']);
}
?>