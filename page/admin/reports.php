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
// ดึงข้อมูลสินค้าตามช่วงเวลา
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'inventory';
$report_data = [];

if ($start_date && $end_date) {
    switch ($report_type) {
        case 'inventory':
            $query = "SELECT p.product_id, pi.product_name, s.store_name, p.expiration_date, 
                             p.status, p.quantity, p.manufacture_date, p.updated_at
                      FROM product p
                      JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                      JOIN stores s ON p.store_id = s.store_id
                      WHERE p.updated_at BETWEEN ? AND ?
                      ORDER BY p.updated_at DESC";
            break;

        case 'orders':
            $query = "SELECT o.order_id, s.store_name, o.order_status, o.total_amount,
                             p.payment_method, p.payment_date, o.order_date,
                             o.shipping_date, o.delivered_date,
                             COUNT(do.detail_order_id) as total_items
                      FROM orders o
                      JOIN stores s ON o.store_id = s.store_id
                      JOIN payments p ON o.order_id = p.order_id
                      LEFT JOIN detail_orders do ON o.order_id = do.order_id
                      WHERE o.order_date BETWEEN ? AND ?
                      GROUP BY o.order_id
                      ORDER BY o.order_date DESC";
            break;

        case 'withdrawals':
            $query = "SELECT w.withdraw_id, w.withdraw_quantity, w.transaction_date,
                             u.name, u.surname, pi.product_name, s.store_name
                      FROM withdrawreport w
                      JOIN users u ON w.user_id = u.user_id
                      JOIN product p ON w.product_id = p.product_id
                      JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                      JOIN stores s ON w.store_id = s.store_id
                      WHERE w.transaction_date BETWEEN ? AND ?
                      ORDER BY w.transaction_date DESC";
            break;

        case 'alerts':
            $query = "SELECT pi.product_name, pas.low_stock_threshold, 
                             pas.expiry_alert_days, pas.updated_at,
                             (SELECT COUNT(*) FROM product p 
                              WHERE p.listproduct_id = pi.listproduct_id 
                              AND p.quantity <= pas.low_stock_threshold) as low_stock_count,
                             (SELECT COUNT(*) FROM product p 
                              WHERE p.listproduct_id = pi.listproduct_id 
                              AND DATEDIFF(p.expiration_date, CURDATE()) <= pas.expiry_alert_days) as expiring_soon_count
                      FROM product_alert_settings pas
                      JOIN products_info pi ON pas.listproduct_id = pi.listproduct_id
                      WHERE pas.updated_at BETWEEN ? AND ?
                      ORDER BY pas.updated_at DESC";
            break;

        case 'order_details':
            $query = "SELECT o.order_id, s.store_name, o.order_status,
                             pi.product_name, do.quantity_set, do.price,
                             (do.quantity_set * do.price) as subtotal,
                             o.order_date, p.payment_method
                      FROM orders o
                      JOIN stores s ON o.store_id = s.store_id
                      JOIN detail_orders do ON o.order_id = do.order_id
                      JOIN products_info pi ON do.listproduct_id = pi.listproduct_id
                      JOIN payments p ON o.order_id = p.order_id
                      WHERE o.order_date BETWEEN ? AND ?
                      ORDER BY o.order_date DESC, o.order_id, pi.product_name";
            break;
        
        case 'product_issues':
            $query = "SELECT ip.issueproduct_id, ip.product_id, pi.product_name, 
                                ip.issue_type, ip.issue_description, ip.report_date,
                                s.store_name
                        FROM issue_product ip
                        JOIN product p ON ip.product_id = p.product_id
                        JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                        JOIN stores s ON p.store_id = s.store_id
                        WHERE ip.report_date BETWEEN ? AND ?
                        ORDER BY ip.report_date DESC";
            break;

        case 'order_issues':
            $query = "SELECT io.issue_id, io.order_id, io.issue_type,
                                io.issue_description, io.report_date,
                                pi.product_name, s.store_name
                        FROM issue_orders io
                        JOIN orders o ON io.order_id = o.order_id
                        LEFT JOIN product p ON io.product_id = p.product_id
                        LEFT JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                        JOIN stores s ON o.store_id = s.store_id
                        WHERE io.report_date BETWEEN ? AND ?
                        ORDER BY io.report_date DESC";
            break;

        case 'product_resolutions':
            $query = "SELECT rp.resolutionproduct_id, rp.product_id,
                                pi.product_name, rp.resolution_type,
                                rp.resolution_description, rp.resolution_date,
                                s.store_name
                        FROM resolution_product rp
                        JOIN product p ON rp.product_id = p.product_id
                        JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                        JOIN stores s ON p.store_id = s.store_id
                        WHERE rp.resolution_date BETWEEN ? AND ?
                        ORDER BY rp.resolution_date DESC";
            break;

        case 'order_resolutions':
            $query = "SELECT ro.resolution_id, ro.order_id,
                                ro.resolution_type, ro.resolution_info,
                                ro.resolution_date, s.store_name
                        FROM resolution_orders ro
                        JOIN orders o ON ro.order_id = o.order_id
                        JOIN stores s ON o.store_id = s.store_id
                        WHERE ro.resolution_date BETWEEN ? AND ?
                        ORDER BY ro.resolution_date DESC";
            break;

        case 'damaged_products':
            $query = "SELECT dp.deproduct_id, dp.product_id, pi.product_name, 
                                s.store_name, dp.deproduct_type, dp.created_at,
                                p.quantity as remaining_quantity,
                                p.expiration_date
                        FROM damaged_products dp
                        JOIN product p ON dp.product_id = p.product_id
                        JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                        JOIN stores s ON dp.store_id = s.store_id
                        WHERE dp.created_at BETWEEN ? AND ?
                        ORDER BY dp.created_at DESC";
            break;

        case 'product_management':
            $query = "SELECT nr.notiflyreport_id, nr.notiflyreport_type, 
                                CASE 
                                WHEN nr.product_id IS NOT NULL THEN pi.product_name
                                WHEN nr.order_id IS NOT NULL THEN CONCAT('Order #', nr.order_id)
                                END as item_name,
                                u.name, u.surname, s.store_name,
                                nr.created_at, nr.status,
                                CASE nr.notiflyreport_type
                                WHEN 'issue_product' THEN 'Product Issue Reported'
                                WHEN 'resolve_product' THEN 'Product Issue Resolved'
                                WHEN 'add_product' THEN 'New Product Added'
                                WHEN 'order_product' THEN 'Product Ordered'
                                WHEN 'con_order' THEN 'Order Confirmed'
                                WHEN 'can_order' THEN 'Order Cancelled'
                                WHEN 'ship_order' THEN 'Order Shipped'
                                WHEN 'deli_order' THEN 'Order Delivered'
                                END as action_description
                        FROM notiflyreport nr
                        JOIN users u ON nr.user_id = u.user_id
                        JOIN stores s ON nr.store_id = s.store_id
                        LEFT JOIN product p ON nr.product_id = p.product_id
                        LEFT JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                        WHERE nr.created_at BETWEEN ? AND ?
                        ORDER BY nr.created_at DESC";
            break;
            case 'admin_history':
                $query = "SELECT tm.transactionm_id, u.name, u.surname, tm.transaction_type,
                            CASE 
                                WHEN tm.transaction_type LIKE '%_u' THEN 'User Management'
                                WHEN tm.transaction_type LIKE '%_s' THEN 'Store Management'
                                WHEN tm.transaction_type LIKE '%_p' THEN 'Product Management'
                            END as category,
                            CASE 
                                WHEN tm.transaction_type LIKE 'add_%' THEN 'Add'
                                WHEN tm.transaction_type LIKE 'edit_%' THEN 'Edit'
                                WHEN tm.transaction_type LIKE 'del_%' THEN 'Delete'
                            END as action,
                            tm.created_at
                        FROM transaction_manage tm
                        JOIN users u ON tm.user_id = u.user_id
                        WHERE tm.created_at BETWEEN ? AND ?
                        ORDER BY tm.created_at DESC";
                break;
    
            case 'notifications':
                $query = "SELECT np.notiflyproduct_id, pi.product_name, s.store_name,
                            np.alert_type, np.status, np.created_at, np.read_at
                        FROM notiflyproduct np
                        JOIN product p ON np.product_id = p.product_id
                        JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                        JOIN stores s ON np.store_id = s.store_id
                        WHERE np.created_at BETWEEN ? AND ?
                        ORDER BY np.created_at DESC";
                break;
    }
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report_data = $result->fetch_all(MYSQLI_ASSOC);
}
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
         .report-container {
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .report-header {
            margin-bottom: 20px;
        }
        .export-buttons {
            margin: 20px 0;
        }
        .status-check { color: #28a745; }
        .status-expired { color: #dc3545; }
        .status-nearing_expiration { color: #ffc107; }
        .status-completed { color: #28a745; }
        .status-cancelled { color: #dc3545; }
        .status-pending { color: #ffc107; }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
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
    <div class="container" id="main-content">
        <div class="report-container">
            <div class="report-header">
                <h2>Reports</h2>
                <p>Generate detailed reports</p>
            </div>

            <form id="reportForm" method="GET" class="mb-4">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="report_type">Report Type:</label>
                            <select id="report_type" name="report_type" class="form-control" required>
                                <option value="inventory" <?php echo $report_type == 'inventory' ? 'selected' : ''; ?>>Inventory Report</option>
                                <option value="orders" <?php echo $report_type == 'orders' ? 'selected' : ''; ?>>Orders Summary</option>
                                <option value="admin_history" <?php echo $report_type == 'admin_history' ? 'selected' : ''; ?>>Admin Management History</option>
                                <option value="notifications" <?php echo $report_type == 'notifications' ? 'selected' : ''; ?>>Product Notifications</option>
                                <option value="damaged_products" <?php echo $report_type == 'damaged_products' ? 'selected' : ''; ?>>Damaged Products</option>
                                <option value="product_management" <?php echo $report_type == 'product_management' ? 'selected' : ''; ?>>Product Management History</option>
                                <option value="order_details" <?php echo $report_type == 'order_details' ? 'selected' : ''; ?>>Order Details</option>
                                <option value="withdrawals" <?php echo $report_type == 'withdrawals' ? 'selected' : ''; ?>>Withdrawal Report</option>
                                <option value="alerts" <?php echo $report_type == 'alerts' ? 'selected' : ''; ?>>Alert Settings</option>
                                <option value="product_issues" <?php echo $report_type == 'product_issues' ? 'selected' : ''; ?>>Product Issues History</option>
                                <option value="order_issues" <?php echo $report_type == 'order_issues' ? 'selected' : ''; ?>>Order Issues History</option>
                                <option value="product_resolutions" <?php echo $report_type == 'product_resolutions' ? 'selected' : ''; ?>>Product Resolution History</option>
                                <option value="order_resolutions" <?php echo $report_type == 'order_resolutions' ? 'selected' : ''; ?>>Order Resolution History</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">Start Date:</label>
                            <input type="date" id="start_date" name="start_date" 
                                   class="form-control" value="<?php echo $start_date; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">End Date:</label>
                            <input type="date" id="end_date" name="end_date" 
                                   class="form-control" value="<?php echo $end_date; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">Generate Report</button>
                        </div>
                    </div>
                </div>
            </form>

            <?php if (!empty($report_data)): ?>
                <div class="export-buttons">
                    <button onclick="exportToExcel()" class="btn btn-success mr-2">
                        Export to Excel
                    </button>
                    <button onclick="exportToPDF()" class="btn btn-danger">
                        Export to PDF
                    </button>
                </div>

                <div class="table-responsive">
                    <?php if ($report_type == 'inventory'): ?>
                        <!-- Inventory Report Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Store</th>
                                    <th>Status</th>
                                    <th>Quantity</th>
                                    <th>Manufacture Date</th>
                                    <th>Expiration Date</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td class="status-<?php echo $item['status']; ?>">
                                            <?php echo htmlspecialchars($item['status']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($item['manufacture_date']); ?></td>
                                        <td><?php echo htmlspecialchars($item['expiration_date']); ?></td>
                                        <td><?php echo htmlspecialchars($item['updated_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'damaged_products'): ?>
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Store</th>
                                    <th>Type</th>
                                    <th>Remaining Quantity</th>
                                    <th>Expiration Date</th>
                                    <th>Reported Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['deproduct_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo ucfirst(htmlspecialchars($item['deproduct_type'])); ?></td>
                                        <td><?php echo htmlspecialchars($item['remaining_quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($item['expiration_date']); ?></td>
                                        <td><?php echo htmlspecialchars($item['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'admin_history'): ?>
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Admin Name</th>
                                    <th>Category</th>
                                    <th>Action</th>
                                    <th>Date/Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['transactionm_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['name'] . ' ' . $item['surname']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category']); ?></td>
                                        <td><?php echo htmlspecialchars($item['action']); ?></td>
                                        <td><?php echo htmlspecialchars($item['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php elseif ($report_type == 'notifications'): ?>
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Store</th>
                                    <th>Alert Type</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Read At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['notiflyproduct_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td>
                                            <?php
                                            $alert_type_class = '';
                                            switch($item['alert_type']) {
                                                case 'low_stock':
                                                    $alert_type_class = 'text-warning';
                                                    break;
                                                case 'near_exp':
                                                    $alert_type_class = 'text-info';
                                                    break;
                                                case 'expired':
                                                    $alert_type_class = 'text-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $alert_type_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $item['alert_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $item['status'] == 'read' ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['created_at']); ?></td>
                                        <td><?php echo $item['read_at'] ? htmlspecialchars($item['read_at']) : '-'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'product_management'): ?>
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Transaction</th>
                                    <th>Item</th>
                                    <th>User Action</th>
                                    <th>Store</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['notiflyreport_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['action_description']); ?></td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['name'] . ' ' . $item['surname']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'orders'): ?>
                        <!-- Orders Summary Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Store</th>
                                    <th>Total Items</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Order Date</th>
                                    <th>Payment Date</th>
                                    <th>Shipping Date</th>
                                    <th>Delivery Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_orders = 0;
                                $total_amount = 0;
                                foreach ($report_data as $item): 
                                    $total_orders++;
                                    $total_amount += $item['total_amount'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['total_items']); ?></td>
                                        <td><?php echo number_format($item['total_amount'], 2); ?></td>
                                        <td class="status-<?php echo strtolower($item['order_status']); ?>">
                                            <?php echo htmlspecialchars($item['order_status']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($item['order_date']); ?></td>
                                        <td><?php echo htmlspecialchars($item['payment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($item['shipping_date']); ?></td>
                                        <td><?php echo htmlspecialchars($item['delivered_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="2">Totals:</td>
                                    <td><?php echo $total_orders; ?> orders</td>
                                    <td><?php echo number_format($total_amount, 2); ?></td>
                                    <td colspan="6"></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'product_issues'): ?>
                        <!-- Product Issues Report Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Issue ID</th>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Store</th>
                                    <th>Issue Type</th>
                                    <th>Description</th>
                                    <th>Report Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['issueproduct_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['issue_type']); ?></td>
                                        <td><?php echo htmlspecialchars($item['issue_description']); ?></td>
                                        <td><?php echo htmlspecialchars($item['report_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'withdrawals'): ?>
                        <!-- Withdrawals Report Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Withdrawal ID</th>
                                    <th>User</th>
                                    <th>Product</th>
                                    <th>Store</th>
                                    <th>Quantity</th>
                                    <th>Transaction Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_withdrawals = 0;
                                $total_quantity = 0;
                                foreach ($report_data as $item): 
                                    $total_withdrawals++;
                                    $total_quantity += $item['withdraw_quantity'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['withdraw_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['name'] . ' ' . $item['surname']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['withdraw_quantity']); ?></td>
                                        <td><?php echo htmlspecialchars($item['transaction_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="4">Totals:</td>
                                    <td><?php echo $total_quantity; ?> items</td>
                                    <td><?php echo $total_withdrawals; ?> withdrawals</td>
                                </tr>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'order_issues'): ?>
                        <!-- Order Issues Report Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Issue ID</th>
                                    <th>Order ID</th>
                                    <th>Store</th>
                                    <th>Product</th>
                                    <th>Issue Type</th>
                                    <th>Description</th>
                                    <th>Report Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['issue_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['issue_type']); ?></td>
                                        <td><?php echo htmlspecialchars($item['issue_description']); ?></td>
                                        <td><?php echo htmlspecialchars($item['report_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php elseif ($report_type == 'product_resolutions'): ?>
                        <!-- Product Resolutions Report Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Resolution ID</th>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Store</th>
                                    <th>Resolution Type</th>
                                    <th>Description</th>
                                    <th>Resolution Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['resolutionproduct_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['resolution_type']); ?></td>
                                        <td><?php echo htmlspecialchars($item['resolution_description']); ?></td>
                                        <td><?php echo htmlspecialchars($item['resolution_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'alerts'): ?>
                        <!-- Alert Settings Report Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Low Stock Threshold</th>
                                    <th>Expiry Alert Days</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['low_stock_threshold']); ?></td>
                                        <td><?php echo htmlspecialchars($item['expiry_alert_days']); ?></td>
                                        <td><?php echo htmlspecialchars($item['updated_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif ($report_type == 'order_resolutions'): ?>
                        <!-- Order Resolutions Report Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Resolution ID</th>
                                    <th>Order ID</th>
                                    <th>Store</th>
                                    <th>Resolution Type</th>
                                    <th>Description</th>
                                    <th>Resolution Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['resolution_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['resolution_type']); ?></td>
                                        <td><?php echo htmlspecialchars($item['resolution_info']); ?></td>
                                        <td><?php echo htmlspecialchars($item['resolution_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <!-- Order Details Table -->
                        <table id="reportTable" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Store</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                    <th>Status</th>
                                    <th>Payment Method</th>
                                    <th>Order Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_items = 0;
                                $total_amount = 0;
                                foreach ($report_data as $item): 
                                    $total_items += $item['quantity_set'];
                                    $total_amount += $item['subtotal'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['quantity_set']); ?></td>
                                        <td><?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo number_format($item['subtotal'], 2); ?></td>
                                        <td class="status-<?php echo strtolower($item['order_status']); ?>">
                                            <?php echo htmlspecialchars($item['order_status']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['payment_method']); ?></td>
                                        <td><?php echo htmlspecialchars($item['order_date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="3">Totals:</td>
                                    <td><?php echo $total_items; ?> items</td>
                                    <td></td>
                                    <td><?php echo number_format($total_amount, 2); ?></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>


    <script>
        // Toggle sidebar
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('main-content').classList.toggle('sidebar-active');
        });

        // Initialize DataTable
        $(document).ready(function() {
            $('#inventoryTable').DataTable({
                "pageLength": 10,
                "ordering": true,
                "info": true,
                "searching": true
            });
        });

        // Export to Excel
        function exportToExcel() {
            const table = document.getElementById('reportTable'); // แก้เป็น 'reportTable'
            const wb = XLSX.utils.table_to_book(table, {sheet: "Report"});
            const fileName = `report_${formatDate(new Date())}.xlsx`;
            XLSX.writeFile(wb, fileName);
        }

        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add report title
            doc.setFontSize(16);
            doc.text('Report', 14, 15);
            
            // Add date range
            doc.setFontSize(12);
            doc.text(`Period: ${document.getElementById('start_date').value} to ${document.getElementById('end_date').value}`, 14, 25);

            // Create table
            doc.autoTable({
                html: '#reportTable',
                startY: 35,
                styles: { fontSize: 8 },
                columnStyles: { 0: { cellWidth: 20 } }
            });

            // Save PDF
            const fileName = `report_${formatDate(new Date())}.pdf`;
            doc.save(fileName);
        }

        // Helper function to format date for filenames
        function formatDate(date) {
            return date.toISOString().split('T')[0];
        }
    </script>
</body>
</html>