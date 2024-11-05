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
// Query to get notifications for the current store with resolve types
$query1 = "SELECT nr.*, u.name, u.surname 
           FROM notiflyreport nr
           LEFT JOIN users u ON nr.user_id = u.user_id
           WHERE nr.store_id = ? 
           AND nr.notiflyreport_type IN ('resolve_order', 'resolve_product', 'con_order', 'can_order' , 'ship_order')
           AND nr.status = 'unread'
           ORDER BY nr.created_at DESC";

$stmt1 = $conn->prepare($query1);
$stmt1->bind_param("i", $store_id);
$stmt1->execute();
$result1 = $stmt1->get_result();

// Fetch unread notifications
$query2 = "SELECT np.*, pi.product_name, p.quantity, p.expiration_date 
           FROM notiflyproduct np
           JOIN products_info pi ON np.listproduct_id = pi.listproduct_id
           JOIN product p ON np.product_id = p.product_id
           WHERE np.store_id = ? AND np.status = 'unread'
           ORDER BY np.created_at DESC";

$stmt2 = $conn->prepare($query2);
$stmt2->bind_param("i", $store_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

$stmt1->close();
$stmt2->close();
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
    <style>
        /* Styling for notification items */
        .notification-item {
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            font-weight: bold;
        }
        .notification-item .date, .notification-item .type, .notification-item .reporter, .notification-item .status {
            margin-bottom: 5px;
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
        <a href="scaning_product.php">Scanning Product</a>
        <a href="resolution.php">Product Report</a>
        <a href="inventory.php">Inventory</a>
        <a href="reports.php">Reports</a>
    </div>
    <div class="container mt-5" id="main-content">
    <h2>Notifications</h2>

    <?php 
    $hasNotifications = $result1->num_rows > 0 || $result2->num_rows > 0; // ตรวจสอบว่ามีการแจ้งเตือนอย่างน้อย 1 อย่างหรือไม่
    ?>

    <?php if ($result1->num_rows > 0): ?>
        <?php while ($row = $result1->fetch_assoc()): ?>
            <div class="notification-item <?php echo $row['status'] === 'unread' ? 'unread' : ''; ?>">
                <div class="date">Date: <?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></div>
                <div class="type">Type: 
                    <?php 
                    if($row['notiflyreport_type'] === 'resolve_order') {
                        echo 'Order Resolve';
                    } elseif($row['notiflyreport_type'] === 'resolve_product') {
                        echo 'Product Resolve';
                    } elseif($row['notiflyreport_type'] === 'con_order') {
                        echo 'Confirm Order';
                    } elseif($row['notiflyreport_type'] === 'can_order') {
                        echo 'Cancel Order';
                    } else {
                        echo 'Ship Order';
                    }
                    ?>
                </div>
                <div class="reporter">Reporter: <?php echo $row['name'] . ' ' . $row['surname']; ?></div>
                <div class="status">Status: <?php echo ucfirst($row['status']); ?></div>
                <div class="action">
                    <?php if ($row['order_id']): ?>
                        <a href="tracking.php?order_id=<?php echo $row['order_id']; ?>&notiflyreport_id=<?php echo $row['notiflyreport_id']; ?>"  
                           class="btn btn-primary btn-sm">
                            View Order
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($row['product_id']): ?>
                        <a href="resolution.php?product_id=<?php echo $row['product_id']; ?>&notiflyreport_id=<?php echo $row['notiflyreport_id']; ?>" 
                           class="btn btn-info btn-sm">
                            View Product
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <?php if ($result2->num_rows > 0): ?>
        <?php while ($row = $result2->fetch_assoc()): ?>
            <div class="notification-item <?php echo $row['status'] === 'unread' ? 'unread' : ''; ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title"><?php echo htmlspecialchars($row['product_name']); ?></h5>
                        <?php if ($row['alert_type'] === 'low_stock'): ?>
                            <p class="card-text text-danger">
                                Low Stock Alert: Currently <?php echo $row['quantity']; ?> units remaining
                            </p>
                        <?php elseif ($row['alert_type'] === 'near_exp'): ?>
                            <p class="card-text text-warning">
                                Expiration Alert: Expires on <?php echo date('Y-m-d', strtotime($row['expiration_date'])); ?>
                            </p>
                        <?php else: ?>
                            <p class="card-text text-secondary">
                                Expiration Alert: Expires on <?php echo date('Y-m-d', strtotime($row['expiration_date'])); ?>
                            </p>
                        <?php endif; ?>
                        <p class="timestamp">Notified: <?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></p>
                        <div class="status">Status: <?php echo ucfirst($row['status']); ?></div>
                        <?php if ($row['product_id']): ?>
                        <a href="inventory.php?product_id=<?php echo $row['product_id']; ?>&notiflyproduct_id=<?php echo $row['notiflyproduct_id']; ?>" 
                           class="btn btn-danger btn-sm">
                            View Product
                        </a>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>

    <!-- ใช้เงื่อนไข else เพียงหนึ่งเดียวสำหรับทั้งสองฟอร์ม -->
    <?php if (!$hasNotifications): ?>
        <div class="alert alert-info text-center">
            No unread notifications at this time.
        </div>
    <?php endif; ?>
</div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('sidebar-active');
        });
    </script>
</body>
</html>