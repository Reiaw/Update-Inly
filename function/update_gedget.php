<?php
require_once '../config/config.php';
require_once 'functions.php';

$data = [
    'id_gedget' => $_POST['id_gedget'],
    'name_gedget' => $_POST['name_gedget'],
    'quantity_gedget' => $_POST['quantity_gedget'],
    'id_bill' => $_POST['id_bill'] // รับค่า id_bill จากฟอร์ม
];

if (updateGedget($data)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}