<?php
session_start();
include('../../config/db.php');

if (!isset($_GET['id'])) {
    header('Location: order_management.php');
    exit;
}

$order_id = $_GET['id'];

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $new_status = $_POST['action'] === 'confirm' ? 'confirm' : 'cancel';
        $update_query = "UPDATE orders SET order_status = ? WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $order_id);
        $update_stmt->execute();
        
        // Refresh the page to show updated status
        header("Location: order_details.php?id=" . $order_id);
        exit;
    }
}

// Fetch order details
$order_query = "SELECT o.*, s.store_name FROM orders o JOIN stores s ON o.store_id = s.store_id WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = "SELECT do.*, pi.product_name FROM detail_orders do JOIN products_info pi ON do.listproduct_id = pi.listproduct_id WHERE do.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

// Fetch payment details
$payment_query = "SELECT * FROM payments WHERE order_id = ?";
$payment_stmt = $conn->prepare($payment_query);
$payment_stmt->bind_param("i", $order_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./respontive.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Order Details - Order #<?php echo $order['order_id']; ?></h2>
        <a href="order_management.php" class="btn btn-primary">Back to Order Management</a>
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">Order Information</h5>
                <p><strong>Store:</strong> <?php echo $order['store_name']; ?></p>
                <p><strong>Total Amount:</strong> <?php echo $order['total_amount']; ?></p>
                <p><strong>Order Date:</strong> <?php echo $order['order_date']; ?></p>
                <p><strong>Status:</strong> <span class="badge badge-<?php 
                    echo $order['order_status'] === 'confirm' ? 'success' : 
                        ($order['order_status'] === 'cancel' ? 'danger' : 'warning'); 
                    ?>"><?php echo $order['order_status']; ?></span></p>
                <?php while ($payment = $payment_result->fetch_assoc()): ?>
                    <p><strong>Date:</strong> <?php echo $payment['payment_date']; ?></p>
                    <?php if ($payment['payment_method'] === 'credit_card'): ?>
                        <p><strong>Payment Method:</strong> Credit Card</p>
                    <?php elseif ($payment['payment_pic']): ?>
                        <p><strong>Payment Method:</strong> PromptPay</p>
                        <div>
                            <strong>Payment Proof:</strong><br>
                            <img src="../manager/payment_proofs/<?php echo htmlspecialchars($payment['payment_pic']); ?>" 
                                alt="Payment Proof" style="max-width: 300px;" class="img-fluid">
                        </div>
                    <?php endif; ?>
                    </div>
                <?php endwhile; ?>
        </div>
                        
        <h3>Order Items</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $item['product_name']; ?></td>
                    <td><?php echo $item['quantity_set']; ?></td>
                    <td><?php echo $item['price']; ?></td>
                    <td><?php echo $item['quantity_set'] * $item['price']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
         <!-- Order Status Controls -->
         <?php if ($order['order_status'] === 'paid'): ?>
            <div class="mb-3">
                <form method="POST" class="d-inline mr-2">
                    <input type="hidden" name="action" value="confirm">
                    <button type="submit" class="btn btn-success">Confirm Order</button>
                </form>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit" class="btn btn-danger">Cancel Order</button>
                </form>
            </div>
            <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>