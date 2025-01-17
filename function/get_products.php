<?php
require_once '../config/config.php';
require_once 'functions.php';

$id_package = isset($_GET['id_package']) ? intval($_GET['id_package']) : 0;

if ($id_package > 0) {
    $sql = "SELECT p.*, o.mainpackage_price, o.ict_price, o.all_price, o.info_overide 
            FROM product_list p 
            LEFT JOIN overide o ON p.id_product = o.id_product 
            WHERE p.id_package = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_package);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($products);
} else {
    echo json_encode([]);
}