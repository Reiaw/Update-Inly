<style>
    .description-cell {
    word-wrap: break-word;
    white-space: normal;
    max-width: 200px; /* Optional: set max width if you want to limit cell width */
}
</style>
<?php
// get_details.php
session_start();
include('../../config/db.php');

if (!isset($_POST['order_id']) || !isset($_SESSION['store_id']) || !isset($_POST['view_type'])) {
    echo "Invalid request";
    exit;
}

$order_id = $_POST['order_id'];
$store_id = $_SESSION['store_id'];
$view_type = $_POST['view_type'];

// Query ข้อมูล order เพียงครั้งเดียว เพื่อให้ $order พร้อมใช้งานในทุกเงื่อนไข
$query = "SELECT * FROM orders WHERE order_id = ? AND store_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $store_id);
$stmt->execute();
$result = $stmt->get_result();

// ตรวจสอบว่ามีข้อมูลหรือไม่ และจัดเก็บข้อมูลไว้ใน $order
if ($result->num_rows > 0) {
    $order = $result->fetch_assoc();
} else {
    echo "<div class='alert alert-warning'>Order not found.</div>";
    exit;
}


if ($view_type === 'order') {
    // Existing order details code...
    $query = "SELECT do.*, pi.product_name, o.order_status, o.total_amount
              FROM detail_orders do
              JOIN orders o ON do.order_id = o.order_id
              JOIN products_info pi ON do.listproduct_id = pi.listproduct_id
              WHERE do.order_id = ? AND o.store_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $store_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $orderDetails = $result->fetch_all(MYSQLI_ASSOC);
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h6>Order Details #<?php echo $order_id; ?></h6>
                    <hr>
                    <?php if ($order['order_status'] === 'cancel') { ?>
                        <div class="alert alert-danger">
                            <strong>Order Canceled:</strong> 
                            <?php echo $order['cancel_info']; ?>
                            <?php if (!empty($order['cancel_pic'])) { ?>
                                <div>
                                    <img src="../../upload/cancel_payment/<?php echo $order['cancel_pic']; ?>" alt="Cancellation Picture" class="img-fluid mt-5" style="max-width: 500px; max-height: 500px;">
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderDetails as $detail) { ?>
                                <tr>
                                    <td><?php echo $detail['product_name']; ?></td>
                                    <td><?php echo $detail['quantity_set']; ?></td>
                                    <td>$<?php echo number_format($detail['price'], 2); ?></td>
                                    <td>$<?php echo number_format($detail['quantity_set'] * $detail['price'], 2); ?></td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Total Amount:</strong></td>
                                <td><strong>$<?php echo number_format($orderDetails[0]['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo "<div class='alert alert-warning'>No order details found for this order.</div>";
    }
} elseif ($view_type === 'product') {
    // Existing product details code...
    $query = "SELECT p.*, pi.product_name, p.quantity, p.expiration_date, p.manufacture_date, p.status, p.location
             FROM product p
             JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
             JOIN detail_orders do ON p.detail_order_id = do.detail_order_id
             JOIN orders o ON do.order_id = o.order_id
             WHERE o.order_id = ? AND o.store_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $store_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h6>Product Details for Order #<?php echo $order_id; ?></h6>
                    <hr>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Manufacture Date</th>
                                <th>Expiration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $product['product_name']; ?></td>
                                    <td><?php echo $product['quantity']; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo match($product['status']) {
                                                'prepare' => 'warning',
                                                'in_stock' => 'success',
                                                'expired' => 'danger',
                                                'low_stock' => 'info',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $product['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="description-cell"><?php echo nl2br($product['location'] ?? '-'); ?></td>
                                    <td><?php echo $product['manufacture_date'] ? date('Y-m-d', strtotime($product['manufacture_date'])) : '-'; ?></td>
                                    <td><?php echo $product['expiration_date'] ? date('Y-m-d', strtotime($product['expiration_date'])) : '-'; ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo "<div class='alert alert-warning'>No product details found for this order.</div>";
    }
} elseif ($view_type === 'issue') {
    // New code for issue details
    $query = "SELECT io.*, pi.product_name
              FROM issue_orders io
              LEFT JOIN product p ON io.product_id = p.product_id
              LEFT JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
              JOIN orders o ON io.order_id = o.order_id
              WHERE io.order_id = ? AND o.store_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $store_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h6>Issue Details for Order #<?php echo $order_id; ?></h6>
                    <hr>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Issue Type</th>
                                <th>Description</th>
                                <th>Issue Date</th>
                                <th>Issue Image</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($issue = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo $issue['product_name'] ?? 'N/A'; ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo match($issue['issue_type']) {
                                                'missing_item' => 'warning',
                                                'damaged_item' => 'danger',
                                                'incorrect_item' => 'info',
                                                'Expired or Quality Issue' => 'dark',
                                                'Damaged Packaging' => 'secondary',
                                                default => 'primary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $issue['issue_type'])); ?>
                                        </span>
                                    </td>
                                    <td class="description-cell"><?php echo nl2br($issue['issue_description'] ?: '-'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($issue['report_date'])); ?></td>
                                    <td>
                                        <?php if ($issue['issue_image']) { ?>
                                            <img src="../../<?php echo $issue['issue_image']; ?>" alt="Issue Image" style="max-width: 100px; max-height: 100px;">
                                        <?php } else { ?>
                                            No image
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo "<div class='alert alert-warning'>No issue details found for this order.</div>";
    }
} elseif ($view_type === 'resolution') {
    // New code for resolution details
    $query = "SELECT ro.*, o.order_status
              FROM resolution_orders ro
              JOIN orders o ON ro.order_id = o.order_id
              WHERE ro.order_id = ? AND o.store_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $store_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h6>Resolution Details for Order #<?php echo $order_id; ?></h6>
                    <hr>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Resolution Type</th>
                                <th>Information</th>
                                <th>Date</th>
                                <th>Image</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($resolution = $result->fetch_assoc()) { ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo match($resolution['resolution_type']) {
                                                'refund' => 'warning',
                                                'return_item' => 'info',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $resolution['resolution_type'])); ?>
                                        </span>
                                    </td>
                                    <td class="description-cell"><?php echo nl2br($resolution['resolution_info'] ?: '-'); ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($resolution['resolution_date'])); ?></td>
                                    <td>
                                        <?php if ($resolution['resolution_image']) { ?>
                                            <img src="../../upload/resolution_images/<?php echo $resolution['resolution_image']; ?>" 
                                                 alt="Resolution Image" 
                                                 style="max-width: 100px; max-height: 100px;">
                                        <?php } else { ?>
                                            No image
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    } else {
        echo "<div class='alert alert-warning'>No resolution details found for this order.</div>";
    }
}

$stmt->close();
$conn->close();

?>