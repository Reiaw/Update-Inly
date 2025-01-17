<?php
require_once '../config/config.php';
require_once 'functions.php';

header('Content-Type: application/json');

$id_package = isset($_GET['id_package']) ? intval($_GET['id_package']) : 0;

if ($id_package > 0) {
    $products = getProductsByPackageId($id_package);
    echo json_encode($products);
} else {
    echo json_encode([]);
}