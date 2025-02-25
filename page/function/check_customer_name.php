<?php
require_once '../config/config.php';
require_once 'functions.php';

$name_customer = $_GET['name_customer'] ?? '';
$exists = checkCustomerName($name_customer);

echo json_encode(['exists' => $exists]);