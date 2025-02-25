<?php
require_once '../config/config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_customer_type = $_GET['id_customer_type'];
    $sql = "SELECT * FROM customer_types WHERE id_customer_type = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_customer_type);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(null);
    }
}