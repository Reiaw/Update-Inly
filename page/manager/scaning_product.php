<?php
session_start();
include('../../config/db.php');

if ($_SESSION['role'] !== 'manager' || $_SESSION['store_id'] === null) {
    header('Location: ../../auth/login.php');
    exit;
}
    
$user_id = $_SESSION['user_id'];

$query = "SELECT u.name, u.surname, u.role, u.store_id, s.store_name 
          FROM users u
          LEFT JOIN stores s ON u.store_id = s.store_id 
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $name = $user['name'];
    $surname = $user['surname'];
    $role = $user['role'];
    $store_id = $user['store_id'];
    $store_name = $user['store_name'];
} else {
    header("Location: ../../auth/login.php");
    exit();
}
ob_clean();
// Handle barcode scanning
if(isset($_POST['barcode'])) {
    // Set content type header before any output
    header('Content-Type: application/json');
    error_reporting(0); // Disable error reporting for AJAX requests
    
    try {
        $barcode = trim($_POST['barcode']);
        $store_id = $_SESSION['store_id'];
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'manager' || $_SESSION['store_id'] === null) {
            throw new Exception('Unauthorized access');
        }
        
        if (empty($barcode)) {
            throw new Exception('Barcode is empty. Please try again.');
        }

        // Rest of your existing barcode handling code...
        $sql = "SELECT o.order_id, o.order_status, o.total_amount, o.order_date,
                    do.detail_order_id, do.price, do.listproduct_id,
                    pi.product_name,
                    p.status as product_status, 
                    p.quantity as product_quantity,
                    p.location,
                    p.product_id,
                    p.expiration_date,
                    p.manufacture_date
                FROM orders o
                JOIN detail_orders do ON o.order_id = do.order_id
                JOIN products_info pi ON do.listproduct_id = pi.listproduct_id
                LEFT JOIN product p ON p.order_id = o.order_id 
                    AND p.listproduct_id = do.listproduct_id 
                    AND p.store_id = ?
                WHERE o.barcode = ? 
                    AND o.store_id = ? 
                    AND o.order_status != 'completed' 
                    AND o.order_status != 'issue'";
            
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("isi", $store_id, $barcode, $store_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $orderDetails = null;
            $products = [];
            
            while ($row = $result->fetch_assoc()) {
                if (!$orderDetails) {
                    if ($row['order_status'] === 'shipped' || $row['order_status'] === 'return_shipped') {
                        $updateStatusSql = "UPDATE orders SET order_status = 'delivered', delivered_date = CURRENT_TIMESTAMP WHERE order_id = ?";
                        $updateStmt = $conn->prepare($updateStatusSql);
                        if (!$updateStmt) {
                            throw new Exception('Database error: ' . $conn->error);
                        }
                        $updateStmt->bind_param("i", $row['order_id']);
                        $updateStmt->execute();
                        $row['order_status'] = 'delivered';
                        $order_id = $row['order_id']; 
                        // Insert notification into notiflyreport table
                        $notifyType = 'deli_order';
                        $insertNotifySql = "INSERT INTO notiflyreport (user_id, order_id, notiflyreport_type, store_id) 
                                        VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($insertNotifySql);
                        $stmt->bind_param("iisi", $user_id, $order_id, $notifyType, $store_id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception('Failed to create notification');
                        }
                    }
                    
                    $orderDetails = [
                        'order_id' => $row['order_id'],
                        'order_status' => $row['order_status'],
                        'total_amount' => $row['total_amount'],
                        'order_date' => $row['order_date']
                    ];
                }
                
                $products[] = [
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name'],
                    'price' => $row['price'],
                    'product_status' => $row['product_status'],
                    'product_quantity' => $row['product_quantity'],
                    'location' => $row['location'],
                    'expiration_date' => $row['expiration_date'],
                    'manufacture_date' => $row['manufacture_date'],
                    'listproduct_id' => $row['listproduct_id']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'order' => $orderDetails,
                'products' => $products
            ]);
            
        } else {
            // Check if order exists but is completed
            $checkSql = "SELECT order_status FROM orders WHERE barcode = ? AND store_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            if (!$checkStmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            $checkStmt->bind_param("si", $barcode, $store_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $orderStatus = $checkResult->fetch_assoc()['order_status'];
                if ($orderStatus === 'completed') {
                    throw new Exception('This order has already been completed.');
                } elseif($orderStatus === 'issue') {
                    throw new Exception('This order has already been reported.');
                }
            } else {
                throw new Exception('No order found for this barcode in your store.');
            }
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit();
}


// Handle product location updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['barcode'])) {
    header('Content-Type: application/json');
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['order_id']) || !isset($data['products'])) {
            throw new Exception('Invalid request data');
        }

        $store_id = $_SESSION['store_id'];
        $conn->begin_transaction();

        // Validate order status
        $checkOrderSql = "SELECT order_status FROM orders WHERE order_id = ? AND store_id = ?";
        $stmt = $conn->prepare($checkOrderSql);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $stmt->bind_param("ii", $data['order_id'], $store_id);
        $stmt->execute();
        $orderResult = $stmt->get_result();
        $orderData = $orderResult->fetch_assoc();

        if (!$orderData || $orderData['order_status'] !== 'delivered') {
            throw new Exception('Invalid order status or order not found.');
        }

        // Update order status
        // อัปเดตสถานะของคำสั่งซื้อ
    $updateOrderSql = "UPDATE orders SET order_status = 'completed' WHERE order_id = ?";
    $orderStmt = $conn->prepare($updateOrderSql);
    if (!$orderStmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $orderStmt->bind_param("i", $data['order_id']);
    $orderStmt->execute();

    // insert แจ้งเตือนลงในตาราง notiflyreport
    $notifyType = 'add_product';
    $insertNotifySql = "INSERT INTO notiflyreport (user_id, order_id, notiflyreport_type, store_id) 
                        VALUES (?, ?, ?, ?)";
    $notiflyStmt = $conn->prepare($insertNotifySql);
    if (!$notiflyStmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $notiflyStmt->bind_param("iisi", $user_id, $data['order_id'], $notifyType, $store_id);
    $notiflyStmt->execute();

    // อัปเดตตำแหน่งของสินค้าและสถานะ
    $updateProductSql = "UPDATE product 
        SET location = ?, 
            status = CASE 
                WHEN status = 'check' THEN 'in_stock'
                ELSE status 
            END,
            receipt_date = CURDATE()
        WHERE product_id = ? AND store_id = ?";
    $productStmt = $conn->prepare($updateProductSql);
    if (!$productStmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    foreach ($data['products'] as $product) {
        if (empty($product['location'])) {
            throw new Exception('Location cannot be empty');
        }
        $productStmt->bind_param("sii", 
            $product['location'],
            $product['product_id'],
            $store_id
        );
        $productStmt->execute();
    }

    // Commit ธุรกรรมหลังจากอัปเดตทั้งหมดเสร็จสมบูรณ์
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Updates completed successfully']);
       
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./respontive.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
    .modal-lg {
        max-width: 80%;
    }
    .close {
        font-size: 1.5rem;
    }
    .modal-body h4 {
        font-weight: bold;
    }
    .location-input {
        width: 100%;
        padding: 0.25rem;
        font-size: 0.9rem;
    }
    .modal-footer {
        justify-content: space-between;
    }
    </style>
</head>
<body>
    <button id="menu-toggle">☰</button>
    <header id="banner">
        <a id="user-info">Name: <?php echo $name . ' ' . $surname; ?> | Role: <?php echo $role; ?>
        <?php if (!is_null($store_id)) { ?> 
            | Store: <?php echo $store_name; ?> 
        <?php } ?>
        </a>
        <button class="btn btn-danger" onclick="window.location.href='../../auth/logout.php'">Log Out</button>
    </header>
    <div id="sidebar">
        <h4 class="text-center">Menu</h4>
        <a href="dashboard.php">Dashboard</a>
        <a href="show_user.php">Show User</a>
        <a href="order.php">Order</a>
        <a href="tracking.php">Tracking</a>
        <a href="scaning_product.php">Scaning Product</a>
        <a href="resolution.php">product Report</a>
        <a href="inventory.php">Inventory</a>
        <a href="reports.php">Reports </a>
    </div>

    <div class="container" id="main-content">
        <h2 class="mt-4 mb-4 text-center">Scan Product</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <button onclick="startScanner()" id="start-camera" class="btn btn-primary">Start Camera</button>
                    <button onclick="stopScanner()" id="stop-camera" class="btn btn-danger">Stop Camera</button>
                </div>
                <div id="scanner-container" style="width: 100%; max-width: 640px; height: 500px; border: 1px solid #ccc;"></div>
            </div>
            <div class="col-md-6">
                <div class="mt-3">
                    <label for="barcode-input">Enter Barcode Manually:</label>
                    <input type="text" id="barcode-input" class="form-control" placeholder="Enter barcode here">
                    <button onclick="submitManualBarcode()" class="btn btn-secondary btn-block mt-2">Submit Barcode</button>
                </div>
                <input type="text" id="barcode-value" class="form-control mt-3" readonly>
                <div id="product-info" class="mt-4"></div>
            </div>
        </div>

        <!-- Order Modal -->
        <div class="modal fade" id="orderModal" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="orderModalLabel">Order Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="order-info">
                            <h4 class="mb-3">Order Information</h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Order ID:</strong> <span id="order-id"></span></p>
                                    <p><strong>Status:</strong> <span id="order-status" class="status-badge"></span></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Amount:</strong> ฿<span id="order-amount"></span></p>
                                    <p><strong>Order Date:</strong> <span id="order-date"></span></p>
                                </div>
                            </div>
                        </div>
                        <div id="products-info" class="mt-4">
                            <h4 class="mb-3">Products</h4>
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Stock</th>
                                        <th>Manufacture date</th>
                                        <th>Expiration date</th>
                                    </tr>
                                </thead>
                                <tbody id="products-table-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div>
                            <button type="button" class="btn btn-primary" id="addButton">Add Product</button>
                            <button type="button" class="btn btn-warning" id="reportButton">Report</button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-success" id="submitlocation" style="display: none;">Submit</button>
                            <button type="button" class="btn btn-success" id="submitreport" style="display: none;">Submit</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>

        <!-- Issue Report Modal -->
        <div class="modal fade" id="issueReportModal" tabindex="-1" role="dialog" aria-labelledby="issueReportModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="issueReportModalLabel">Report Issue</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="issueReportForm" enctype="multipart/form-data">
                            <input type="hidden" id="issue_order_id" name="order_id">
                            <input type="hidden" id="issue_product_id" name="product_id">
                            
                            <div class="form-group">
                                <label for="issue_type">Issue Type</label>
                                <select class="form-control" id="issue_type" name="issue_type" required>
                                    <option value="">Select Issue Type</option>
                                    <option value="missing_item">Missing Item</option>
                                    <option value="damaged_item">Damaged Item</option>
                                    <option value="incorrect_item">Incorrect Item</option>
                                    <option value="Expired or Quality Issue">Expired or Quality Issue</option>
                                    <option value="Damaged Packaging">Damaged Packaging</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="issue_description">Description</label>
                                <textarea class="form-control" id="issue_description" name="issue_description" rows="3" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="issue_image">Upload Evidence (Image)</label>
                                <input type="file" class="form-control-file" id="issue_image" name="issue_image" accept="image/*">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="submitIssue">Submit Issue</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    let isScanning = false;
    function startScanner() {
        if (isScanning) {
            Quagga.stop();
        }
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector('#scanner-container'),
                constraints: {
                    width: 640,
                    height: 480,
                    facingMode: "environment"
                },
            },
            decoder: {
                readers: ["code_128_reader"]
            }
        }, function (err) {
            if (err) {
                console.log(err);
                return;
            }
            console.log("Initialization finished. Ready to start");
            Quagga.start();
            isScanning = true;
        });

        Quagga.onDetected(function (result) {
            let code = result.codeResult.code;
            document.getElementById('barcode-value').value = code;
            Quagga.stop();
            isScanning = false;
            submitBarcode(code);
        });
    }

    function stopScanner() {
        if (isScanning) {
            Quagga.stop();
            isScanning = false;
            console.log("Scanner stopped");
        }
    }

    // ฟังก์ชันสำหรับการส่งรหัสบาร์โค้ดด้วยตนเอง
    function submitManualBarcode() {
            const barcode = document.getElementById('barcode-input').value;
            if (barcode.trim() !== "") {
                submitBarcode(barcode);
            } else {
                alert("Please enter a barcode.");
            }
        }
        // เริ่มการสแกนเมื่อเปิดหน้า
        startScanner();
        // Add this after your existing JavaScript code
        document.getElementById('submitlocation').addEventListener('click', function() {
            const orderIdElement = document.getElementById('order-id');
            const orderId = orderIdElement.textContent;
            
            // Collect all location inputs
            const locationInputs = document.querySelectorAll('.location-input');
            const products = [];
            let hasIssueProduct = false;
            
            locationInputs.forEach(input => {
                const productStatus = input.closest('tr').querySelector('.badge').textContent.trim();
                
                // ตรวจสอบสถานะของ product ว่าเป็น 'issue' หรือไม่
                if (productStatus === 'issue') {
                    hasIssueProduct = true;
                } else {
                    products.push({
                        product_id: input.dataset.productId,
                        location: input.value.trim()
                    });
                }
            });

            if (hasIssueProduct) {
                alert('One or more products have an issue status and cannot be updated.');
                return;
            }
            // Validate locations
            const emptyLocations = products.some(p => !p.location);
            if (emptyLocations) {
                alert('Please fill in all location fields');
                return;
            }

            // Submit the data
            fetch('scaning_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    products: products
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Locations updated successfully');
                    $('#orderModal').modal('hide');
                    // Optionally refresh the page or update the display
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating locations');
            });
        });

    function createTableRow(product, mode, orderId) {
        const row = document.createElement('tr');
        
        // Basic columns that always show
        const basicColumns = `
            <td>${product.product_id || ''}</td>
            <td>${product.product_name || ''}</td>
            <td>฿${parseFloat(product.price).toLocaleString()}</td>
            <td><span class="badge ${(product.product_status)}">${product.product_status || ''}</span></td>
            <td>${product.product_quantity || ''}</td>
            <td>${product.manufacture_date ? new Date(product.manufacture_date).toLocaleDateString() : ''}</td>
            <td>${product.expiration_date ? new Date(product.expiration_date).toLocaleDateString() : ''}</td>`;
        
        // Location column - only show in verify mode
        const locationColumn = mode === 'add' ? `
            <td>
                <input type="text" class="form-control location-input" 
                    data-product-id="${product.product_id}"
                    value="${product.location || ''}" 
                    placeholder="Enter location">
            </td>` : '';
        
        // Action column - only show in report mode
        const actionColumn = mode === 'report' ? `
            <td class="text-center">
                <button class="btn btn-danger btn-sm report-issue" 
                    data-order-id="${orderId}"
                    data-product-id="${product.product_id}">
                    Report Issue
                </button>
            </td>` : '';
        
        row.innerHTML = basicColumns + locationColumn + actionColumn;
        return row;
    }

    function submitBarcode(barcode) {
        fetch('scaning_product.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'barcode=' + barcode
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update order details
                document.getElementById('order-id').textContent = data.order.order_id;
                document.getElementById('order-status').textContent = data.order.order_status;
                document.getElementById('order-amount').textContent = parseFloat(data.order.total_amount).toLocaleString();
                
                const orderDate = new Date(data.order.order_date);
                document.getElementById('order-date').textContent = orderDate.toLocaleDateString('th-TH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                // Clear existing table headers and rows
                const tableHead = document.querySelector('#products-info table thead tr');
                const tableBody = document.getElementById('products-table-body');
                
                // Initial table setup - basic columns only
                tableHead.innerHTML = `
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Stock</th>
                    <th>Manufacture date</th>
                    <th>Expiration date</th>
                `;
                
                // Clear and populate table body with basic view
                tableBody.innerHTML = '';
                data.products.forEach(product => {
                    tableBody.appendChild(createTableRow(product, 'basic', data.order.order_id));
                });

                // Add event listeners for verify and report buttons
                document.getElementById('addButton').addEventListener('click', function() {

                    document.getElementById('submitlocation').style.display = 'inline-block';
                    document.getElementById('submitreport').style.display = 'none';
                    // Add location column to header
                    tableHead.innerHTML = `
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Stock</th>
                        <th>Manufacture date</th>
                        <th>Expiration date</th>
                        <th>Location</th>
                    `;
                    
                    // Repopulate table with location inputs
                    tableBody.innerHTML = '';
                    data.products.forEach(product => {
                        tableBody.appendChild(createTableRow(product, 'add', data.order.order_id));
                    });
                });

                document.getElementById('reportButton').addEventListener('click', function() {

                    document.getElementById('submitreport').style.display = 'inline-block';
                    document.getElementById('submitlocation').style.display = 'none';
                    // Add action column to header
                    tableHead.innerHTML = `
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Stock</th>
                        <th>Manufacture date</th>
                        <th>Expiration date</th>
                        <th>Report</th>
                    `;
                    
                    // Repopulate table with report buttons
                    tableBody.innerHTML = '';
                    data.products.forEach(product => { 
                        tableBody.appendChild(createTableRow(product, 'report', data.order.order_id));
                });

                // Add event listeners for report buttons
                document.querySelectorAll('.report-issue').forEach(button => {
                    button.addEventListener('click', function() {
                        const orderId = this.getAttribute('data-order-id');
                        const productId = this.getAttribute('data-product-id');
                        
                        document.getElementById('issue_order_id').value = orderId;
                        document.getElementById('issue_product_id').value = productId;
                        
                        $('#issueReportModal').modal('show');
                    });
                });
            });

            // Show the modal
            $('#orderModal').modal('show');
        } else {
            document.getElementById('product-info').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    ${data.message}
                </div>`;
        }

        // Restart scanner after delay if needed
        if (isScanning) {
            setTimeout(() => {
                startScanner();
            }, 3000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('product-info').innerHTML = `
            <div class="alert alert-danger" role="alert">
                An error occurred while processing the request.
            </div>`;
        if (isScanning) {
            startScanner();
        }
    });
}
    document.getElementById('submitIssue').addEventListener('click', function() {
        const form = document.getElementById('issueReportForm');
        const formData = new FormData(form);
        formData.append('action', 'submit_issue');

        fetch('issue_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                $('#issueReportModal').modal('hide');
                form.reset();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while submitting the issue');
        });
    });

    document.getElementById('submitreport').addEventListener('click', function() {
        const orderId = document.getElementById('order-id').textContent;

        fetch('issue_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=update_order_status&order_id=${orderId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                $('#orderModal').modal('hide');
                // Optionally refresh the page
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the order status');
        });
    });
    </script>
    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('sidebar-active');
        });
    </script>
</body>
</html>