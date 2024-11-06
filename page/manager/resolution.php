<?php
session_start();
include('../../config/db.php');
  // เปลี่ยนเส้นทางการเชื่อมต่อฐานข้อมูล
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
    $store_id = $user['store_id']; // อาจเป็น null ได้
    $store_name = $user['store_name'];
} else {
    header("Location: login.php");
    exit();
}
if (isset($_GET['notiflyreport_id'])) {
    $notiflyreport_id = $_GET['notiflyreport_id'];
    
    $update_status_query = $conn->prepare("UPDATE notiflyreport SET status = 'read' WHERE notiflyreport_id = ?");
    $update_status_query->bind_param("i", $notiflyreport_id);
    $update_status_query->execute();
}
// Handle AJAX request for status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $product_id = $_POST['product_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$product_id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    // Verify the product belongs to the user's store
    $query = "SELECT store_id FROM product WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product['store_id'] != $_SESSION['store_id']) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to this product']);
        exit;
    }
    
    // Update product status
    $update_query = "UPDATE product SET status = ? WHERE product_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $status, $product_id);
    
    if ($update_stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $update_stmt->close();
    exit;
}

// Query to get resolution data with product information
$query = "SELECT rp.*, p.status as product_status, p.product_id, 
          pi.product_name, p.store_id
          FROM resolution_product rp
          JOIN product p ON rp.product_id = p.product_id
          JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
          WHERE p.store_id = ?
          ORDER BY rp.resolution_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();

//Retrieve filter values from GET request
$search_product_name = $_GET['product_name'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Query to get resolution data with product information
$query = "SELECT rp.*, p.status as product_status, p.product_id, 
          pi.product_name, p.store_id
          FROM resolution_product rp
          JOIN product p ON rp.product_id = p.product_id
          JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
          WHERE p.store_id = ?";

// Apply filters if provided
$params = [$store_id];
if (!empty($search_product_name)) {
    $query .= " AND pi.product_name LIKE ?";
    $params[] = "%" . $search_product_name . "%";
}
if (!empty($filter_status)) {
    $query .= " AND p.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY rp.resolution_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat("s", count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./respontive.css">
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
        <a href="resolution.php">Product Report</a>
        <a href="inventory.php">Inventory</a>
        <a href="reports.php">Reports </a>
    </div>
    <div class="container" id="main-content">
        <h2 class="mt-4 mb-4">Resolution Products</h2>
         <!-- Search and Filter Form -->
         <form method="GET" class="form-inline mb-3">
            <input type="text" name="product_name" class="form-control mr-2" placeholder="Product Name" value="<?php echo htmlspecialchars($search_product_name); ?>">
            <select name="status" class="form-control mr-2">
                <option value="">All Status</option>
                <option value="Unusable" <?php if ($filter_status == 'Unusable') echo 'selected'; ?>>Unusable</option>
                <option value="Replace" <?php if ($filter_status == 'Replace') echo 'selected'; ?>>Replace</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <div class="table-responsive resolution-table">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Resolution Type</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $row['resolution_type'] === 'replace' ? 'status-replace' : 'status-reject'; ?>">
                                    <?php echo ucfirst($row['resolution_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['resolution_description']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($row['resolution_date'])); ?></td>
                           <td>
                            <?php
                            $status = $row['priduct_status'] ?? 'Pending';
                            $badgeClass = '';

                            switch ($status) {
                                case 'in_stock':
                                    $badgeClass = 'success'; // สีเขียว
                                    break;
                                case 'check':
                                    $badgeClass = 'warning'; // สีเหลือง
                                    break;
                                case 'expired':
                                    $badgeClass = 'danger'; // สีแดง
                                    break;
                                case 'nearing_expiration':
                                    $badgeClass = 'secondary'; // สีเทาอ่อน
                                    break;
                                case 'issue':
                                    $badgeClass = 'danger'; // สีแดง
                                    break;
                                case 'cancel':
                                    $badgeClass = 'dark'; // สีเทาเข้ม
                                    break;
                                case 'unusable':
                                    $badgeClass = 'dark'; // สีเทาเข้ม
                                    break;
                                case 'replace':
                                    $badgeClass = 'primary'; // สีน้ำเงิน
                                    break;
                                case 'empty':
                                    $badgeClass = 'light'; // สีน้ำเงินอ่อน
                                    break;
                                default:
                                    $badgeClass = 'info'; // สีพื้นฐาน (ขาว)
                                    break;
                            }
                            ?>
                            <span class="badge badge-<?php echo $badgeClass; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                            </span>
                        </td>
                            <td>
                                <?php if ($row['resolution_type'] === 'replace' && $row['product_status'] !== 'in_stock') { ?>
                                    <button 
                                        class="btn btn-success btn-sm update-status" 
                                        data-product-id="<?php echo $row['product_id']; ?>"
                                    >
                                        Add to Stock
                                    </button>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
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
        // Handle status update
        $(document).ready(function() {
            $('.update-status').click(function() {
                const productId = $(this).data('product-id');
                const button = $(this);
                
                $.ajax({
                    url: 'resolution.php',
                    method: 'POST',
                    data: {
                        action: 'update_status',
                        product_id: productId,
                        status: 'in_stock'
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.success) {
                            button.closest('tr').find('td:eq(4)').text('In Stock');
                            button.remove();
                            alert('Product status updated successfully!');
                        } else {
                            alert('Error updating product status: ' + data.message);
                        }
                    },
                    error: function() {
                        alert('Error occurred while updating product status');
                    }
                });
            });
        });
    </script>
</body>
</html>