<?php
session_start();
require_once '../config/config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $task_id = intval($data['task_id']);

    // ตรวจสอบว่า user_id ตรงกับ user_id ใน task หรือไม่
    $sql = "SELECT user_id FROM task WHERE id_task = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $task = $result->fetch_assoc();

    if ($task['user_id'] == $_SESSION['user_id']) {
        // ลบ task และข้อมูลที่เกี่ยวข้อง
        $conn->begin_transaction();
        try {
            // ลบข้อมูลที่เกี่ยวข้องในตาราง notifications
            $sql = "DELETE FROM notifications WHERE task_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
    
            // ลบ task จากตาราง task_group
            $sql = "DELETE FROM task_group WHERE task_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
    
            // ลบ task จากตาราง task
            $sql = "DELETE FROM task WHERE id_task = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $task_id);
            $stmt->execute();
    
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'คุณไม่มีสิทธิ์ลบ task นี้']);
    }
}
?>