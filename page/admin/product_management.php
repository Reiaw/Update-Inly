<?php
session_start();
include('../../config/db.php');

if ($_SESSION['role'] !== 'admin') {
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
} else {
    header("Location: login.php");
    exit();
}

// Handle resolution submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_resolution'])) {
    $issue_id = $_POST['issue_id'];
    $product_id = $_POST['product_id'];
    $resolution_type = $_POST['resolution_type'];
    $resolution_description = $_POST['resolution_description'];
    
    // Handle file upload
    $image_path = null;
    if (isset($_FILES['resolution_image']) && $_FILES['resolution_image']['error'] === 0) {
        $upload_dir = '../../upload/resolution_images/';
        $image_name = uniqid() . '_' . basename($_FILES['resolution_image']['name']);
        $target_path = $upload_dir . $image_name;
        
        if (move_uploaded_file($_FILES['resolution_image']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        }
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert resolution
        $resolution_query = "INSERT INTO resolution_product (product_id, resolution_type, resolution_description, resolution_date, resolution_image) 
                           VALUES (?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($resolution_query);
        $stmt->bind_param("isss", $product_id, $resolution_type, $resolution_description, $image_path);
        $stmt->execute();
        
        // Update product status based on resolution type
        $new_status = ($resolution_type === 'replace') ? 'replace' : 'unusable';
        $update_query = "UPDATE product SET status = ? WHERE product_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_status, $product_id);
        $stmt->execute();
        
        $conn->commit();
        echo "<script>alert('Resolution submitted successfully!');</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error submitting resolution: " . $e->getMessage() . "');</script>";
    }
}

// Fetch issue data
$query = "SELECT ip.*, pi.product_name, p.status, s.store_name
            FROM issue_product ip
            JOIN product p ON ip.product_id = p.product_id
            JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
            JOIN stores s ON p.store_id = s.store_id
            ORDER BY ip.report_date DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Issue Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./respontive.css">
    <style>
        .issue-image {
            max-width: 100px;
            height: auto;
        }
        .modal-body img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <button id="menu-toggle">☰</button>
    <header id="banner">
        <a id="user-info">Name: <?php echo $name . ' ' . $surname; ?> | Role: <?php echo $role; ?></a>
        <button class="btn btn-danger" onclick="window.location.href='../../auth/logout.php'">Log Out</button>
    </header>
    <div id="sidebar">
        <h4 class="text-center">Menu</h4>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_user.php">Manage Users</a>
        <a href="manage_store.php">Manage Stores</a>
        <a href="product_menu.php">Product Menu</a>
        <a href="order_management.php">Order reqeuest</a>
        <a href="product_management.php">Product report</a>
        <a href="notification-settings.php">Notification Settings</a>
        <a href="reports.php">Reports</a>
    </div>

    <div class="container-fluid" id="main-content">
        <h2 class="mt-4 mb-4">Product Issues</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Store Name</th>
                        <th>Product Name</th>
                        <th>Issue Type</th>
                        <th>Description</th>
                        <th>Report Date</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['store_name']); ?></td> 
                        <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['issue_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['issue_description']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['report_date'])); ?></td>
                        <td>
                            <?php if ($row['issue_image']): ?>
                            <img src="../../upload/issue_pic/<?php echo $row['issue_image']; ?>" class="issue-image" alt="Issue Image">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['status'] ?? 'Pending'); ?></td>
                        <td>
                            <?php if (!in_array($row['status'], ['replace', 'unusable'])): ?>
                            <button class="btn btn-primary btn-sm" data-toggle="modal" 
                                    data-target="#resolutionModal" 
                                    data-issue-id="<?php echo $row['issueproduct_id']; ?>"
                                    data-product-id="<?php echo $row['product_id']; ?>">
                                Resolve
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Resolution Modal -->
    <div class="modal fade" id="resolutionModal" tabindex="-1" role="dialog" aria-labelledby="resolutionModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resolutionModalLabel">Submit Resolution</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="issue_id" id="issue_id">
                        <input type="hidden" name="product_id" id="product_id">
                        
                        <div class="form-group">
                            <label for="resolution_type">Resolution Type</label>
                            <select class="form-control" id="resolution_type" name="resolution_type" required>
                                <option value="">Select resolution type</option>
                                <option value="replace">Replace</option>
                                <option value="reject">Reject</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="resolution_description">Description</label>
                            <textarea class="form-control" id="resolution_description" name="resolution_description" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="resolution_image">Resolution Image</label>
                            <input type="file" class="form-control-file" id="resolution_image" name="resolution_image">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="submit_resolution" class="btn btn-primary">Submit Resolution</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('sidebar-active');
        });

        // Handle modal data
        $('#resolutionModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var issueId = button.data('issue-id');
            var productId = button.data('product-id');
            
            var modal = $(this);
            modal.find('#issue_id').val(issueId);
            modal.find('#product_id').val(productId);
        });
    </script>
</body>
</html>