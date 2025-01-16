<?php
require_once '../config/config.php';
require_once 'functions.php';

if (isset($_GET['id_bill'])) {
    $id_bill = $_GET['id_bill'];

    // ดึงข้อมูลบิล
    $sql = "SELECT * FROM bill_customer WHERE id_bill = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bill);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill = $result->fetch_assoc();

    // ดึงข้อมูลบริการ
    $sql = "SELECT * FROM service_customer WHERE id_bill = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bill);
    $stmt->execute();
    $result = $stmt->get_result();
    $services = $result->fetch_all(MYSQLI_ASSOC);

    // ส่งข้อมูลกลับเป็น JSON
    echo json_encode(['bill' => $bill, 'services' => $services]);
}