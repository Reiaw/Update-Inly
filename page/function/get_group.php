<?php
require_once '../config/config.php';
require_once '../function/functions.php';

$id_group = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_group > 0) {
    $group = getGroupById($id_group);
    $groupDetails = getGroupDetails($id_group);

    $services = [];
    $gedgets = [];

    foreach ($groupDetails as $detail) {
        if ($detail['id_service']) {
            $services[] = $detail['id_service'];
        }
        if ($detail['id_gedget']) {
            $gedgets[] = $detail['id_gedget'];
        }
    }

    echo json_encode([
        'group' => $group,
        'services' => $services,
        'gedgets' => $gedgets
    ]);
} else {
    echo json_encode(['error' => 'Invalid group ID']);
}