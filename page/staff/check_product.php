<?php
function checkProductStatus($conn, $product_id) {
    // 1. ดึงข้อมูลสินค้าและการตั้งค่าแจ้งเตือน
    $query = "SELECT p.*, pi.listproduct_id, pas.low_stock_threshold, pas.expiry_alert_days
              FROM product p
              JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
              LEFT JOIN product_alert_settings pas ON pi.listproduct_id = pas.listproduct_id
              WHERE p.product_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product) {
        return false;
    }

    // ถ้าไม่มีการตั้งค่าแจ้งเตือน ใช้ค่าเริ่มต้น
    $low_stock_threshold = $product['low_stock_threshold'] ?? 10;
    $expiry_alert_days = $product['expiry_alert_days'] ?? 7;

    // 2. ตรวจสอบจำนวนรวมของสินค้า
    $total_query = "SELECT SUM(quantity) as total_quantity 
                    FROM product 
                    WHERE listproduct_id = ? 
                    AND store_id = ? 
                    AND status IN ('in_stock', 'nearing_expiration')";
    
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->bind_param("ii", $product['listproduct_id'], $product['store_id']);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_data = $total_result->fetch_assoc();
    $total_quantity = $total_data['total_quantity'] ?? 0;

    // 3. ตรวจสอบและบันทึกการแจ้งเตือน
    $notifications = [];

    // ตรวจสอบสินค้าใกล้หมด
    if ($total_quantity <= $low_stock_threshold) {
        // ตรวจสอบว่ามีการแจ้งเตือนอยู่แล้วหรือไม่
        $check_existing = "SELECT * FROM notiflyproduct 
                          WHERE listproduct_id = ? 
                          AND store_id = ? 
                          AND alert_type = 'low_stock' 
                          AND status = 'unread'";
        
        $check_stmt = $conn->prepare($check_existing);
        $check_stmt->bind_param("ii", $product['listproduct_id'], $product['store_id']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            // สร้างการแจ้งเตือนใหม่
            $insert_notify = "INSERT INTO notiflyproduct 
                            (listproduct_id, product_id, alert_type, store_id) 
                            VALUES (?, ?, 'low_stock', ?)";
            
            $notify_stmt = $conn->prepare($insert_notify);
            $notify_stmt->bind_param("iii", 
                $product['listproduct_id'], 
                $product_id,
                $product['store_id']
            );
            $notify_stmt->execute();
            $notifications[] = [
                'type' => 'low_stock',
                'message' => 'Low stock alert created',
                'product_id' => $product_id
            ];
        }
    }

    // ตรวจสอบวันหมดอายุ
    if ($product['expiration_date']) {
        $exp_date = new DateTime($product['expiration_date']);
        $today = new DateTime();
        $interval = $today->diff($exp_date);
        $days_until_expiry = $interval->days;

        if ($days_until_expiry <= $expiry_alert_days && $days_until_expiry >= 0) {
            // ตรวจสอบว่ามีการแจ้งเตือนอยู่แล้วหรือไม่
            $check_existing = "SELECT * FROM notiflyproduct 
                             WHERE product_id = ? 
                             AND alert_type = 'near_exp' 
                             AND status = 'unread'";
            
            $check_stmt = $conn->prepare($check_existing);
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows === 0) {
                // สร้างการแจ้งเตือนใหม่
                $insert_notify = "INSERT INTO notiflyproduct 
                                (listproduct_id, product_id, alert_type, store_id) 
                                VALUES (?, ?, 'near_exp', ?)";
                
                $notify_stmt = $conn->prepare($insert_notify);
                $notify_stmt->bind_param("iii", 
                    $product['listproduct_id'], 
                    $product_id,
                    $product['store_id']
                );
                $notify_stmt->execute();
                
                // อัพเดตสถานะสินค้าเป็น nearing_expiration
                $update_status = "UPDATE product 
                                SET status = 'nearing_expiration' 
                                WHERE product_id = ?";
                
                $update_stmt = $conn->prepare($update_status);
                $update_stmt->bind_param("i", $product_id);
                $update_stmt->execute();
                
                $notifications[] = [
                    'type' => 'near_exp',
                    'message' => 'Near expiration alert created and status updated',
                    'product_id' => $product_id
                ];
            }
        }
    }
    // เพิ่มการตรวจสอบวันหมดอายุตรงกับวันปัจจุบัน
    if ($product['expiration_date'] && ($product['status'] == 'nearing_expiration' || $product['status'] == 'in_stock')) {
        $today = new DateTime();
        $exp_date = new DateTime($product['expiration_date']);
        
        // เปรียบเทียบเฉพาะวัน เดือน ปี (ไม่รวมเวลา)
        $today->setTime(0, 0, 0);
        $exp_date->setTime(0, 0, 0);
        
        if ($today == $exp_date) {
            // อัพเดตสถานะเป็น expired
            $update_status = "UPDATE product 
                            SET status = 'expired' 
                            WHERE product_id = ?";
            
            $update_stmt = $conn->prepare($update_status);
            $update_stmt->bind_param("i", $product_id);
            $update_stmt->execute();
            
            // เพิ่มการแจ้งเตือน
            $notifications[] = [
                'type' => 'expired',
                'message' => 'Product has expired today',
                'product_id' => $product_id
            ];

            // สร้างการแจ้งเตือนในตาราง notiflyproduct
            $insert_notify = "INSERT INTO notiflyproduct 
                            (listproduct_id, product_id, alert_type, store_id) 
                            VALUES (?, ?, 'expired', ?)";
            $notify_stmt = $conn->prepare($insert_notify);
            $notify_stmt->bind_param("iii", 
                $product['listproduct_id'], 
                $product_id,
                $product['store_id']
            );
            $notify_stmt->execute();
             // บันทึกข้อมูลลงในตาราง damaged_products
            $insert_damaged = "INSERT INTO damaged_products 
                             ( product_id, store_id, deproduct_type) 
                             VALUES ( ?, ?, 'expire')";
            $damaged_stmt = $conn->prepare($insert_damaged);
            $damaged_stmt->bind_param("ii", 
                $product_id,
                $product['store_id']
            );
            $damaged_stmt->execute();
            // หลังจากอัพเดตสถานะเป็น expired แล้ว ให้ return เลย
            return $notifications;
        }
    }
    // เช็คสถานะ in_stock และรีเช็คเงื่อนไขต่างๆ
    if ($product['status'] === 'in_stock') {
        // เรียกใช้ฟังก์ชัน recheck สำหรับสินค้าที่กลับมาเป็น in_stock
        $recheck_notifications = recheckProductStatus($conn, $product_id);
        if ($recheck_notifications) {
            $notifications = array_merge($notifications, $recheck_notifications);
        }
    }

    return $notifications;
}

// เพิ่มฟังก์ชันใหม่สำหรับการรีเช็คสถานะ
function recheckProductStatus($conn, $product_id) {
    $notifications = [];
    
    // ดึงข้อมูลสินค้า
    $query = "SELECT p.*, pi.listproduct_id, pas.low_stock_threshold, pas.expiry_alert_days
              FROM product p
              JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
              LEFT JOIN product_alert_settings pas ON pi.listproduct_id = pas.listproduct_id
              WHERE p.product_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        return $notifications;
    }

    // เช็คเงื่อนไขต่างๆ อีกครั้ง
    // 1. เช็ควันหมดอายุ
    if ($product['expiration_date']) {
        $exp_date = new DateTime($product['expiration_date']);
        $today = new DateTime();
        $interval = $today->diff($exp_date);
        $days_until_expiry = $interval->days;
        
        if ($days_until_expiry <= ($product['expiry_alert_days'] ?? 7) && $days_until_expiry >= 0) {
            $update_status = "UPDATE product 
                            SET status = 'nearing_expiration' 
                            WHERE product_id = ?";
            
            $update_stmt = $conn->prepare($update_status);
            $update_stmt->bind_param("i", $product_id);
            $update_stmt->execute();
            
            $notifications[] = [
                'type' => 'status_update',
                'message' => 'Product status updated to nearing_expiration after recheck',
                'product_id' => $product_id
            ];
        }
    }

    // 2. เช็คจำนวนสินค้าคงเหลือ
    $total_query = "SELECT SUM(quantity) as total_quantity 
                    FROM product 
                    WHERE listproduct_id = ? 
                    AND store_id = ? 
                    AND status IN ('in_stock', 'nearing_expiration')";
    
    $total_stmt = $conn->prepare($total_query);
    $total_stmt->bind_param("ii", $product['listproduct_id'], $product['store_id']);
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_data = $total_result->fetch_assoc();
    $total_quantity = $total_data['total_quantity'] ?? 0;

    if ($total_quantity <= ($product['low_stock_threshold'] ?? 10)) {
        $notifications[] = [
            'type' => 'recheck_low_stock',
            'message' => 'Low stock condition detected after recheck',
            'product_id' => $product_id
        ];
    }

    return $notifications;
}