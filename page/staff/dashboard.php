<?php
session_start();
include('../../config/db.php');
  // เปลี่ยนเส้นทางการเชื่อมต่อฐานข้อมูล
  if ($_SESSION['role'] !== 'staff' || $_SESSION['store_id'] === null) {
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
$store_id = $_SESSION['store_id'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['markAsRead'])) {
    $notiflyproduct_id = $_POST['notiflyproduct_id'];
    $updateQuery = "UPDATE notiflyproduct SET status = 'read', read_at = CURRENT_TIMESTAMP WHERE notiflyproduct_id = ? AND store_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("ii", $notiflyproduct_id, $store_id);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Redirect to refresh the page
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Fetch unread notifications
$query = "SELECT np.*, pi.product_name, p.quantity, p.expiration_date 
          FROM notiflyproduct np
          JOIN products_info pi ON np.listproduct_id = pi.listproduct_id
          JOIN product p ON np.product_id = p.product_id
          WHERE np.store_id = ? AND np.status = 'unread'
          ORDER BY np.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $store_id);
$stmt->execute();
$result = $stmt->get_result();
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
    <style>
        /* Styles for notification cards */
        .notification-card {
            margin-bottom: 15px;
            border-left: 4px solid;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .low-stock {
            border-left-color: #dc3545; /* Red border for low stock */     
        }
        .near-exp {
            border-left-color: #ffc107; /* Yellow border for near expiration */
        }
        .expired {
            border-left-color: #6c757d;
        }
        .timestamp {
            font-size: 0.85em;
            color: #6c757d;
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
        <a href="inventory.php">Inventory</a>
    </div>
    <div class="container mt-5" id="main-content">
        <h2>Product Notifications</h2>
        
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="notification-card <?php echo $row['alert_type'] === 'low_stock' ? 'low-stock' : ($row['alert_type'] === 'near_exp' ? 'near-exp' : 'expired'); ?>">
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
                        </div>
                        <form method="POST">
                            <input type="hidden" name="notiflyproduct_id" value="<?php echo $row['notiflyproduct_id']; ?>">
                            <button type="submit" name="markAsRead" class="btn btn-outline-secondary btn-sm">
                                Mark as Read
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
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
        });
    </script>
</body>
</html>
    