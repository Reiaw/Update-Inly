<?php
require_once '../config/config.php';
require_once '../function/functions.php';

$id_group = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_group > 0) {
    $success = deleteGroup($id_group);
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid group ID']);
}