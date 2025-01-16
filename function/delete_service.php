<?php
require_once '../config/config.php';
require_once 'functions.php';

$id = $_GET['id'];
$sql = "DELETE FROM service_customer WHERE id_service = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>