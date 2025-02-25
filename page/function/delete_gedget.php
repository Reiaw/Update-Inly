<?php
require_once '../config/config.php';
require_once 'functions.php';

$id_gedget = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_gedget > 0) {
    // ตรวจสอบว่ามีข้อมูลที่เกี่ยวข้องในตาราง group_servicedetail หรือไม่
    $sql_check_fk = "SELECT COUNT(*) AS count FROM group_servicedetail WHERE id_gedget = ?";
    $stmt_check_fk = $conn->prepare($sql_check_fk);
    $stmt_check_fk->bind_param("i", $id_gedget);
    $stmt_check_fk->execute();
    $result_check_fk = $stmt_check_fk->get_result();
    $row_check_fk = $result_check_fk->fetch_assoc();

    if ($row_check_fk['count'] > 0) {
        // หากมีข้อมูลที่เกี่ยวข้อง แจ้งเตือนและไม่ทำการลบ
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบข้อมูลได้ เนื่องจากมีข้อมูลที่เกี่ยวข้องอยู่ในกลุ่ม']);
    } else {
        // หากไม่มีข้อมูลที่เกี่ยวข้อง ทำการลบ gedget
        if (deleteGedget($id_gedget)) {
            echo json_encode(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID ไม่ถูกต้อง']);
}