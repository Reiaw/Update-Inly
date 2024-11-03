<?php
// issue_handler.php
session_start();
include('../../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    // Handle issue submission
    if (isset($_POST['action']) && $_POST['action'] === 'submit_issue') {
        try {
            // Validate inputs
            if (!isset($_POST['order_id']) || !isset($_POST['product_id']) || 
                !isset($_POST['issue_type']) || !isset($_POST['issue_description'])) {
                throw new Exception('Missing required fields');
            }

            $order_id = intval($_POST['order_id']);
            $product_id = intval($_POST['product_id']);
            $issue_type = $_POST['issue_type'];
            $issue_description = $_POST['issue_description'];
            $issue_image_path = null;

            // Check if product already has issue status
            $checkProductStatusSql = "SELECT status FROM product WHERE product_id = ?";
            $stmt = $conn->prepare($checkProductStatusSql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $productStatus = $result->fetch_assoc()['status'];

            if ($productStatus === 'issue') {
                throw new Exception('This product is already reported.');
            }

            // Handle image upload
            if (isset($_FILES['issue_image']) && $_FILES['issue_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../upload/issue_pic/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Generate unique filename
                $file_extension = strtolower(pathinfo($_FILES['issue_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.');
                }

                // Check file size (limit to 5MB)
                if ($_FILES['issue_image']['size'] > 5 * 1024 * 1024) {
                    throw new Exception('File size too large. Maximum size is 5MB.');
                }

                // Set full path for the uploaded file
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                // Move uploaded file
                if (!move_uploaded_file($_FILES['issue_image']['tmp_name'], $upload_path)) {
                    throw new Exception('Failed to upload image');
                }

                // Store relative path in database
                $issue_image_path = 'upload/issue_pic/' . $new_filename;
            }

            // Begin transaction
            $conn->begin_transaction();

            // Define the SQL query for inserting issue
            $sql = "INSERT INTO issue_orders (order_id, product_id, issue_type, issue_description, issue_image,report_date) 
                    VALUES (?, ?, ?, ?, ?, NOW())";

            // Insert issue into database
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisss", $order_id, $product_id, $issue_type, $issue_description, $issue_image_path);
        
            if (!$stmt->execute()) {
                throw new Exception('Failed to save issue');
            }

            // Update product status to 'issue' in product table
            $updateProductStatusSql = "UPDATE product SET status = 'issue' WHERE product_id = ?";
            $stmt = $conn->prepare($updateProductStatusSql);
            $stmt->bind_param("i", $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update product status');
            }
            // Commit transaction
            $conn->commit();
            
            $response['success'] = true;
            $response['message'] = 'Issue reported successfully';
            
        } catch (Exception $e) {
            // Rollback transaction if there's an error
            
            // Delete uploaded file if it exists and there was an error
            if (isset($upload_path) && file_exists($upload_path)) {
                unlink($upload_path);
            }
            
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    }
    
    // Handle order status update
    else if (isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
        try {
            if (!isset($_POST['order_id'])) {
                throw new Exception('Order ID is required');
            }

            $order_id = intval($_POST['order_id']);

            // Begin transaction
            $conn->begin_transaction();

            // Check if there are any issues reported
            $checkIssuesSql = "SELECT COUNT(*) as issue_count FROM issue_orders WHERE order_id = ?";
            $stmt = $conn->prepare($checkIssuesSql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $issueCount = $result->fetch_assoc()['issue_count'];

            if ($issueCount === 0) {
                throw new Exception('At least one issue must be reported before updating status');
            }

            // Update order status to 'issue'
            $updateSql = "UPDATE orders SET order_status = 'issue' WHERE order_id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("i", $order_id);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to update order status');
            }

            // Commit transaction
            $conn->commit();

            $response['success'] = true;
            $response['message'] = 'Order status updated successfully';
            
        } catch (Exception $e) {
            
            $response['message'] = 'Error: ' . $e->getMessage();
        }
    }

    echo json_encode($response);
    exit();
}
?>