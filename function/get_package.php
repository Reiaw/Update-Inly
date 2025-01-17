<?php
require_once '../config/config.php';

$id_package = isset($_GET['id_package']) ? intval($_GET['id_package']) : 0;

if ($id_package > 0) {
    $sql = "SELECT * FROM package_list WHERE id_package = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_package);
    $stmt->execute();
    $result = $stmt->get_result();
    $package = $result->fetch_assoc();

    echo json_encode($package);
} else {
    echo json_encode([]);
}