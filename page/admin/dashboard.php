<?php
session_start();
include('../../config/db.php');
  // เปลี่ยนเส้นทางการเชื่อมต่อฐานข้อมูล
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');  // เปลี่ยนเส้นทางการเช็ค role
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
$query = "SELECT nr.*, u.name, u.surname, s.store_name
          FROM notiflyreport nr
          LEFT JOIN users u ON nr.user_id = u.user_id
          LEFT JOIN stores s ON nr.store_id = s.store_id
          WHERE nr.notiflyreport_type IN ('issue_order', 'issue_product', 'add_product' , 'order_product', 'deli_order')
          AND nr.status = 'unread'
          ORDER BY nr.created_at DESC";

$stmt = $conn->prepare($query);
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
    <style>
        /* Styling for notification items */
        .notification-item {
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .notification-item.unread {
            font-weight: bold;
            border-left-color: #dc3545;
        }
        .notification-item .date, .notification-item .type, .notification-item .reporter, .notification-item .status {
            margin-bottom: 5px;
        }
        .action .btn {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <button id="menu-toggle">☰</button>
    <header id="banner">
        <a id="user-info">Name: <?php echo htmlspecialchars($name) . ' ' . htmlspecialchars($surname); ?> | Role: <?php echo htmlspecialchars($role); ?></a>
        <button class="btn btn-danger" onclick="window.location.href='../../auth/logout.php'">Log Out</button>
    </header>
    <div id="sidebar">
        <h4 class="text-center">Menu</h4>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_user.php">Manage Users</a>
        <a href="manage_store.php">Manage Stores</a>
        <a href="product_menu.php">Product Menu</a>
        <a href="order_management.php">Order Request</a>
        <a href="product_management.php">Product Report</a>
        <a href="notification-settings.php">Notification Settings</a>
        <a href="reports.php">Reports</a>
    </div>
    <div class="container mt-5" id="main-content">
        <h2>Notifications</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="notification-item <?php echo $row['status'] === 'unread' ? 'unread' : ''; ?>">
                    <div class="date">Date: <?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></div>
                    <div class="type">Type: 
                        <?php 
                        switch ($row['notiflyreport_type']) {
                            case 'resolve_order':
                                echo 'Order Resolve';
                                break;
                            case 'resolve_product':
                                echo 'Product Resolve';
                                break;
                            case 'con_order':
                                echo 'Confirm Order';
                                break;
                            case 'can_order':
                                echo 'Cancel Order';
                                break;
                            default:
                                echo 'Ship Order';
                        }
                        ?>
                    </div>
                    <div class="reporter">Reporter: <?php echo htmlspecialchars($row['name'] . ' ' . $row['surname']); ?></div>
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
        <?php else: ?>
            <div class="alert alert-info text-center">
                No notifications found
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