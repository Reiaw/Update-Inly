<?php
require_once '../config/config.php';
require_once 'functions.php';

$data = json_decode(file_get_contents('php://input'), true);

try {
    global $conn;
    $conn->begin_transaction();

    if (isset($data['id_service']) && $data['id_service'] > 0) {
        $id_package = null;
        
        if (isset($data['id_package']) && !empty($data['id_package'])) {
            // Update existing package
            $packageData = [
                'name_package' => $data['name_package'],
                'info_package' => $data['info_package'],
                'create_at' => $data['create_at']
            ];
            if (updatePackage($data['id_package'], $packageData)) {
                $id_package = $data['id_package'];
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
            
            // Insert the package and get its ID
            $sql = "INSERT INTO package_list (name_package, info_package, id_service, create_at, update_at) 
                    VALUES (?, ?, ?, ?, NOW())";
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

        // Now that we have the package ID, create the products
        if ($id_package && !empty($data['products'])) {
            foreach ($data['products'] as $product) {
                $productData = [
                    'name_product' => $product['name_product'],
                    'info_product' => $product['info_product'],
                    'id_package' => $id_package,
                    'create_at' => $data['create_at']
                ];
                
                $sql = "INSERT INTO product_list (name_product, info_product, id_package, create_at, update_at) 
                        VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", 
                    $productData['name_product'], 
                    $productData['info_product'], 
                    $productData['id_package'], 
                    $productData['create_at']
                );
                
                if (!$stmt->execute()) {
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