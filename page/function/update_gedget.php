<?php
require_once '../config/config.php';
require_once 'functions.php';

$data = [
    'id_gedget' => $_POST['id_gedget'],
    'name_gedget' => $_POST['name_gedget'],
    'status_gedget' => $_POST['status_gedget'],
    'note' => $_POST['note'],
    'id_bill' => $_POST['id_bill']
];

if (updateGedget($data)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}