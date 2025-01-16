<?php
// delete_customer.php

require_once  '../config/config.php';
require_once  'functions.php';

$id_customer = $_GET['id_customer'];

if (deleteCustomer($id_customer)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>