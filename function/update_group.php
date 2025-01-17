<?php
require_once '../config/config.php';
require_once '../function/functions.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['id_group'], $data['group_name'], $data['services'], $data['gedgets'])) {
    $success = updateGroup([
        'id_group' => $data['id_group'],
        'group_name' => $data['group_name'],
        'services' => $data['services'],
        'gedgets' => $data['gedgets']
    ]);

    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}
?>