<?php
session_start();
include('../../config/db.php');

// เปลี่ยนเส้นทางการเชื่อมต่อฐานข้อมูล
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../../auth/login.php');  // เปลี่ยนเส้นทางการเช็ค role
    exit;
}

$use_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้งาน
$query = "SELECT u.name, u.surname, u.role, u.store_id, s.store_name 
          FROM users u
          LEFT JOIN stores s ON u.store_id = s.store_id 
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $use_id);
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

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["action"])) {
        switch ($_POST["action"]) {
            case "add":
                addProduct($conn);
                break;
            case "edit":
                editProduct($conn);
                break;
            case "delete":
                deleteProduct($conn);
                break;
            case "toggle":
                toggleVisibility($conn);
                break;
        }
    }
}

// ฟังก์ชันสำหรับดึงข้อมูลสินค้าทั้งหมด (ค้นหาและตัวกรอง)
function getProducts($conn, $search = '', $category = '') {
    $sql = "SELECT * FROM products_info WHERE 1";  // เริ่มต้น SQL

    // ตรวจสอบเงื่อนไขการค้นหา
    if (!empty($search)) {
        $sql .= " AND (listproduct_id LIKE ? OR product_name LIKE ?)";
    }

    // ตรวจสอบตัวกรองหมวดหมู่
    if (!empty($category)) {
        $sql .= " AND category = ?";
    }

    $sql .= " ORDER BY updated_at DESC";
    
    $stmt = $conn->prepare($sql);

    // ผูกค่าเงื่อนไขตามการค้นหาและตัวกรอง
    if (!empty($search) && !empty($category)) {
        $search_param = '%' . $search . '%';
        $stmt->bind_param("sss", $search_param, $search_param, $category);
    } elseif (!empty($search)) {
        $search_param = '%' . $search . '%';
        $stmt->bind_param("ss", $search_param, $search_param);
    } elseif (!empty($category)) {
        $stmt->bind_param("s", $category);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


// ฟังก์ชันสำหรับเพิ่มสินค้าใหม่
function addProduct($conn) {
    $product_name = $_POST['product_name'];
    $category = $_POST['category'];
    $price_set = $_POST['price_set'];
    $product_info = $_POST['product_info'];
    $quantity_set = $_POST['quantity_set'];
    $product_pic = '';

    if (isset($_FILES['product_pic']) && $_FILES['product_pic']['error'] == 0) {
        $target_dir = ".../../upload/picture_product/"; // กำหนดโฟลเดอร์ที่เก็บรูปภาพ
        $target_file = $target_dir . basename($_FILES['product_pic']['name']);
        move_uploaded_file($_FILES['product_pic']['tmp_name'], $target_file); // บันทึกไฟล์ลงโฟลเดอร์
        $product_pic = basename($_FILES['product_pic']['name']); // เก็บชื่อไฟล์ในฐานข้อมูล
    }

    // ตรวจสอบชื่อสินค้าซ้ำ
    $check_sql = "SELECT * FROM products_info WHERE product_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $product_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo ('Duplicate product name');
        exit();
    }
    
    $sql = "INSERT INTO products_info (product_name, category, price_set, product_info, quantity_set, product_pic) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdsis", $product_name, $category, $price_set, $product_info, $quantity_set, $product_pic);
    
    if ($stmt->execute()) {
        echo ('Product added successfully');
        logTransaction($conn, $_SESSION['user_id'], 'add_p');
        exit();
    } else {
        echo ('An error occurred while adding the product');
        exit();
    }
}

// ฟังก์ชันสำหรับแก้ไขสินค้า
function editProduct($conn) {
    $listproduct_id = $_POST['listproduct_id'];
    $price_set = $_POST['price_set'];
    $product_info = $_POST['product_info'];
    $quantity_set = $_POST['quantity_set'];
    $product_pic = '';

    // ดึงข้อมูลสินค้าเดิมเพื่อเอาชื่อรูปภาพเก่ามาใช้ลบ
    $sql = "SELECT product_pic FROM products_info WHERE listproduct_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $listproduct_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_product = $result->fetch_assoc();
    $old_product_pic = $old_product['product_pic'];

    // ตรวจสอบว่ามีการอัพโหลดรูปภาพใหม่หรือไม่
    if (isset($_FILES['product_pic']) && $_FILES['product_pic']['error'] == 0) {
        // ตรวจสอบประเภทไฟล์และขนาดไฟล์
        $allowed_extensions = ['jpg', 'jpeg' ,'png'];
        $file_extension = pathinfo($_FILES['product_pic']['name'], PATHINFO_EXTENSION);
        
        if (in_array($file_extension, $allowed_extensions) && $_FILES['product_pic']['size'] <= 500000) {
            $target_dir = "../../upload/picture_product/";
            $target_file = $target_dir . basename($_FILES['product_pic']['name']);
            
            // ลบรูปภาพเก่าออกก่อน
            if (!empty($old_product_pic) && file_exists($target_dir . $old_product_pic)) {
                unlink($target_dir . $old_product_pic);
            }
            
            move_uploaded_file($_FILES['product_pic']['tmp_name'], $target_file);
            $product_pic = basename($_FILES['product_pic']['name']); // เก็บชื่อไฟล์ในฐานข้อมูล
        } else {
            echo ('Invalid image file');
            exit();
        }
    }

    // สร้าง SQL สำหรับแก้ไขข้อมูล
    if ($product_pic) {
        // หากมีการอัพโหลดรูปภาพใหม่
        $sql = "UPDATE products_info SET price_set = ?, product_info = ?, quantity_set = ?, product_pic = ? WHERE listproduct_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsisi", $price_set, $product_info, $quantity_set, $product_pic, $listproduct_id);
    } else {
        // หากไม่มีการอัพโหลดรูปภาพใหม่
        $sql = "UPDATE products_info SET price_set = ?, product_info = ?, quantity_set = ? WHERE listproduct_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsii", $price_set, $product_info, $quantity_set, $listproduct_id);
    }

    if ($stmt->execute()) {
        echo ('Product updated successfully');
        logTransaction($conn, $_SESSION['user_id'], 'edit_p');
        exit();
    } else {
        echo ('An error occurred while updating the product');
        exit();
    }
}

// ฟังก์ชันสำหรับลบสินค้า
function deleteProduct($conn) {
    $listproduct_id = $_POST['listproduct_id'];
    $sql = "DELETE FROM products_info WHERE listproduct_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $listproduct_id);
    
    if ($stmt->execute()) {
        echo ('An error occurred while deleting the product');
        logTransaction($conn, $_SESSION['user_id'], 'del_p');
        exit();
    } else {
        echo ('An error occurred while deleting the product');
        exit();
    }
}

// ฟังก์ชันสำหรับเปลี่ยนการมองเห็นสินค้า
function toggleVisibility($conn) {
    $listproduct_id = $_POST['listproduct_id'];
    $visible = $_POST['visible'];
    $sql = "UPDATE products_info SET visible = ? WHERE listproduct_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $visible, $listproduct_id);
    
    if ($stmt->execute()) {
        echo ('Product visibility updated successfully');
        exit();
    } else {
        echo ('An error occurred while updating product visibility');
        exit();
    }
}
function logTransaction($conn, $use_id, $transaction_type) {
    $sql = "INSERT INTO transaction_manage (user_id, transaction_type,created_at) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $use_id, $transaction_type); // ใช้ $use_id เป็นทั้ง user_id และ reporter

    if (!$stmt->execute()) {
        echo "บันทึก transaction ผิดพลาด: " . $stmt->error;
    }
}

$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$products = getProducts($conn, $search, $category);
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
        .product-image {
            max-width: 100px;
            max-height: 100px;
        }
    </style>
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
    <div class="container" id="main-content">
        <h1 class="mb-4">Product Menu management</h1>
        <form action="" method="GET" class="mb-3">
            <div class="input-group">
                <input style="width: 500px;" type="text" class="form-control" placeholder="Search by Product ID or Name" name="search"  value="<?php echo htmlspecialchars($search); ?>">
                <select class="form-control" name="category" >
                <option value="">Select Category</option>
                    <option value="กาแฟ" <?php echo $category == 'กาแฟ' ? 'selected' : ''; ?>>Coffee</option>
                    <option value="นมและครีม" <?php echo $category == 'นมและครีม' ? 'selected' : ''; ?>>Milk and Cream</option>
                    <option value="ไซรัปและน้ำเชื่อม" <?php echo $category == 'ไซรัปและน้ำเชื่อม' ? 'selected' : ''; ?>>Syrups and Sweeteners</option>
                    <option value="ผงเครื่องดื่มและส่วนผสมอื่นๆ" <?php echo $category == 'ผงเครื่องดื่มและส่วนผสมอื่นๆ' ? 'selected' : ''; ?>>Beverage Powders and Other Ingredients</option>
                    <option value="ขนมและของว่าง" <?php echo $category == 'ขนมและของว่าง' ? 'selected' : ''; ?>>Snacks and Treats</option>
                    <option value="อุปกรณ์การชงกาแฟ" <?php echo $category == 'อุปกรณ์การชงกาแฟ' ? 'selected' : ''; ?>>Coffee Brewing Equipment</option>
                    <option value="แก้วและภาชนะบรรจุ" <?php echo $category == 'แก้วและภาชนะบรรจุ' ? 'selected' : ''; ?>>Cups and Containers</option>
                    <option value="สารให้ความหวานและสารแต่งกลิ่นรส" <?php echo $category == 'สารให้ความหวานและสารแต่งกลิ่นรส' ? 'selected' : ''; ?>>Sweeteners and Flavoring Agents</option>
                    <option value="ผลิตภัณฑ์เพิ่มมูลค่า" <?php echo $category == 'ผลิตภัณฑ์เพิ่มมูลค่า' ? 'selected' : ''; ?>>Value-Added Products</option>
                </select>
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </div>
        </form>
        <!-- ปุ่มเพิ่มสินค้า -->
        <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#addProductModal">
            Add Product
        </button>
        <div class="table-responsive">
    <?php if (empty($products)): ?>
        <div class="alert alert-warning mt-3">
            No products found matching the search term "<?php echo htmlspecialchars($search); ?>" and category "<?php echo htmlspecialchars($category); ?>"
        </div>
    <?php else: ?>
        <!-- Product Table -->
        <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Description</th>
                <th>Quantity</th>
                <th>Image</th>
                <th>Last Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo $product['listproduct_id']; ?></td>
                <td><?php echo $product['product_name']; ?></td>
                <td><?php echo $product['category']; ?></td>
                <td><?php echo $product['price_set']; ?></td>
                <td><?php echo $product['product_info']; ?></td>
                <td><?php echo $product['quantity_set']; ?></td>
                <td>
                    <?php if ($product['product_pic']): ?>
                        <img src="../../upload/picture_product/<?php echo $product['product_pic']; ?>" alt="Product Image" class="product-image">
                    <?php else: ?>
                        No Image
                    <?php endif; ?>
                </td>
                <td><?php echo $product['updated_at']; ?></td>
                <td>
                    <!-- Edit Button -->
                    <button type="button" class="btn btn-sm btn-info edit-product" data-toggle="modal" data-target="#editProductModal" 
                    data-product='<?php echo json_encode($product); ?>'>Edit</button>
                    <!-- Delete Button -->
                    <button type="button" class="btn btn-sm btn-danger delete-product" 
                    data-product-id="<?php echo $product['listproduct_id']; ?>">Delete</button>
                    <!-- Toggle Visibility Button -->
                    <button type="button" class="btn btn-sm <?php echo $product['visible'] ? 'btn-success' : 'btn-secondary'; ?> toggle-visibility"
                    data-product-id="<?php echo $product['listproduct_id']; ?>" data-visible="<?php echo $product['visible']; ?>"><?php echo $product['visible'] ? 'Show' : 'Hide'; ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" role="dialog" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addProductForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="form-group">
                            <label for="product_name">Product Name</label>
                            <input type="text" class="form-control" id="product_name" name="product_name" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="กาแฟ">Coffee</option>
                                <option value="นมและครีม">Milk and Cream</option>
                                <option value="ไซรัปและน้ำเชื่อม ">Syrups and Sweeteners</option>
                                <option value="ผงเครื่องดื่มและส่วนผสมอื่นๆ">Beverage Powders and Other Ingredients</option>
                                <option value="ขนมและของว่าง ">Snacks and Treats</option>
                                <option value="อุปกรณ์การชงกาแฟ">Coffee Brewing Equipment</option>
                                <option value="แก้วและภาชนะบรรจุ">Cups and Containers</option>
                                <option value="สารให้ความหวานและสารแต่งกลิ่นรส">Sweeteners and Flavoring Agents</option>
                                <option value="ผลิตภัณฑ์เพิ่มมูลค่า 5">Value-Added Products 5</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="price_set">Price</label>
                            <input type="number" class="form-control" id="price_set" name="price_set" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="product_info">Product Description</label>
                            <textarea class="form-control" id="product_info" name="product_info"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="quantity_set">Quantity</label>
                            <input type="number" class="form-control" id="quantity_set" name="quantity_set" required>
                        </div>
                        <div class="form-group">
                            <label for="product_pic">>Product Image</label>
                            <input type="file" class="form-control-file" id="product_pic" name="product_pic" accept=".jpg,.jpeg,.png">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="addProductBtn">Add Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แก้ไขสินค้า -->
    <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="edit_listproduct_id" name="listproduct_id">
                        <div class="form-group">
                            <label for="edit_price_set">Price</label>
                            <input type="number" class="form-control" id="edit_price_set" name="price_set" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_info">Product Description</label>
                            <textarea class="form-control" id="edit_product_info" name="product_info"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_quantity_set">Quantity</label>
                            <input type="number" class="form-control" id="edit_quantity_set" name="quantity_set" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_product_pic">Product Image </label>
                            <input type="file" class="form-control-file" id="edit_product_pic" name="product_pic" accept=".jpg,.jpeg,.png">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="editProductBtn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
// เพิ่มสินค้า
    $('#addProductForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            type: 'POST',
            url: '<?php echo $_SERVER['PHP_SELF']; ?>', // POST ไปยังเพจเดียวกัน
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                alert(response);
                location.reload();
            }
        });
    });

    // แก้ไขสินค้า
    $('.edit-product').click(function() {
        var product = $(this).data('product');
        $('#edit_listproduct_id').val(product.listproduct_id);
        $('#edit_price_set').val(product.price_set);
        $('#edit_product_info').val(product.product_info);
        $('#edit_quantity_set').val(product.quantity_set);
    });

    $('#editProductForm').submit(function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        $.ajax({
            type: 'POST',
            url: '<?php echo $_SERVER['PHP_SELF']; ?>',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                alert(response);
                location.reload();
            }
        });
    });

    // ลบสินค้า
    $('.delete-product').click(function() {
        if (confirm('Are you sure you want to delete this product?')) {
            var productId = $(this).data('product-id');
            $.post('<?php echo $_SERVER['PHP_SELF']; ?>', { action: 'delete', listproduct_id: productId }, function(response) {
                alert(response);
                location.reload();
            });
        }
    });

    // สลับการแสดงผลสินค้า
    $('.toggle-visibility').click(function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');
        var visible = $(this).data('visible');
        $.post('<?php echo $_SERVER['PHP_SELF']; ?>', { action: 'toggle', listproduct_id: productId, visible: visible ? 0 : 1 }, function(response) {
            alert(response);
            location.reload();
        });
    });

    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('main-content').classList.toggle('sidebar-active');
    });
});
</script>
</body>
</html>

