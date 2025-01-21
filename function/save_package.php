<?php
require_once '../config/config.php';
require_once 'functions.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    global $conn;
    $conn->begin_transaction();

    if (isset($data['id_service']) && $data['id_service'] > 0) {
        $id_package = null;
        
        // First, check if there are any existing active packages for this service
        if (!isset($data['id_package']) || empty($data['id_package'])) {
            // For new package, update status of all existing packages and their products
            $sql = "SELECT id_package FROM package_list 
                   WHERE id_service = ? AND status_package = 'ใช้งาน'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $data['id_service']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                // Update existing package status
                $updatePackageSql = "UPDATE package_list SET 
                                   status_package = 'ยกเลิก',
                                   update_at = NOW() 
                                   WHERE id_package = ?";
                $updateStmt = $conn->prepare($updatePackageSql);
                $updateStmt->bind_param("i", $row['id_package']);
                $updateStmt->execute();
                
                // Update associated products status
                $updateProductSql = "UPDATE product_list SET 
                                   status_product = 'ยกเลิก',
                                   update_at = NOW() 
                                   WHERE id_package = ?";
                $updateStmt = $conn->prepare($updateProductSql);
                $updateStmt->bind_param("i", $row['id_package']);
                $updateStmt->execute();
            }
        }

        if (isset($data['id_package']) && !empty($data['id_package'])) {
            // Update existing package
            $packageData = [
                'name_package' => $data['name_package'],
                'info_package' => $data['info_package'],
                'create_at' => $data['create_at']
            ];
            if (updatePackage($data['id_package'], $packageData)) {
                $id_package = $data['id_package'];
                
                // Update status of existing products to 'ยกเลิก'
                $sql = "UPDATE product_list SET 
                       status_product = 'ยกเลิก',
                       update_at = NOW() 
                       WHERE id_package = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_package);
                $stmt->execute();
                
                // Delete associated overide records
                $sql = "DELETE o FROM overide o 
                       INNER JOIN product_list p ON o.id_product = p.id_product 
                       WHERE p.id_package = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_package);
                $stmt->execute();
            } else {
                throw new Exception("Failed to update package");
            }
        } else {
            // Create new package
            $packageData = [
                'name_package' => $data['name_package'],
                'info_package' => $data['info_package'],
                'id_service' => $data['id_service'],
                'create_at' => $data['create_at']
            ];
            
            $sql = "INSERT INTO package_list (name_package, info_package, id_service, create_at, update_at, status_package) 
                    VALUES (?, ?, ?, ?, NOW(), 'ใช้งาน')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssis", 
                $packageData['name_package'], 
                $packageData['info_package'], 
                $packageData['id_service'], 
                $packageData['create_at']
            );
            
            if ($stmt->execute()) {
                $id_package = $stmt->insert_id;
            } else {
                throw new Exception("Failed to create package");
            }
        }

        // Add new products
        if ($id_package && !empty($data['products'])) {
            foreach ($data['products'] as $product) {
                $productData = [
                    'name_product' => $product['name_product'],
                    'info_product' => $product['info_product'],
                    'id_package' => $id_package,
                    'create_at' => $data['create_at']
                ];
                
                $sql = "INSERT INTO product_list (name_product, info_product, id_package, create_at, update_at, status_product) 
                        VALUES (?, ?, ?, ?, NOW(), 'ใช้งาน')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", 
                    $productData['name_product'], 
                    $productData['info_product'], 
                    $productData['id_package'], 
                    $productData['create_at']
                );
                
                if ($stmt->execute()) {
                    $id_product = $stmt->insert_id;
        
                    // Save new overide data
                    $overideData = [
                        'mainpackage_price' => $product['mainpackage_price'],
                        'ict_price' => $product['ict_price'],
                        'all_price' => $product['all_price'],
                        'info_overide' => $product['info_overide'],
                        'id_product' => $id_product
                    ];
        
                    $sql = "INSERT INTO overide (mainpackage_price, ict_price, all_price, info_overide, id_product) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("dddss", 
                        $overideData['mainpackage_price'], 
                        $overideData['ict_price'], 
                        $overideData['all_price'], 
                        $overideData['info_overide'], 
                        $overideData['id_product']
                    );
        
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to create overide data");
                    }
                } else {
                    throw new Exception("Failed to create product");
                }
            }
        }
        $conn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Invalid service ID");
    }
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}