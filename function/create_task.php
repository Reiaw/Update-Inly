<?php
session_start();
require_once '../config/config.php';
require_once './functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $taskData = [
        'name_task' => $_POST['name_task'],
        'detail_task' => $_POST['detail_task'],
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'reminder_date' => !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : null
    ];
    
    $assignedUsers = isset($_POST['assigned_users']) ? $_POST['assigned_users'] : [];
    
    if (addTask($taskData, $assignedUsers)) {
        $_SESSION['success'] = "เพิ่มงานสำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการเพิ่มงาน";
    }
    
    header('Location: ../page/index.php');
    exit;
}
?>