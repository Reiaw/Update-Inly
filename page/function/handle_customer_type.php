<?php
require_once '../config/config.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    $response = ['success' => false, 'message' => ''];

    switch ($action) {
        case 'create':
            if (empty($_POST['type_customer'])) {
                throw new Exception('กรุณากรอกประเภทลูกค้า');
            }
            if (createCustomerType($_POST['type_customer'])) {
                $response['success'] = true;
                $response['message'] = 'เพิ่มประเภทลูกค้าสำเร็จ';
            } else {
                throw new Exception('ไม่สามารถเพิ่มประเภทลูกค้าได้');
            }
            break;

        case 'update':
            if (empty($_POST['id_customer_type']) || empty($_POST['type_customer'])) {
                throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
            }
            
            // Check if customer type has associated customers before update
            $customerCount = getCustomerCountByType($_POST['id_customer_type']);
            if ($customerCount > 0) {
                $response['success'] = true;
                $response['message'] = 'อัปเดตประเภทลูกค้าสำเร็จ';
            }
            
            if (updateCustomerType($_POST['id_customer_type'], $_POST['type_customer'])) {
                $response['success'] = true;
                $response['message'] = 'อัปเดตประเภทลูกค้าสำเร็จ';
            } else {
                throw new Exception('ไม่สามารถอัปเดตประเภทลูกค้าได้');
            }
            break;

        case 'delete':
            if (empty($_GET['id_customer_type'])) {
                throw new Exception('ไม่พบ ID ประเภทลูกค้า');
            }

            // Check if customer type has associated customers before deletion
            $customerCount = getCustomerCountByType($_GET['id_customer_type']);
            if ($customerCount > 0) {
                throw new Exception("ไม่สามารถลบประเภทลูกค้านี้ได้ เนื่องจากมีลูกค้า {$customerCount} รายการที่ใช้ประเภทนี้อยู่");
            }

            if (deleteCustomerType($_GET['id_customer_type'])) {
                $response['success'] = true;
                $response['message'] = 'ลบประเภทลูกค้าสำเร็จ';
            } else {
                throw new Exception('ไม่สามารถลบประเภทลูกค้าได้');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);