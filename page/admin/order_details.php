<?php
session_start();
include('../../config/db.php');
require_once('../../vendor/autoload.php');
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\Exceptions\BarcodeException;

$user_id = $_SESSION['user_id'];
if (!isset($_GET['id'])) {
    header('Location: order_management.php');
    exit;
}
// Function to generate a unique barcode
function generateBarcode() {
    return uniqid() . rand(1000, 9999);
}

// Function to generate barcode image and store it in ../barcodes
function generateBarcodeImage($barcode) {
     // สร้างบาร์โค้ด
     $generator = new BarcodeGeneratorPNG();
     $barcode_data = $generator->getBarcode($barcode, $generator::TYPE_CODE_128);
     
     // กำหนดพาธที่ต้องการบันทึกไฟล์
     $barcode_img_path = '../../upload/barcodes/' . $barcode . '.png';
     
     // สร้างภาพจากข้อมูลบาร์โค้ดโดยใช้ GD
     $image = imagecreatefromstring($barcode_data);
     if ($image === false) {
         throw new BarcodeException('Failed to create image from barcode data');
     }
 
     // สร้างภาพที่มีพื้นหลังสีขาว
     $width = imagesx($image);
     $height = imagesy($image);
     $white_bg_image = imagecreatetruecolor($width, $height);
     
     // กำหนดสีพื้นหลังเป็นสีขาว
     $white = imagecolorallocate($white_bg_image, 255, 255, 255); 
     imagefill($white_bg_image, 0, 0, $white);
     
     // คัดลอกบาร์โค้ดลงในภาพที่มีพื้นหลังสีขาว
     imagecopy($white_bg_image, $image, 0, 0, 0, 0, $width, $height);
     
     // บันทึกภาพไปยังไฟล์
     imagepng($white_bg_image, $barcode_img_path);
     
     // ทำการลบภาพจากหน่วยความจำ
     imagedestroy($image);
     imagedestroy($white_bg_image);
 
     return $barcode_img_path;
}

// Handle order processing
if (isset($_POST['process_order'])) {
    $order_id = $_POST['order_id'];
    $expiration_dates = $_POST['expiration_date']; // Array of expiration dates for each detail_order_id
    $manufacture_dates = $_POST['manufacture_date'];

     // ตรวจสอบ manufacture_date และ expiration_date
     $current_date = new DateTime(); // วันที่ปัจจุบัน
     foreach ($manufacture_dates as $detail_order_id => $manufacture_date) {
         $expiration_date = $expiration_dates[$detail_order_id];
         $manufacture_date_obj = new DateTime($manufacture_date);
         $expiration_date_obj = new DateTime($expiration_date);
 
         // ตรวจสอบว่า manufacture_date ห้ามเกินวันปัจจุบัน
         if ($manufacture_date_obj > $current_date) {
                echo "<script>alert('Manufacture date cannot be in the future.');window.history.back();</script>";
                exit;
         }
 
         // ตรวจสอบว่า expiration_date ต้องมากกว่าวันปัจจุบัน
         if ($expiration_date_obj <= $current_date) {
                echo "<script>alert('Expiration date must be later than today.');window.history.back();</script>";
                exit;
         }
     }
    // สร้างบาร์โค้ดเฉพาะสำหรับทั้งออเดอร์
    $order_barcode = generateBarcode();
     // Generate barcode image and get image path
     $barcode_img_path = generateBarcodeImage($order_barcode);
    // Start transaction
    $conn->begin_transaction();
    try {
        $get_store_query = "SELECT store_id FROM orders WHERE order_id = ?";
        $get_store_stmt = $conn->prepare($get_store_query);
        $get_store_stmt->bind_param("i", $order_id);
        $get_store_stmt->execute();
        $store_result = $get_store_stmt->get_result();
        $store_data = $store_result->fetch_assoc();
        $store_id = $store_data['store_id']; // Retrieve store_id
        // Update order status to 'processing'
        $update_order = $conn->prepare("UPDATE orders SET order_status = 'shipped', shipping_date = CURRENT_TIMESTAMP, barcode = ?, barcode_pic = ? WHERE order_id = ?");
        $update_order->bind_param("ssi", $order_barcode, $barcode_img_path, $order_id);
        $update_order->execute();
        // Insert notification into notiflyreport table
            $notifyType = 'ship_order';
            $insertNotifySql = "INSERT INTO notiflyreport (user_id, order_id, notiflyreport_type, store_id) 
                            VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertNotifySql);
            $stmt->bind_param("iisi", $user_id, $order_id, $notifyType, $store_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to create notification');
            }
        // Get order details and store_id
        $get_store = $conn->prepare("SELECT store_id FROM orders WHERE order_id = ?");
        $get_store->bind_param("i", $order_id);
        $get_store->execute();
        $store_result = $get_store->get_result();
        $store_data = $store_result->fetch_assoc();
        $store_id = $store_data['store_id'];

        // Get order details with quantity_set from products_info
        $get_details = $conn->prepare("SELECT do.detail_order_id, do.listproduct_id, do.quantity_set, 
                                     pi.product_name, pi.quantity_set as product_quantity_set
                                     FROM detail_orders do 
                                     JOIN products_info pi ON do.listproduct_id = pi.listproduct_id
                                     WHERE do.order_id = ?");
        $get_details->bind_param("i", $order_id);
        $get_details->execute();
        $details_result = $get_details->get_result();

        while ($detail = $details_result->fetch_assoc()) {
            $detail_order_id = $detail['detail_order_id'];
            $listproduct_id = $detail['listproduct_id'];
            $order_quantity = $detail['quantity_set']; // Number of sets ordered
            $product_quantity = $detail['product_quantity_set']; // Quantity per set
            $expiration_date = $expiration_dates[$detail_order_id];
            $manufacture_date = $manufacture_dates[$detail_order_id];
            
            // Generate barcode for each item in the set
            for ($i = 0; $i < $order_quantity; $i++) {
                // Insert one record per item in the set
                $insert_product = $conn->prepare("INSERT INTO product (listproduct_id, store_id, 
                                           status, quantity, expiration_date, manufacture_date, detail_order_id, order_id) 
                                           VALUES (?, ?, 'check', ?, ?, ?, ?, ?)");
                $insert_product->bind_param("iiissii", $listproduct_id, $store_id, 
                                            $product_quantity, $expiration_date, $manufacture_date, $detail_order_id, $order_id);
                $insert_product->execute();
            }
        }
        if (!$update_order->execute()) {
            throw new Exception("Failed to update order with barcode information");
        }

        $conn->commit();
        // Add success message or redirect
    } catch (Exception $e) {
        error_log("Order processing failed: " . $e->getMessage());
        $conn->rollback();
    }
}
$order_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Fetch the store_id from the orders table
        $get_store_query = "SELECT store_id FROM orders WHERE order_id = ?";
        $get_store_stmt = $conn->prepare($get_store_query);
        $get_store_stmt->bind_param("i", $order_id);
        $get_store_stmt->execute();
        $store_result = $get_store_stmt->get_result();
        $store_data = $store_result->fetch_assoc();
        $store_id = $store_data['store_id']; // Retrieve store_id

        if ($_POST['action'] === 'confirm') {
            $new_status = 'confirm';
            $update_query = "UPDATE orders SET order_status = ? WHERE order_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $new_status, $order_id);
            $update_stmt->execute();

            // Insert notification into notiflyreport table
            $notifyType = 'con_order';
            $insertNotifySql = "INSERT INTO notiflyreport (user_id, order_id, notiflyreport_type, store_id) 
                            VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertNotifySql);
            $stmt->bind_param("iisi", $user_id, $order_id, $notifyType, $store_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to create notification');
            }
        } elseif ($_POST['action'] === 'cancel') {
            $new_status = 'cancel';
            $cancel_reason = $_POST['cancel_reason'];

            // Insert notification into notiflyreport table
            $notifyType = 'can_order';
            $insertNotifySql = "INSERT INTO notiflyreport (user_id, order_id, notiflyreport_type, store_id) 
                            VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertNotifySql);
            $stmt->bind_param("iisi", $user_id, $order_id, $notifyType, $store_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to create notification');
            }

            // Handle cancel image upload
            $cancel_pic = '';
            if (isset($_FILES['cancel_pic']) && $_FILES['cancel_pic']['error'] === 0) {
                $target_dir = "../../upload/cancel_payment/"; // Define the folder to save images
                $target_file = $target_dir . basename($_FILES['cancel_pic']['name']);
                move_uploaded_file($_FILES['cancel_pic']['tmp_name'], $target_file); // Save file to folder
                $cancel_pic = basename($_FILES['cancel_pic']['name']); // Save file name to the database
            }

            // Update the order with cancellation details
            $update_query = "UPDATE orders SET order_status = ?, cancel_info = ?, cancel_pic = ? WHERE order_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssi", $new_status, $cancel_reason, $cancel_pic, $order_id);
            $update_stmt->execute();
        }
    }
}


// Fetch order details with store information
$order_query = "SELECT o.*, s.store_name, s.tel_store FROM orders o 
                JOIN stores s ON o.store_id = s.store_id 
                WHERE o.order_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = "SELECT do.*, pi.product_name FROM detail_orders do 
                JOIN products_info pi ON do.listproduct_id = pi.listproduct_id 
                WHERE do.order_id = ?";
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
        <a href="order_management.php" type="button" class="btn btn-primary mb-3" >Back to Order Management</a>
        
        <?php if ($order['order_status'] == 'confirm'): ?>
            <button type="button" class="btn btn-success mb-3" data-toggle="modal" data-target="#processModal<?php echo $order['order_id']; ?>">Process Order</button>
            <!-- Process Order Modal -->
            <div class="modal fade" id="processModal<?php echo $order['order_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Set Dates</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <?php 
                                $detail_query = "SELECT do.*, pi.product_name 
                                                FROM detail_orders do 
                                                JOIN products_info pi ON do.listproduct_id = pi.listproduct_id 
                                                WHERE do.order_id = ?";
                                $detail_stmt = $conn->prepare($detail_query);
                                $detail_stmt->bind_param("i", $order['order_id']);
                                $detail_stmt->execute();
                                $detail_result = $detail_stmt->get_result();

                                while ($detail = $detail_result->fetch_assoc()):
                                ?>
                                    <div class="form-group">
                                        <label><?php echo $detail['product_name']; ?> (Quantity: <?php echo $detail['quantity_set']; ?>)</label>
                                        
                                            <div>
                                            <!-- Field สำหรับเลือก manufacture_date -->
                                            <label>Manufacture Date:</label>
                                            <input type="date" 
                                                name="manufacture_date[<?php echo $detail['detail_order_id']; ?>]" 
                                                class="form-control" 
                                                required>
                                            
                                            <!-- Field สำหรับเลือก expiration_date -->
                                            <label>Expiration Date:</label>
                                            <input type="date" 
                                                name="expiration_date[<?php echo $detail['detail_order_id']; ?>]" 
                                                class="form-control" 
                                                required>
                                            </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" name="process_order" class="btn btn-primary">Process Order</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($order['order_status'] === 'shipped' || $order['order_status'] === 'delivered' || $order['order_status'] === 'issue' || $order['order_status'] === 'issue' ) : ?>
        <button type="button" class="btn btn-info mb-3" data-toggle="modal" data-target="#productDetailsModal">
            View Product Details
        </button>
        <!-- Product Details Modal -->
        <div class="modal fade" id="productDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Product Details</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <?php if ($order['barcode']): ?>
                            <div class="text-center mb-4">
                                <h6>Order Barcode</h6>
                                <img src="../../upload/barcodes/<?php echo htmlspecialchars($order['barcode']); ?>.png" 
                                    alt="Order Barcode" class="img-fluid" style="max-width: 300px;">
                                <p class="mt-2">Barcode: <?php echo htmlspecialchars($order['barcode']); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Quantity per Set</th>
                                        <th>Manufacture Date</th>
                                        <th>Expiration Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $products_query = "SELECT p.*, pi.product_name, pi.quantity_set as qty_per_set, 
                                                    do.quantity_set as num_sets 
                                                    FROM product p
                                                    JOIN products_info pi ON p.listproduct_id = pi.listproduct_id
                                                    JOIN detail_orders do ON p.detail_order_id = do.detail_order_id
                                                    WHERE do.order_id = ?";
                                    $products_stmt = $conn->prepare($products_query);
                                    $products_stmt->bind_param("i", $order_id);
                                    $products_stmt->execute();
                                    $products_result = $products_stmt->get_result();
                                    
                                    while ($product = $products_result->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['qty_per_set']); ?></td>
                                            <td><?php echo htmlspecialchars($product['manufacture_date']); ?></td>
                                            <td><?php echo htmlspecialchars($product['expiration_date']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $product['status'] === 'in_stock' ? 'success' : 
                                                        ($product['status'] === 'check' ? 'warning' : 
                                                        ($product['status'] === 'expired' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo htmlspecialchars($product['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if ($order['order_status'] === 'issue'): ?>
        <button type="button" class="btn btn-warning mb-3" data-toggle="modal" data-target="#reportIssueModal">
            Report Issue
        </button>

        <!-- Report Issue Modal -->
        <div class="modal fade" id="reportIssueModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Report Order Issue</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" action="process_issue.php">
                        <div class="modal-body">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                        <div class="mb-4">
                            <h6 class="border-bottom pb-2">Issues History</h6>
                            <?php
                            // Query to get all issues for this order
                            $issues_query = "SELECT * FROM issue_orders WHERE order_id = ?";
                            $issues_stmt = $conn->prepare($issues_query);
                            $issues_stmt->bind_param("i", $order_id);
                            $issues_stmt->execute();
                            $issues_result = $issues_stmt->get_result();
                            
                            if ($issues_result->num_rows > 0) {
                                echo '<div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Issue ID</th>
                                                    <th>Product_id</th>
                                                    <th>Type</th>
                                                    <th>Description</th>
                                                    <th>Image</th>
                                                    <th>Report Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                                
                                while ($issue = $issues_result->fetch_assoc()) {
                                    echo '<tr>
                                            <td>'.$issue['issue_id'].'</td>
                                            <td>'.$issue['product_id'].'</td>
                                            <td>'.$issue['issue_type'].'</td>
                                            <td>'.$issue['issue_description'].'</td>
                                            <td>';
                                    if ($issue['issue_image']) {
                                        // Assuming issue images are stored in a specific directory
                                        echo '<img src="../../'.htmlspecialchars($issue['issue_image']).'" 
                                            alt="issue_pic" class="img-fluid" style="max-width: 100px; max-height: 100px;">';
                                    } else {
                                        
                                        echo 'No image';
                                    }
                                    echo '</td>
                                            <td>'.date('Y-m-d H:i', strtotime($issue['report_date'])).'</td>
                                        </tr>';
                                }
                                
                                echo '</tbody></table></div>';
                            } else {
                                echo '<p class="text-muted">No previous issues reported for this order.</p>';
                            }
                            ?>
                        </div>
                            <!-- Resolution Type -->
                            <div class="form-group">
                                <label>Preferred Resolution:</label>
                                <select name="resolution_type" class="form-control" required id="resolutionType">
                                    <option value="">Select Resolution</option>
                                    <option value="refund">Refund</option>
                                    <option value="return_item">Return Item</option>
                                </select>
                            </div>

                            <!-- Resolution Description -->
                            <div class="form-group">
                                <label>Description of Resolution:</label>
                                <textarea name="resolution_description" class="form-control" rows="3" required></textarea>
                            </div>
                            
                            <!-- Issue Image -->
                            <div class="form-group">
                                <label>Upload Image of Issue:</label>
                                <input type="file" name="resolution_image" class="form-control-file" accept="image/*" required>
                            </div>

                            <!-- Refund QR Code (initially hidden) -->
                            <div id="refundQRSection" style="display: none;" class="text-center">
                                <h6>Refund via PromptPay</h6>
                                <img src="https://promptpay.io/<?php echo $order['tel_store']; ?>/<?php echo $order['total_amount']; ?>" 
                                    alt="PromptPay QR Code" class="img-fluid" style="max-width: 200px;">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Submit Report</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($upload_error)): ?>
            <div class="alert alert-danger mt-3">
                <?php echo htmlspecialchars($upload_error); ?>
            </div>
        <?php endif; ?>
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
                
                <?php if ($order['order_status'] === 'cancel' && $order['cancel_info']): ?>
                    <div class="alert alert-danger">
                        <h6>Cancellation Reason:</h6>
                        <p><?php echo htmlspecialchars($order['cancel_info']); ?></p>
                        <?php if ($order['cancel_pic']): ?>
                            <h6>Cancellation Image:</h6>
                            <img src="../../upload/cancel_payment/<?php echo htmlspecialchars($order['cancel_pic']); ?>" 
                            alt="Cancellation Image" class="img-fluid" style="max-width: 300px;">
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php while ($payment = $payment_result->fetch_assoc()): ?>
                    <p><strong>Payment Date:</strong> <?php echo $payment['payment_date']; ?></p>
                    <?php if ($payment['payment_method'] === 'credit_card'): ?>
                        <p><strong>Payment Method:</strong> Credit Card</p>
                    <?php elseif ($payment['payment_pic']): ?>
                        <p><strong>Payment Method:</strong> PromptPay</p>
                        <div>
                            <strong>Payment Proof:</strong><br>
                            <img src="../../upload/payment_proofs/<?php echo htmlspecialchars($payment['payment_pic']); ?>" 
                                alt="Payment Proof" style="max-width: 300px;" class="img-fluid">
                        </div>
                    <?php endif; ?>
                <?php endwhile; ?>
            </div>
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
                
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#cancelModal">
                    Cancel Order
                </button>
            </div>
        <?php endif; ?>

        <!-- Cancel Modal -->
        <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel Order</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="cancel">
                            <div class="form-group">
                                <label for="cancel_reason">Cancellation Reason:</label>
                                <textarea name="cancel_reason" id="cancel_reason" class="form-control" required></textarea>
                            </div>
                            <div class="mt-3">
                                <h6>PromptPay QR Code</h6>
                                <img src="https://promptpay.io/<?php echo $order['tel_store']; ?>/<?php echo $order['total_amount']; ?>" 
                                    alt="PromptPay QR Code" class="img-fluid" style="max-width: 200px;">
                            </div>
                            <div class="form-group">
                                <label for="cancel_pic">Upload Image (JPG/JPEG only):</label>
                                <input type="file" name="cancel_pic" id="cancel_pic" class="form-control-file" accept="image/jpeg">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('resolutionType').addEventListener('change', function() {
        const refundSection = document.getElementById('refundQRSection');
        refundSection.style.display = this.value === 'refund' ? 'block' : 'none';
    });
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>