<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once 'functions.php';

// รับข้อมูลจาก $_POST
$data = $_POST;

if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    if (createGedget($data)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create gedget']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>