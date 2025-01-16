<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/config.php';
require_once '../function/functions.php';

$id_customer = isset($_GET['id_customer']) ? intval($_GET['id_customer']) : 0;
$customer_name = '';
if ($id_customer > 0) {
    $sql = "SELECT name_customer FROM customers WHERE id_customer = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_customer);
    $stmt->execute();
    $result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $customer_name = $customer['name_customer'];
}
// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_bill'])) {
        // รับข้อมูลจากฟอร์ม
        $id_customer = $_POST['id_customer'];
        $number_bill = $_POST['number_bill'];
        $type_bill = $_POST['type_bill'];
        $status_bill = $_POST['status_bill'];

        // สร้างบิลใหม่
        $sql = "INSERT INTO bill_customer (id_customer, number_bill, type_bill, status_bill, create_at, update_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isss", $id_customer, $number_bill, $type_bill, $status_bill);
        $stmt->execute();
        $id_bill = $stmt->insert_id;

        // สร้างบริการตามจำนวนที่ระบุ
        if (isset($_POST['code_service'])) {
            foreach ($_POST['code_service'] as $index => $code_service) {
                $type_service = $_POST['type_service'][$index];
                $type_gadget = $_POST['type_gadget'][$index];
                $status_service = $_POST['status_service'][$index];

                $sql = "INSERT INTO service_customer (code_service, type_service, type_gadget, status_service, id_bill, create_at, update_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $code_service, $type_service, $type_gadget, $status_service, $id_bill);
                $stmt->execute();
            }
        }

        echo "<script>alert('บิลและบริการถูกสร้างเรียบร้อยแล้ว');</script>";
    } elseif (isset($_POST['update_bill'])) {
        // รับข้อมูลจากฟอร์ม
        $id_bill = $_POST['id_bill'];
        $id_customer = $_POST['id_customer'];
        $number_bill = $_POST['number_bill'];
        $type_bill = $_POST['type_bill'];
        $status_bill = $_POST['status_bill'];

        // อัปเดตข้อมูลบิล
        $sql = "UPDATE bill_customer SET id_customer = ?, number_bill = ?, type_bill = ?, status_bill = ?, update_at = NOW() WHERE id_bill = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssi", $id_customer, $number_bill, $type_bill, $status_bill, $id_bill);
        $stmt->execute();

        // ลบบริการเดิม
        $sql = "DELETE FROM service_customer WHERE id_bill = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_bill);
        $stmt->execute();

        // เพิ่มบริการใหม่
        if (isset($_POST['code_service'])) {
            foreach ($_POST['code_service'] as $index => $code_service) {
                $type_service = $_POST['type_service'][$index];
                $type_gadget = $_POST['type_gadget'][$index];
                $status_service = $_POST['status_service'][$index];

                $sql = "INSERT INTO service_customer (code_service, type_service, type_gadget, status_service, id_bill, create_at, update_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $code_service, $type_service, $type_gadget, $status_service, $id_bill);
                $stmt->execute();
            }
        }

        echo "<script>alert('บิลและบริการถูกอัปเดตเรียบร้อยแล้ว');</script>";
    }
}

// ดึงข้อมูลบิลและบริการจากฐานข้อมูล
$sql = "SELECT 
            c.name_customer AS customer_name,
            b.id_bill,
            b.number_bill AS bill_number,
            b.type_bill AS bill_type,
            b.status_bill AS bill_status,
            b.create_at AS bill_start_date,
            SUM(CASE WHEN s.status_service = 'ใช้งาน' THEN 1 ELSE 0 END) AS active_services,
            SUM(CASE WHEN s.status_service = 'ยกเลิก' THEN 1 ELSE 0 END) AS canceled_services
        FROM bill_customer b
        JOIN customers c ON b.id_customer = c.id_customer
        LEFT JOIN service_customer s ON b.id_bill = s.id_bill
        WHERE 1=1";

if ($id_customer > 0) {
    $sql .= " AND b.id_customer = ?";
}

$sql .= " GROUP BY b.id_bill ORDER BY b.create_at DESC";

$stmt = $conn->prepare($sql);

if ($id_customer > 0) {
    $stmt->bind_param("i", $id_customer);
}

$stmt->execute();
$result = $stmt->get_result();
$bills = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <style>
        /* ซ่อนปุ่มค้นหาของ DataTables */
        .dataTables_filter {
            display: none;
        }
        .flex-wrap {
            gap: 0.5rem; /* ระยะห่างระหว่างองค์ประกอบ */
        }
        .border {
            border: 1px solid #e2e8f0; /* สีเส้นขอบ */
        }
        .rounded-md {
            border-radius: 0.375rem; /* มุมโค้ง */
        }
          /* เพิ่มสีพื้นหลังให้หัวตาราง */
        #billTable thead th {
            background-color: #4a5568; /* สีเทาเข้ม */
            color: white; /* สีตัวอักษรขาว */
        }

        /* ปรับสีพื้นหลังของแถวตาราง */
        #billTable tbody tr {
            background-color: rgb(255, 255, 255); /* สีขาว */
        }

        /* ปรับสีพื้นหลังของแถวตารางเมื่อโฮเวอร์ */
        #billTable tbody tr:hover {
            background-color: rgb(198, 198, 198); /* สีเทาอ่อน */
        }

        /* ปรับขนาดและสีของไอคอนสถานะ */
        .fa-circle {
            font-size: 12px; /* ปรับขนาดไอคอน */
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include './components/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-4">จัดการบิลสำหรับลูกค้า: <?= $id_customer > 0 ? htmlspecialchars($customer_name) : 'ทั้งหมด' ?></h1>
        <button onclick="openCreateBillModal()" class="bg-blue-500 text-white px-4 py-2 rounded-md">สร้างบิลใหม่</button>

        <!-- ตารางแสดงข้อมูลบิล -->
        <div class="mt-6">
            <table id="billTable" class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ลำดับที่</th>
                        <th class="py-2 px-4 border-b">ชื่อลูกค้า</th>
                        <th class="py-2 px-4 border-b">บิลลูกค้า</th>
                        <th class="py-2 px-4 border-b">ประเภทบิล</th>
                        <th class="py-2 px-4 border-b">สถานะบิล</th>
                        <th class="py-2 px-4 border-b">จำนวนบริการที่ใช้งาน</th>
                        <th class="py-2 px-4 border-b">จำนวนบริการที่ยกเลิก</th>
                        <th class="py-2 px-4 border-b">วันที่เริ่มบิล</th>
                        <th class="py-2 px-4 border-b">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bills)): ?>
                        <?php foreach ($bills as $bill): ?>
                            <tr>
                                <td class="py-2 px-4 border-b text-center"></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['customer_name']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['bill_number']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['bill_type']); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <?php if ($bill['bill_status'] === 'ใช้งาน'): ?>
                                        <i class="fas fa-circle text-green-500"></i><?= $bill['bill_status'] ?> <!-- ไอคอน Online -->
                                    <?php else: ?>
                                        <i class="fas fa-circle text-red-500"></i><?= $bill['bill_status'] ?> <!-- ไอคอน Offline -->
                                    <?php endif; ?>
                                </td>
                                <td class="py-2 px-4 border-b text-center"><?php echo $bill['active_services']; ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo $bill['canceled_services']; ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($bill['bill_start_date']); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button onclick="openEditBillModal(<?php echo $bill['id_bill']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded-md">
                                        <i class="fas fa-edit"></i> <!-- ไอคอนแก้ไข -->
                                    </button>
                                    <a href="service_bill.php?id_bill=<?php echo $bill['id_bill']; ?>" class="bg-blue-500 text-white px-2 py-1 rounded-md">
                                        <i class="fas fa-info-circle"></i> Info<!-- ไอคอน Info -->
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="py-2 px-4 border-b text-center">ไม่มีข้อมูลบิล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Include Modal -->
    <?php include './components/bill_modal.php'; ?>

    <!-- jQuery และ DataTables JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <script>
    // เรียกใช้ DataTables
    $(document).ready(function() {
        $('#billTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [[10, 20, 50, 100, -1], [10, 20, 50, 100, "All"]],
            "language": {
                "search": "ค้นหา:",
                "lengthMenu": "แสดง _MENU_ แถวต่อหน้า",
                "info": "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ แถว",
                "paginate": {
                    "first": "แรก",
                    "last": "สุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            },
            "columnDefs": [
                {
                    "targets": 0, // คอลัมน์ลำดับที่
                    "render": function(data, type, row, meta) {
                        return meta.row + 1; // แสดงลำดับที่
                    }
                }
            ]
        });
    });

    function openEditBillModal(id_bill) {
        // ดึงข้อมูลบิลและบริการจากฐานข้อมูล
        fetch(`../function/get_bill.php?id_bill=${id_bill}`)
            .then(response => response.json())
            .then(data => {
                // เตรียมข้อมูลสำหรับฟอร์มแก้ไข
                document.getElementById('id_bill').value = data.bill.id_bill;
                document.getElementById('id_customer').value = data.bill.id_customer;
                document.getElementById('number_bill').value = data.bill.number_bill;
                document.getElementById('type_bill').value = data.bill.type_bill;
                document.getElementById('status_bill').value = data.bill.status_bill;

                // ล้างบริการเดิม
                const container = document.getElementById('services-container');
                container.innerHTML = '';

                // เพิ่มบริการใหม่
                data.services.forEach((service, index) => {
                    addServiceField(index, service);
                });

                // เปิด Modal
                document.getElementById('createBillModal').classList.remove('hidden');
                document.getElementById('createBillButton').classList.add('hidden');
                document.getElementById('updateBillButton').classList.remove('hidden');
            });
    }

    function addServiceField(index, service = null) {
        const container = document.getElementById('services-container');
        const newService = document.createElement('div');
        newService.classList.add('mb-4', 'border', 'p-4', 'rounded-md');
        newService.innerHTML = `
            <h3 class="text-lg font-semibold mb-2">บริการที่ ${index + 1}</h3>
            <div class="grid grid-cols-2 gap-4 mb-2">
                <div>
                    <label for="code_service_${index}" class="block text-sm font-medium text-gray-700">รหัสบริการ</label>
                    <input type="text" name="code_service[]" id="code_service_${index}" class="mt-1 p-2 border rounded-md w-full focus:ring-blue-500 focus:border-blue-500" required value="${service ? service.code_service : ''}">
                </div>
                <div>
                    <label for="type_service_${index}" class="block text-sm font-medium text-gray-700">ประเภทบริการ</label>
                    <select name="type_service[]" id="type_service_${index}" class="mt-1 p-2 border rounded-md w-full focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="Fttx" ${service && service.type_service === 'Fttx' ? 'selected' : ''}>Fttx</option>
                        <option value="Fttx+ICT solution" ${service && service.type_service === 'Fttx+ICT solution' ? 'selected' : ''}>Fttx+ICT solution</option>
                        <option value="Fttx 2+ICT solution" ${service && service.type_service === 'Fttx 2+ICT solution' ? 'selected' : ''}>Fttx 2+ICT solution</option>
                        <option value="SI service" ${service && service.type_service === 'SI service' ? 'selected' : ''}>SI service</option>
                        <option value="วงจเช่า" ${service && service.type_service === 'วงจเช่า' ? 'selected' : ''}>วงจเช่า</option>
                        <option value="IP phone" ${service && service.type_service === 'IP phone' ? 'selected' : ''}>IP phone</option>
                        <option value="Smart City" ${service && service.type_service === 'Smart City' ? 'selected' : ''}>Smart City</option>
                        <option value="WiFi" ${service && service.type_service === 'WiFi' ? 'selected' : ''}>WiFi</option>
                        <option value="อื่นๆ" ${service && service.type_service === 'อื่นๆ' ? 'selected' : ''}>อื่นๆ</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mb-2">
                <div>
                    <label for="type_gadget_${index}" class="block text-sm font-medium text-gray-700">ประเภทอุปกรณ์</label>
                    <select name="type_gadget[]" id="type_gadget_${index}" class="mt-1 p-2 border rounded-md w-full focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="เช่า" ${service && service.type_gadget === 'เช่า' ? 'selected' : ''}>เช่า</option>
                        <option value="ขาย" ${service && service.type_gadget === 'ขาย' ? 'selected' : ''}>ขาย</option>
                        <option value="เช่าและขาย" ${service && service.type_gadget === 'เช่าและขาย' ? 'selected' : ''}>เช่าและขาย</option>
                    </select>
                </div>
                <div>
                    <label for="status_service_${index}" class="block text-sm font-medium text-gray-700">สถานะบริการ</label>
                    <select name="status_service[]" id="status_service_${index}" class="mt-1 p-2 border rounded-md w-full focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="ใช้งาน" ${service && service.status_service === 'ใช้งาน' ? 'selected' : ''}>ใช้งาน</option>
                        <option value="ยกเลิก" ${service && service.status_service === 'ยกเลิก' ? 'selected' : ''}>ยกเลิก</option>
                    </select>
                </div>
            </div>
            <button type="button" onclick="removeServiceField(this)" class="bg-red-500 text-white px-2 py-1 rounded-md">ลบบริการ</button>
        `;
        container.appendChild(newService);
    }

    function removeServiceField(button) {
        const serviceDiv = button.parentElement;
        serviceDiv.remove();
    }

    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const idCustomer = urlParams.get('id_customer');

        if (idCustomer) {
            document.getElementById('id_customer').value = idCustomer;
        }
    });
    </script>
</body>
</html>