<?php
session_start();
require_once '../config/config.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? null;

if (!$notification_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Notification ID is required']);
    exit;
}

// อัปเดตสถานะการแจ้งเตือนเป็นอ่านแล้ว (is_read = 1) และบันทึกเวลา read_at
$sql = "UPDATE notifications SET is_read = 1 WHERE id_notifications = ? AND id_user = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to update notification status']);
}