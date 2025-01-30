<?php
header('Content-Type: application/json');
session_start();

require_once '../config/config.php';
require_once 'functions.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$result = checkNearExpiry($conn, $user_id);
echo json_encode($result);
function checkNearExpiry($conn, $user_id) {
    try {
        // Delete old notifications
        $sql_delete_old = "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 10 DAY)";
        $stmt_delete_old = $conn->prepare($sql_delete_old);
        $stmt_delete_old->execute();

        // Get near expiry contracts
        $sql = "SELECT COUNT(*) as near_expiry_count
                FROM bill_customer bc
                INNER JOIN customers c ON bc.id_customer = c.id_customer
                LEFT JOIN notifications n ON bc.id_bill = n.id_bill AND n.id_user = ?
                WHERE bc.end_date IS NOT NULL 
                AND bc.end_date != '0000-00-00'
                AND bc.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                AND bc.contact_status != 'ยกเลิกสัญญา'
                AND bc.status_bill = 'ใช้งาน'
                AND (n.is_read = 0 OR n.is_read IS NULL)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $near_expiry_count = $result->fetch_assoc()['near_expiry_count'];

        // Get contract details
        $sql_details = "SELECT 
                    c.id_customer,
                    c.name_customer,
                    bc.id_bill,
                    bc.end_date,
                    bc.number_bill,
                    bc.type_bill,
                    n.id_notifications,
                    n.is_read,
                    DATEDIFF(bc.end_date, CURDATE()) as days_remaining
                FROM bill_customer bc
                INNER JOIN customers c ON bc.id_customer = c.id_customer
                LEFT JOIN notifications n ON bc.id_bill = n.id_bill AND n.id_user = ?
                WHERE bc.end_date IS NOT NULL 
                AND bc.end_date != '0000-00-00'
                AND bc.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)
                AND bc.contact_status != 'ยกเลิกสัญญา'
                AND bc.status_bill = 'ใช้งาน'
                ORDER BY bc.end_date ASC";
        
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bind_param("i", $user_id);
        $stmt_details->execute();
        $near_expiry_contracts = $stmt_details->get_result()->fetch_all(MYSQLI_ASSOC);

        // Create notifications
        foreach ($near_expiry_contracts as $contract) {
            $message = "ลูกค้า: {$contract['name_customer']}\nหมายเลขบิล: {$contract['number_bill']}";
            
            // Check if notification exists
            $sql_check = "SELECT id_notifications FROM notifications WHERE id_user = ? AND id_bill = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ii", $user_id, $contract['id_bill']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows == 0) {
                $sql_insert = "INSERT INTO notifications (id_user, id_bill, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iis", $user_id, $contract['id_bill'], $message);
                $stmt_insert->execute();
            }
        }

        return [
            'success' => true,
            'near_expiry_count' => $near_expiry_count,
            'contracts' => $near_expiry_contracts
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode([
    'near_expiry_count' => $near_expiry_count,
    'contracts' => $near_expiry_contracts
]);