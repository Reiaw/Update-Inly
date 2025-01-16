<?php
require_once '../config/config.php';
require_once 'functions.php';

$data = [
    'name_gedget' => $_POST['name_gedget'],
    'quantity_gedget' => $_POST['quantity_gedget']
];

if (createGedget($data)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}