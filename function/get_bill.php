<?php
require_once '../config/config.php';

$id_bill = $_GET['id_bill'] ?? 0;

$sql = "SELECT * FROM bill_customer WHERE id_bill = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_bill);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $bill = $result->fetch_assoc();
    echo json_encode($bill);
} else {
    echo json_encode(['error' => 'ไม่พบข้อมูลบิล']);
}
exit;
?>