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
if (isset($_GET['notiflyreport_id'])) {
    $notiflyreport_id = $_GET['notiflyreport_id'];
    
    $update_status_query = $conn->prepare("UPDATE notiflyreport SET status = 'read' WHERE notiflyreport_id = ?");
    $update_status_query->bind_param("i", $notiflyreport_id);
    $update_status_query->execute();
}
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
$store_id = $_SESSION['store_id'];

// Fetch orders for the specific store
$query = "SELECT o.*, 
    COUNT(do.detail_order_id) as total_items,
    GROUP_CONCAT(CONCAT(do.quantity_set, 'x $', do.price) SEPARATOR ', ') as order_details
    FROM orders o
    LEFT JOIN detail_orders do ON o.order_id = do.order_id
    WHERE o.store_id = ?
    GROUP BY o.order_id
    ORDER BY o.order_date DESC";

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
        <a href="resolution.php">Product Report</a>
        <a href="inventory.php">Inventory</a>
        <a href="reports.php">Reports </a>
    </div>
    <div class="container" id="main-content">
        <h2>Order Tracking</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                        <th>Order Date</th>
                        <th>Shipping Date</th>
                        <th>Delivered Date</th>
                        <th>Total Items</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo match($order['order_status']) {
                                        'paid' => 'info',
                                        'confirm' => 'primary',
                                        'shipped' => 'warning',
                                        'delivered' => 'success',
                                        'completed' => 'success',
                                        'issue' => 'danger',
                                        'cancel' => 'secondary',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $order['shipping_date'] ? date('Y-m-d H:i', strtotime($order['shipping_date'])) : '-'; ?></td>
                            <td><?php echo $order['delivered_date'] ? date('Y-m-d H:i', strtotime($order['delivered_date'])) : '-'; ?></td>
                            <td><?php echo $order['total_items']; ?></td>
                            <td>
                                <?php if ($order['order_status'] === 'paid' || $order['order_status'] === 'confirm' || $order['order_status'] === 'cancel' || $order['order_status'] === 'shipped' || $order['order_status'] === 'delivered' || $order['order_status'] === 'issue' || $order['order_status'] === 'refund' || $order['order_status'] === 'return_shipped' || $order['order_status'] === 'completed') { ?>
                                    <button class="btn btn-primary btn-sm view-details" 
                                            data-order-id="<?php echo $order['order_id']; ?>"
                                            data-toggle="modal" 
                                            data-target="#orderDetailModal">
                                        Order Details
                                    </button>
                                <?php } ?>
                                <?php if ($order['order_status'] === 'shipped'|| $order['order_status'] === 'delivered' || $order['order_status'] === 'issue' || $order['order_status'] === 'refund' || $order['order_status'] === 'return_shipped' || $order['order_status'] === 'completed') { ?>
                                    <button class="btn btn-info btn-sm view-products" 
                                            data-order-id="<?php echo $order['order_id']; ?>"
                                            data-toggle="modal" 
                                            data-target="#productDetailModal">
                                        Product Details
                                    </button>
                                <?php } ?>
                                <?php if ($order['order_status'] === 'issue' || $order['order_status'] === 'refund' || $order['order_status'] === 'return_shipped')  { ?>
                                    <button class="btn btn-danger btn-sm view-issue" 
                                            data-order-id="<?php echo $order['order_id']; ?>"
                                            data-toggle="modal" 
                                            data-target="#issueDetailModal">
                                        Isseue reports
                                    </button>
                                <?php } ?>
                                <?php if ($order['order_status'] === 'refund' || $order['order_status'] === 'return_shipped')  { ?>
                                    <button class="btn btn-warning btn-sm view-resolution" 
                                            data-order-id="<?php echo $order['order_id']; ?>"
                                            data-toggle="modal" 
                                            data-target="#resolutionDetailModal">
                                        Resolution reports
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal fade" id="orderDetailModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productDetailModalLabel">Order Detail</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="orderDetailContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
     <!-- Product Detail Modal -->
    <div class="modal fade" id="productDetailModal" tabindex="-1" role="dialog" aria-labelledby="productDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productDetailModalLabel">Order Products</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="productDetailContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Issue Detail Modal -->
    <div class="modal fade" id="issueDetailModal" tabindex="-1" role="dialog" aria-labelledby="issueDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="issueDetailModalLabel">Product Issue</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="issueDetailContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Resolition Detail Modal -->
    <div class="modal fade" id="resolutionDetailModal" tabindex="-1" role="dialog" aria-labelledby="resolutionDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resolutionDetailModalLabel">Products Relosution</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="resolutionDetailContent">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
        $(document).ready(function() {
            // สำหรับปุ่ม Order Details
            $('.view-details').click(function() {
                const orderId = $(this).data('order-id');
                
                // Load order details via AJAX
                $.ajax({
                    url: 'get_details.php',
                    type: 'POST',
                    data: { 
                        order_id: orderId,
                        view_type: 'order'
                    },
                    success: function(response) {
                        $('#orderDetailContent').html(response);
                    },
                    error: function() {
                        $('#orderDetailContent').html('<div class="alert alert-danger">Error loading order details</div>');
                    }
                });
            });

            // สำหรับปุ่ม Product Details
            $('.view-products').click(function() {
                const orderId = $(this).data('order-id');
                
                // Load product details via AJAX
                $.ajax({
                    url: 'get_details.php',
                    type: 'POST',
                    data: { 
                        order_id: orderId,
                        view_type: 'product'
                    },
                    success: function(response) {
                        $('#productDetailContent').html(response);
                    },
                    error: function() {
                        $('#productDetailContent').html('<div class="alert alert-danger">Error loading product details</div>');
                    }
                });
            });

            $('.view-issue').click(function() {
                const orderId = $(this).data('order-id');
                
                // Load product details via AJAX
                $.ajax({
                    url: 'get_details.php',
                    type: 'POST',
                    data: { 
                        order_id: orderId,
                        view_type: 'issue'
                    },
                    success: function(response) {
                        $('#issueDetailContent').html(response);
                    },
                    error: function() {
                        $('#issueDetailContent').html('<div class="alert alert-danger">Error loading issue details</div>');
                    }
                });
            });
            $('.view-resolution').click(function() {
                const orderId = $(this).data('order-id');
                
                // Load resolution details via AJAX
                $.ajax({
                    url: 'get_details.php',
                    type: 'POST',
                    data: { 
                        order_id: orderId,
                        view_type: 'resolution'
                    },
                    success: function(response) {
                        $('#resolutionDetailContent').html(response);
                    },
                    error: function() {
                        $('#resolutionDetailContent').html('<div class="alert alert-danger">Error loading resolution details</div>');
                    }
                });
            }); 
        });
    </script>
</body>
</html>