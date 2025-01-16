<?php
require_once '../config/config.php';
require_once 'functions.php';

$id_gedget = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_gedget > 0 && deleteGedget($id_gedget)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}