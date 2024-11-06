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

// Fetch orders
$orders_query = "SELECT o.*, s.store_name FROM orders o 
                JOIN stores s ON o.store_id = s.store_id 
                ORDER BY o.order_date DESC";
$orders_result = $conn->query($orders_query);
// Fetch orders with optional status filter
$order_status = isset($_GET['order_status']) ? $_GET['order_status'] : '';
$store_id = isset($_GET['store_id']) ? $_GET['store_id'] : '';

// Base query
$orders_query = "SELECT o.*, s.store_name FROM orders o 
                 JOIN stores s ON o.store_id = s.store_id";

// Add filters if selected
$conditions = [];
$params = [];
$param_types = '';
// Add status filter if selected
if ($order_status) {
    $conditions[] = "o.order_status = ?";
    $params[] = $order_status;
    $param_types .= 's';
}

if ($store_id) {
    $conditions[] = "o.store_id = ?";
    $params[] = $store_id;
    $param_types .= 'i';
}

if ($conditions) {
    $orders_query .= " WHERE " . implode(" AND ", $conditions);
}

$orders_query .= " ORDER BY o.order_date DESC";

// Prepare and execute query
if ($conditions) {
    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $orders_result = $stmt->get_result();
} else {
    $orders_result = $conn->query($orders_query);
}

if (isset($_GET['notiflyreport_id'])) {
    $notiflyreport_id = $_GET['notiflyreport_id'];
    
    $update_status_query = $conn->prepare("UPDATE notiflyreport SET status = 'read' WHERE notiflyreport_id = ?");
    $update_status_query->bind_param("i", $notiflyreport_id);
    $update_status_query->execute();
}
$stores_query = "SELECT store_id, store_name FROM stores";
$stores_result = $conn->query($stores_query);

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
        <h2>Order Management</h2>
        <!-- Order Status Filter Form -->
        <form method="GET" id="statusFilterForm" class="form-inline mb-3">
            <label for="order_status" class="mr-2">Filter by Order Status:</label>
            <select name="order_status" id="order_status" class="form-control mr-2">
                <option value="">All</option>
                <option value="paid" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'paid') echo 'selected'; ?>>Paid</option>
                <option value="confirm" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'confirm') echo 'selected'; ?>>Confirm</option>
                <option value="cancel" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'cancel') echo 'selected'; ?>>Cancel</option>
                <option value="shipped" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'shipped') echo 'selected'; ?>>Shipped</option>
                <option value="delivered" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'delivered') echo 'selected'; ?>>Delivered</option>
                <option value="issue" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'issue') echo 'selected'; ?>>Issue</option>
                <option value="refund" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'refund') echo 'selected'; ?>>Refund</option>
                <option value="return_shipped" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'return_shipped') echo 'selected'; ?>>Return Shipped</option>
                <option value="completed" <?php if (isset($_GET['order_status']) && $_GET['order_status'] == 'completed') echo 'selected'; ?>>Completed</option>
            </select>
            <label for="store_id" class="mr-2">Filter by Store:</label>
            <select name="store_id" id="store_id" class="form-control mr-2">
                <option value="">All Stores</option>
                <?php while ($store = $stores_result->fetch_assoc()): ?>
                    <option value="<?php echo $store['store_id']; ?>" 
                        <?php if (isset($_GET['store_id']) && $_GET['store_id'] == $store['store_id']) echo 'selected'; ?>>
                        <?php echo $store['store_name']; ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn btn-primary">Apply Filter</button>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Store</th>
                    <th>Total Amount</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $order['order_id']; ?></td>
                    <td><?php echo $order['store_name']; ?></td>
                    <td><?php echo $order['total_amount']; ?></td>
                    <td><?php echo $order['order_date']; ?></td>
                    <td>
                        <span class="badge badge-<?php 
                            switch ($order['order_status']) {
                                case 'paid':
                                    echo 'info'; // สีเขียว
                                    break;
                                case 'confirm':
                                    echo 'success'; // สีฟ้า
                                    break;
                                case 'cancel':
                                    echo 'danger'; // สีแดง
                                    break;
                                case 'shipped':
                                    echo 'warning'; // สีเหลือง
                                    break;
                                case 'delivered':
                                    echo 'primary'; // สีฟ้าน้ำทะเล
                                    break;
                                case 'issue':
                                    echo 'danger'; // สีแดง
                                    break;
                                case 'refund':
                                    echo 'warning'; // สีเหลืองทอง
                                    break;
                                case 'return_shipped':
                                    echo 'warning'; // สีเทา
                                    break;
                                case 'completed':
                                    echo 'success'; // สีเขียว
                                    break;
                                default:
                                    echo 'light'; // สีพื้นฐาน (ขาว)
                                    break;
                            }
                        ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info btn-sm">View Details</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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