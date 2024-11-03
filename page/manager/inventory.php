<?php
session_start();
include('../../config/db.php');
  // เปลี่ยนเส้นทางการเชื่อมต่อฐานข้อมูล
  if ($_SESSION['role'] !== 'manager' || $_SESSION['store_id'] === null) {
    header('Location: ../../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$store_id = $_SESSION['store_id'];
// ตรวจสอบประเภทของการร้องขอ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่าการร้องขอเป็นการดึงข้อมูล location
    if (isset($_POST['action']) && $_POST['action'] === 'get_location' && isset($_POST['product_id'])) {
        $product_id = $_POST['product_id'];
        $query = "SELECT location FROM product WHERE product_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['location' => $row['location']]);
        } else {
            echo json_encode(['error' => 'Product not found']);
        }
        $stmt->close();
        exit();
    }

    // ตรวจสอบว่าการร้องขอเป็นการอัปเดต location
    if (isset($_POST['action']) && $_POST['action'] === 'update_location' && isset($_POST['product_id']) && isset($_POST['location'])) {
        $product_id = $_POST['product_id'];
        $location = $_POST['location'];
        
        $query = "UPDATE product SET location = ? WHERE product_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $location, $product_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
        $stmt->close();
        exit();
    }
    if (isset($_POST['action']) && $_POST['action'] === 'submit_report') {
        $product_id = $_POST['product_id'];
        $issue_type = $_POST['issue_type'];
        $issue_description = $_POST['issue_description'];
        
        // Handle file upload
        $upload_dir = '../../upload/issue_pic/';
        $image_path = null;
        
        if (isset($_FILES['issue_image']) && $_FILES['issue_image']['error'] === 0) {
            $file_extension = pathinfo($_FILES['issue_image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['issue_image']['tmp_name'], $upload_path)) {
                $image_path = $new_filename;
            }
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into issue_product table
            $query = "INSERT INTO issue_product (product_id, issue_type, issue_description, report_date, issue_image) 
                     VALUES (?, ?, ?, NOW(), ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isss", $product_id, $issue_type, $issue_description, $image_path);
            $stmt->execute();
            
            // Update product status to 'issue'
            $query = "UPDATE product SET status = 'issue' WHERE product_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}

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

// Prepare the query to get product information
$query = "SELECT p.product_id, p.quantity, p.status, p.expiration_date, p.manufacture_date, 
                 p.location, pi.product_name, pi.category, pi.price_set
          FROM product p
          JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
          WHERE p.store_id = ? AND p.status != 'check'
          ORDER BY p.updated_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $store_id);
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
        <a href="inventory.php">Inventory</a>
        <a href="reports.php">Reports </a>
    </div>
    <div class="container" id="main-content">
        <h2>Inventory Management</h2>
        
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Expiration Date</th>
                        <th>Manufacture Date</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($row['price_set'], 2); ?></td>
                            <td><?php echo $row['expiration_date']; ?></td>
                            <td><?php echo $row['manufacture_date']; ?></td>
                            <td><?php echo $row['location'] ?? 'N/A'; ?></td>
                            <td>
                            <?php if ($order['order_status'] !== 'issue') { ?>
                                <button class="btn btn-sm btn-primary" onclick="editLocation(<?php echo $row['product_id']; ?>)">Edit</button>
                                <button class="btn btn-sm btn-danger" onclick="reportProduct(<?php echo $row['product_id']; ?>)">Report</button>
                            <?php } ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modal for editing location -->
    <div class="modal fade" id="editLocationModal" tabindex="-1" aria-labelledby="editLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLocationModalLabel">Edit Product Location</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editLocationForm">
                        <input type="hidden" id="editProductId">
                        <div class="form-group">
                            <label for="editLocation">Location</label>
                            <input type="text" class="form-control" id="editLocation" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitEditLocation()">Save changes</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for issue report -->
    <div class="modal fade" id="reportProductModal" tabindex="-1" aria-labelledby="reportProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportProductModalLabel">Report Product Issue</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="reportProductForm" enctype="multipart/form-data">
                        <input type="hidden" id="reportProductId" name="product_id">
                        <div class="form-group">
                            <label for="issueType">Issue Type</label>
                            <select class="form-control" id="issueType" name="issue_type" required>
                                <option value="">Select Issue Type</option>
                                <option value="quality_issue">Quality Issue</option>
                                <option value="quantity_issue">Quantity Issue</option>
                                <option value="damaged_issue">Damaged Issue</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="issueDescription">Description</label>
                            <textarea class="form-control" id="issueDescription" name="issue_description" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="issueImage">Evidence Image</label>
                            <input type="file" class="form-control-file" id="issueImage" name="issue_image" accept="image/*">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitReport()">Submit Report</button>
                </div>
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
        function editLocation(productId) {
            document.getElementById('editProductId').value = productId;
            $.post('', { action: 'get_location', product_id: productId }, function(response) {
                const data = JSON.parse(response);
                if (data.location) {
                    document.getElementById('editLocation').value = data.location;
                    $('#editLocationModal').modal('show');
                } else {
                    alert('Error: ' + data.error);
                }
            });
        }
        function submitEditLocation() {
            const productId = document.getElementById('editProductId').value;
            const newLocation = document.getElementById('editLocation').value;
            $.post('', { action: 'update_location', product_id: productId, location: newLocation }, function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    alert('Location updated successfully');
                    $('#editLocationModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error updating location: ' + data.message);
                }
            });
        }
        function reportProduct(productId) {
            document.getElementById('reportProductId').value = productId;
            $('#reportProductModal').modal('show');
        }
        function submitReport() {
            const form = document.getElementById('reportProductForm');
            const formData = new FormData(form);
            formData.append('action', 'submit_report');
            
            $.ajax({
                url: '',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        alert('Issue reported successfully');
                        $('#reportProductModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error reporting issue: ' + data.message);
                    }
                },
                error: function() {
                    alert('Error submitting report');
                }
            });
        }
    </script>
</body>
</html>