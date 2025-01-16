<?php
// get_customer.php

require_once  '../config/config.php';
require_once  'functions.php';

if (isset($_GET['id_customer'])) {
    $id_customer = $_GET['id_customer'];
    $customer = getCustomerById($id_customer);
    echo json_encode($customer);
}
?>