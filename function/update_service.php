<?php
require_once '../config/config.php';
require_once 'functions.php';

$data = $_POST;
$sql = "UPDATE service_customer SET code_service = ?, type_service = ?, type_gadget = ?, status_service = ?, update_at = NOW() WHERE id_service = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $data['code_service'], $data['type_service'], $data['type_gadget'], $data['status_service'], $data['id_service']);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>