<?php
require_once '../config/config.php';
require_once 'functions.php';

$data = $_POST;
$sql = "INSERT INTO service_customer (code_service, type_service, type_gadget, status_service, id_bill, create_at, update_at) 
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssi", $data['code_service'], $data['type_service'], $data['type_gadget'], $data['status_service'], $data['id_bill']);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>