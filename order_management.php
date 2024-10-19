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

// Function to generate a unique barcode
function generateBarcode() {
    return uniqid() . rand(1000, 9999);
}

// Handle order processing
if (isset($_POST['process_order'])) {
    $order_id = $_POST['order_id'];
    
    // Update order status to 'processing'
    $update_order = $conn->prepare("UPDATE orders SET order_status = 'processing' WHERE order_id = ?");
    $update_order->bind_param("i", $order_id);
    $update_order->execute();

    // Get order details
    $get_details = $conn->prepare("SELECT * FROM detail_orders WHERE order_id = ?");
    $get_details->bind_param("i", $order_id);
    $get_details->execute();
    $details_result = $get_details->get_result();

    while ($detail = $details_result->fetch_assoc()) {
        $listproduct_id = $detail['listproduct_id'];
        $quantity = $detail['quantity_set'];

        for ($i = 0; $i < $quantity; $i++) {
            $barcode = generateBarcode();
            $store_id = $_SESSION['store_id'];  // Assuming store_id is stored in session

            // Insert into product table
            $insert_product = $conn->prepare("INSERT INTO product (listproduct_id, store_id, barcode, status, quantity) VALUES (?, ?, ?, 'available', 1)");
            $insert_product->bind_param("iis", $listproduct_id, $store_id, $barcode);
            $insert_product->execute();
        }
    }
}

// Fetch orders
$orders_query = "SELECT o.*, s.store_name FROM orders o JOIN stores s ON o.store_id = s.store_id ORDER BY o.order_date DESC";
$orders_result = $conn->query($orders_query);

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
        <a href="notification-settings.php">Notification Settings</a>
        <a href="reports.php">Reports</a>
    </div>
    <div class="container-fluid" id="main-content">
        <h2>Order Management</h2>
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
                    <td><?php echo $order['order_status']; ?></td>
                    <td>
                        <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-info btn-sm">View Details</a>
                        <?php if ($order['order_status'] == 'confirm'): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <button type="submit" name="process_order" class="btn btn-success btn-sm">Process Order</button>
                        </form>
                        <?php endif; ?>
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