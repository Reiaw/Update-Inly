<?php
// get_tambons.php

require_once '../config/config.php';
require_once 'functions.php';

if (isset($_GET['id_amphures'])) {
    $id_amphures = $_GET['id_amphures'];
    $query = $conn->prepare("SELECT * FROM tambons WHERE id_amphures = ?");
    $query->bind_param("i", $id_amphures);
    $query->execute();
    $result = $query->get_result();
    $tambons = $result->fetch_all(MYSQLI_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($tambons);
}