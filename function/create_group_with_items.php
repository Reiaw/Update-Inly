
<?php
require_once '../config/config.php';
require_once 'functions.php';

$data = json_decode(file_get_contents('php://input'), true);

if (createGroupWithItems($data)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}