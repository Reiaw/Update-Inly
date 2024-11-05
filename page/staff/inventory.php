<?php
session_start();
include('../../config/db.php');
  // เปลี่ยนเส้นทางการเชื่อมต่อฐานข้อมูล
  if ($_SESSION['role'] !== 'staff' || $_SESSION['store_id'] === null) {
    header('Location: ../../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$store_id = $_SESSION['store_id'];

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
    $store_id = $user['store_id']; // อาจเป็น null ได้
    $store_name = $user['store_name'];
} else {
    header("Location: login.php");
    exit();
}
// Get unique products with their total quantities
$total_quantities_query = "SELECT 
    pi.product_name,
    pi.listproduct_id,
    SUM(p.quantity) as total_quantity
FROM products_info pi
JOIN product p ON pi.listproduct_id = p.listproduct_id
WHERE p.store_id = ? 
AND p.status IN ('in_stock', 'nearing_expiration')
GROUP BY pi.product_name, pi.listproduct_id
HAVING total_quantity > 0";

$stmt = $conn->prepare($total_quantities_query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$available_products = $stmt->get_result();

// Handle withdrawal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_withdraw'])) {
    $listproduct_id = $_POST['listproduct_id'];
    $withdraw_quantity = $_POST['withdraw_quantity'];
    
    // First get products with nearing_expiration status
    $get_products = "SELECT product_id, quantity, status 
                    FROM product 
                    WHERE listproduct_id = ? 
                    AND store_id = ? 
                    AND status IN ('nearing_expiration', 'in_stock')
                    ORDER BY CASE 
                        WHEN status = 'nearing_expiration' THEN 1 
                        WHEN status = 'in_stock' THEN 2 
                    END,
                    expiration_date ASC";
                    
    $stmt = $conn->prepare($get_products);
    $stmt->bind_param("ii", $listproduct_id, $store_id);
    $stmt->execute();
    $products = $stmt->get_result();
    
    $remaining_withdraw = $withdraw_quantity;
    
    while ($product = $products->fetch_assoc()) {
        if ($remaining_withdraw <= 0) break;
        
        $current_quantity = min($product['quantity'], $remaining_withdraw);
        $new_quantity = $product['quantity'] - $current_quantity;
        
        if ($new_quantity > 0) {
            // Update quantity
            $update_query = "UPDATE product SET quantity = ? WHERE product_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $new_quantity, $product['product_id']);
            $update_stmt->execute();
        } else {
            // Update status to empty if quantity becomes 0
            $update_query = "UPDATE product SET quantity = 0, status = 'empty' WHERE product_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $product['product_id']);
            $update_stmt->execute();
        }
        
        // Record withdrawal in withdrawreport
        $insert_report = "INSERT INTO withdrawreport (user_id, product_id, store_id, withdraw_quantity) 
                         VALUES (?, ?, ?, ?)";
        $report_stmt = $conn->prepare($insert_report);
        $report_stmt->bind_param("iiii", $user_id, $product['product_id'], $store_id, $current_quantity);
        $report_stmt->execute();
        
        $remaining_withdraw -= $current_quantity;
    }
    
    if ($remaining_withdraw > 0) {
        echo "<script>alert('Warning: Could not fulfill entire withdrawal request. Only withdrew " . 
             ($withdraw_quantity - $remaining_withdraw) . " units.');</script>";
    } else {
        echo "<script>alert('Successfully withdrew " . $withdraw_quantity . " units.');</script>";
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
// Handle product withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw'])) {
    $product_id = $_POST['product_id'];
    $withdraw_quantity = $_POST['withdraw_quantity'];
    
    // Get current product quantity
    $check_query = "SELECT quantity FROM product WHERE product_id = ? AND store_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $product_id, $store_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product && $withdraw_quantity <= $product['quantity']) {
        $new_quantity = $product['quantity'] - $withdraw_quantity;
        
        if ($new_quantity > 0) {
            // Update quantity
            $update_query = "UPDATE product SET quantity = ? WHERE product_id = ? AND store_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("iii", $new_quantity, $product_id, $store_id);
            $update_stmt->execute();
        } else {
            // Update status to empty if quantity becomes 0
            $update_query = "UPDATE product SET quantity = 0, status = 'empty' WHERE product_id = ? AND store_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $product_id, $store_id);
            $update_stmt->execute();
        }
        // Record withdrawal in withdrawreport
        $insert_report = "INSERT INTO withdrawreport (user_id, product_id, store_id, withdraw_quantity) 
                            VALUES (?, ?, ?, ?)";
        $report_stmt = $conn->prepare($insert_report);
        $report_stmt->bind_param("iiii", $user_id, $product_id, $store_id, $withdraw_quantity);
        $report_stmt->execute();

        // Commit transaction
        $conn->commit();
        
    } else {
        echo "<script>alert('Invalid withdrawal quantity. Please ensure the quantity does not exceed available stock.');</script>";
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch products with product info
$query = "SELECT p.*, pi.product_name, pi.category 
          FROM product p
          JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
          WHERE p.store_id = ? 
          AND p.status IN ('in_stock', 'expired', 'nearing_expiration', 'issue')
          ORDER BY pi.category, pi.product_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $store_id);
$stmt->execute();

$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./respontive.css">
</head>
<style>
        .status-expired { color: red; }
        .status-nearing_expiration { color: orange; }
        .status-issue { color: #dc3545; }
        .product-table th { position: sticky; top: 0; background: white; }
</style>
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
        <a href="inventory.php">Inventory</a>
    </div>
    <div class="container" id="main-content">
    <h2 class="mb-4">Product Inventory</h2>
    <div class="card-body">
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#bulkWithdrawModal">
            Bulk Product Withdrawal
        </button>
    </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Quantity</th>
                        <th>Expiration Date</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($product = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td class="status-<?php echo $product['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                            </td>
                            <td><?php echo $product['quantity']; ?></td>
                            <td><?php echo $product['expiration_date']; ?></td>
                            <td><?php echo $product['location'] ?? 'N/A'; ?></td>
                            <td>
                            <?php if ($product['status'] !== 'expired' && $product['status'] !== 'issue'): ?>
                                <button type="button" 
                                        class="btn btn-primary btn-sm"
                                        data-toggle="modal"
                                        data-target="#withdrawModal<?php echo $product['product_id']; ?>">
                                    Withdraw
                                </button>
                            <?php endif; ?>

                                <!-- Withdrawal Modal -->
                                <div class="modal fade" id="withdrawModal<?php echo $product['product_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Withdraw Product</h5>
                                                <button type="button" class="close" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="product_id" 
                                                           value="<?php echo $product['product_id']; ?>">
                                                    <div class="form-group">
                                                        <label>Quantity (Max: <?php echo $product['quantity']; ?>)</label>
                                                        <input type="number" name="withdraw_quantity" 
                                                               class="form-control" required
                                                               min="1" max="<?php echo $product['quantity']; ?>">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" 
                                                            data-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="withdraw" 
                                                            class="btn btn-primary">Withdraw</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Bulk Withdrawal Modal -->
    <div class="modal fade" id="bulkWithdrawModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Product Withdrawal</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" id="bulkWithdrawForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="listproduct_id">Select Product:</label>
                        <select name="listproduct_id" id="listproduct_id" class="form-control" required onchange="updateSummary()">
                            <option value="">Choose product...</option>
                            <?php while ($product = $available_products->fetch_assoc()): ?>
                                <option value="<?php echo $product['listproduct_id']; ?>" 
                                        data-name="<?php echo $product['product_name']; ?>"
                                        data-location="<?php echo $product['location'] ?? 'N/A'; ?>"
                                        data-quantity="<?php echo $product['total_quantity']; ?>">
                                    <?php echo $product['product_name'] . ' (Available: ' . $product['total_quantity'] . ')'; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="withdraw_quantity">Withdrawal Quantity:</label>
                        <input type="number" name="withdraw_quantity" id="withdraw_quantity" class="form-control" 
                               min="1" required onchange="updateSummary()">
                    </div>
                    <div id="withdrawalSummary" class="alert alert-info d-none">
                    <h6>Withdrawal Summary:</h6>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Location</th>
                                <th>Quantity to Withdraw</th>
                                <th>Available Stock</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" id="previewConfirm" class="btn btn-info">Preview Withdrawal</button>
                    <button type="submit" name="bulk_withdraw" class="btn btn-primary">Confirm Withdrawal</button>
                </div>
            </form>
        </div>
    </div>
</div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('sidebar-active');
        });
        function updateSummary() {
            const listproductSelect = document.getElementById('listproduct_id');
            const quantityInput = document.getElementById('withdraw_quantity');
            const summaryDiv = document.getElementById('withdrawalSummary');
            const confirmButton = document.querySelector("button[name='bulk_withdraw']");
            
              // Clear previous warning message
            const existingWarning = summaryDiv.querySelector('.alert-warning');
            if (existingWarning) {
                existingWarning.remove();
            }

            if (!listproductSelect.value || !quantityInput.value) {
                summaryDiv.classList.add('d-none');
                return;
            }
            
            // ส่ง AJAX request เพื่อดึงข้อมูลตัวอย่างการเบิก
            fetch('preview_withdrawal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `listproduct_id=${listproductSelect.value}&withdraw_quantity=${quantityInput.value}`
            })
            .then(response => response.json())
            .then(data => {
                const tableBody = document.createElement('tbody');
                data.items.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.product_id}</td>
                            <td>${item.product_name}</td>
                            <td>${item.location || 'N/A'}</td>
                            <td>${item.quantity_to_withdraw}</td>
                            <td>${item.available_quantity}</td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
                
                // อัพเดทตารางสรุป
                const summaryTable = summaryDiv.querySelector('table');
                const existingBody = summaryTable.querySelector('tbody');
                if (existingBody) {
                    existingBody.remove();
                }
                summaryTable.appendChild(tableBody);
                
                // แสดงข้อความเตือนถ้าไม่สามารถเบิกได้ตามจำนวนที่ต้องการ
                if (data.total_available < data.total_requested) {
                    const warning = document.createElement('div');
                    warning.className = 'alert alert-warning mt-3';
                    warning.textContent = `Warning: Can only withdraw ${data.total_available} units out of ${data.total_requested} requested units.`;
                    summaryDiv.appendChild(warning);
                    confirmButton.style.display = 'none';
                } else {
                    // Show confirm button if available quantity is sufficient
                    confirmButton.style.display = 'inline-block';
                }
                
                summaryDiv.classList.remove('d-none');
            })
            .catch(error => console.error('Error:', error));
        }

        // เพิ่ม event listener สำหรับปุ่ม Preview
        document.getElementById('previewConfirm').addEventListener('click', function() {
            updateSummary();
        });

        // ปรับปรุง form submission
        document.getElementById('bulkWithdrawForm').addEventListener('submit', function(e) {
            const summaryDiv = document.getElementById('withdrawalSummary');
            if (summaryDiv.classList.contains('d-none')) {
                e.preventDefault();
                alert('Please preview the withdrawal first before confirming.');
            }
        });
        fetch('process_check.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // แสดงการแจ้งเตือนที่สร้างขึ้น
                    data.notifications.forEach(notification => {
                        console.log(`${notification.type}: ${notification.message}`);
                    });
                } else {
                    console.error('Error:', data.error);
                }
            })
            .catch(error => console.error('Error:', error));
        </script>
</body>
</html>