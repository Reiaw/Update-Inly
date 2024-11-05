<?php
// process_issue.php
session_start();
include('../../config/db.php');
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Get form data
        $order_id = $_POST['order_id'];
        $resolution_type = $_POST['resolution_type'];
        $resolution_description = $_POST['resolution_description'];
        
        // Handle image upload
        $resolution_image = null;
        if (isset($_FILES['resolution_image']) && $_FILES['resolution_image']['error'] === 0) {
            $target_dir = "../../upload/resolution_images/";
            // สร้างโฟลเดอร์ถ้ายังไม่มี
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['resolution_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $target_file = $target_dir . $new_filename;
            // ตรวจสอบประเภทไฟล์
            $allowed_types = ['jpg', 'jpeg', 'png'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                throw new Exception("Invalid file type. Only JPG, JPEG and PNG are allowed.");
            }
            
            if (move_uploaded_file($_FILES['resolution_image']['tmp_name'], $target_file)) {
                $resolution_image = $new_filename;
            }
        }        
        // ดึง store_id จาก order_id
        $store_id_query = $conn->prepare("SELECT store_id FROM orders WHERE order_id = ?");
        $store_id_query->bind_param("i", $order_id);
        $store_id_query->execute();
        $store_id_result = $store_id_query->get_result();
        if ($store_id_result->num_rows > 0) {
            $store_id_row = $store_id_result->fetch_assoc();
            $store_id = $store_id_row['store_id'];
        } else {
            throw new Exception("Store ID not found for the specified order.");
        }


        // Insert into resolution_orders table
        $insert_resolution = $conn->prepare("INSERT INTO resolution_orders (order_id, resolution_info, 
                                                    resolution_type, resolution_image, resolution_date) 
                                      VALUES (?, ?, ?, ?, NOW())");
        $insert_resolution->bind_param("isss", $order_id, $resolution_description, 
                                            $resolution_type, $resolution_image);
        if (!$insert_resolution->execute()) {
            throw new Exception("Failed to insert resolution record.");
        }
        // Insert notification into notiflyreport table
        $notiflyreport_type = 'resolve_order';
        $insert_notification = $conn->prepare("INSERT INTO notiflyreport (user_id, order_id, notiflyreport_type, store_id) 
                                            VALUES (?, ?, ?, ?)");
        $insert_notification->bind_param("iisi", $user_id, $order_id, $notiflyreport_type, $store_id);
        if (!$insert_notification->execute()) {
            throw new Exception("Failed to insert notification record.");
        }

        // Update order status based on resolution type
        $new_order_status = ($resolution_type === 'refund') ? 'refund' : 'return_shipped';
        $update_order = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
        $update_order->bind_param("si", $new_order_status, $order_id);
        if (!$update_order->execute()) {
            throw new Exception("Failed to update order status.");
        }

        // Update all products associated with this order
        $update_products = $conn->prepare("UPDATE product p 
                                         JOIN detail_orders do ON p.detail_order_id = do.detail_order_id 
                                         SET p.status = ? 
                                         WHERE do.order_id = ?");
        $new_product_status = ($resolution_type === 'refund') ? 'cancel' : 'check';
        $update_products->bind_param("si", $new_product_status, $order_id);
        if (!$update_products->execute()) {
            throw new Exception("Failed to update product statuses.");
        }

        $conn->commit();
        $_SESSION['success'] = "Resolution process completed successfully.";
        header("Location: order_details.php?id=" . $order_id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: order_details.php?id=" . $order_id);
        exit;
    }
}
?>