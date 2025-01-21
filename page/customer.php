<?php
// customer.php

session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

require_once  '../config/config.php';
require_once  '../function/functions.php';

// ดึงข้อมูลประเภทลูกค้า
$customer_types = $conn->query("SELECT DISTINCT type_customer FROM customers")->fetch_all(MYSQLI_ASSOC);

// ดึงข้อมูลอำเภอ
$amphures = $conn->query("SELECT * FROM amphures")->fetch_all(MYSQLI_ASSOC);

// ดึงข้อมูลตำบล (จะโหลดแบบไดนามิกเมื่อเลือกอำเภอ)
$tambons = []; // เริ่มต้นด้วยอาร์เรย์ว่าง

$customers = $conn->query("
    SELECT c.*, a.info_address, t.name_tambons, am.name_amphures, t.zip_code 
    FROM customers c 
    JOIN address a ON c.id_address = a.id_address 
    JOIN tambons t ON a.id_tambons = t.id_tambons 
    JOIN amphures am ON a.id_amphures = am.id_amphures
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management</title>
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
        #customerTable thead th {
            background-color: #4a5568; /* สีเทาเข้ม */
            color: white; /* สีตัวอักษรขาว */
        }

        /* ปรับสีพื้นหลังของแถวตาราง */
        #customerTable tbody tr {
            background-color:rgb(255, 255, 255); /* สีขาว */
        }

        /* ปรับสีพื้นหลังของแถวตารางเมื่อโฮเวอร์ */
        #customerTable tbody tr:hover {
            background-color:rgb(198, 198, 198); /* สีเทาอ่อน */
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
        <h1 class="text-2xl font-bold mb-4">Customer Management</h1>
        
        <!-- ปรับ UI ให้ปุ่ม "Add Customer" และฟอร์มค้นหาอยู่ในบรรทัดเดียวกัน -->
        <div class="flex flex-wrap items-center justify-between mb-4">
            <!-- ปุ่ม Add Customer และฟอร์ม Import อยู่ใน div เดียวกัน -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- ปุ่ม Add Customer -->
                <button onclick="openModal()" class="bg-blue-500 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-plus"></i> Add Customer
                </button>

                <!-- ฟอร์มอัปโหลดไฟล์ Excel -->
                <form id="uploadForm" enctype="multipart/form-data" class="flex items-center gap-2">
                    <input type="file" name="excelFile" id="excelFile" accept=".xls, .xlsx" class="border p-2 rounded-md">
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-upload"></i> Import Excel
                    </button>
                </form>
            </div>

            <!-- ฟิลด์ค้นหา -->
            <div class="flex flex-wrap gap-2">
                <div class="relative">
                    <input type="text" id="searchName" placeholder="Search by name..." class="border p-2 rounded-md pl-10">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i> <!-- ไอคอนค้นหา -->
                </div>
                <select id="searchType" class="border p-2 rounded-md">
                    <option value="">All Types</option>
                    <?php foreach ($customer_types as $type): ?>
                        <option value="<?= $type['type_customer'] ?>"><?= $type['type_customer'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="searchAmphure" class="border p-2 rounded-md">
                    <option value="">All Amphures</option>
                    <?php foreach ($amphures as $amphure): ?>
                        <option value="<?= $amphure['id_amphures'] ?>"><?= $amphure['name_amphures'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="searchTambon" class="border p-2 rounded-md">
                    <option value="">All Tambons</option>
                    <!-- ตำบลจะโหลดแบบไดนามิกเมื่อเลือกอำเภอ -->
                </select>
                <button onclick="resetSearch()" class="bg-gray-500 text-white px-4 py-2 rounded-md">
                    <i class="fas fa-sync-alt"></i> <!-- ไอคอนรีเซ็ต -->
                </button>
            </div>
        </div>

        <!-- ตารางข้อมูลลูกค้า -->
        <table id="customerTable" class="   ">
            <thead>
                <tr>
                    <th class="py-2">ลำดับที่</th>
                    <th class="py-2">ชื่อลูกค้า</th>
                    <th class="py-2">ประเภทลูกค้า</th>
                    <th class="py-2">เบอร์โทรศัพท์</th>
                    <th class="py-2">สถานะ</th>
                    <th class="py-2">ที่อยู่ (Tambon, Amphure)</th>
                    <th class="py-2">การดำเนินการ</th>
                </tr>
            </thead>
            <!-- ในส่วนของตาราง -->
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td class="py-2 text-center"></td>
                        <td class="py-2 text-center"><?= $customer['name_customer'] ?></td>
                        <td class="py-2 text-center"><?= $customer['type_customer'] ?></td>
                        <td class="py-2 text-center"><?= $customer['phone_customer'] ?></td>
                        <td class="py-2 text-center">
                            <?php if ($customer['status_customer'] === 'ใช้งาน'): ?>
                                <i class="fas fa-circle text-green-500"></i><?= $customer['status_customer'] ?> <!-- ไอคอน Online -->
                            <?php else: ?>
                                <i class="fas fa-circle text-red-500"></i><?= $customer['status_customer'] ?> <!-- ไอคอน Offline -->
                            <?php endif; ?>
                        </td>
                        <td class="py-2 text-center">
                            <?= $customer['info_address'] ?> <?= $customer['name_tambons'] ?>, <?= $customer['name_amphures'] ?>, <?= $customer['zip_code'] ?>
                        </td>
                        <td class="py-2 text-center">
                            <button onclick="editCustomer(<?= $customer['id_customer'] ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded-md">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteCustomer(<?= $customer['id_customer'] ?>)" class="bg-red-500 text-white px-2 py-1 rounded-md ml-2">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button onclick="window.location.href='bill.php?id_customer=<?= $customer['id_customer'] ?>'" class="bg-blue-500 text-white px-2 py-1 rounded-md ml-2">
                                <i class="fas fa-info-circle"></i> Bills
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>    
            </tbody>
        </table>
    </div>

    <?php include './components/customer_modal.php'; ?>

    <!-- jQuery และ DataTables JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <script>
    // เรียกใช้ DataTables
    $(document).ready(function() {
        var table = $('#customerTable').DataTable({
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
                    "targets": 0,
                    "render": function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                }
            ]
        });

        // ค้นหาจากชื่อลูกค้า (แสดงผลทันทีเมื่อพิมพ์)
        $('#searchName').on('keyup', function() {
            table.column(1).search(this.value).draw();
        });

        // ค้นหาจากประเภทลูกค้า (แสดงผลทันทีเมื่อเลือก)
        $('#searchType').on('change', function() {
            table.column(2).search(this.value).draw();
        });

        // ค้นหาจากอำเภอ (แสดงผลทันทีเมื่อเลือก)
        $('#searchAmphure').on('change', function() {
            const amphureId = this.value;
            table.column(5).search(this.options[this.selectedIndex].text).draw();

            // โหลดตำบลตามอำเภอที่เลือก
            if (amphureId) {
                loadTambonss(amphureId);
            } else {
                $('#searchTambon').html('<option value="">All Tambons</option>');
            }
        });

        // ค้นหาจากตำบล (แสดงผลทันทีเมื่อเลือก)
        $('#searchTambon').on('change', function() {
            table.column(5).search(this.value).draw();
        });
    });

    // ฟังก์ชันโหลดตำบลตามอำเภอ
    function loadTambonss(amphureId) {
        return fetch(`../function/get_tambons.php?id_amphures=${amphureId}`)
            .then(response => response.json())
            .then(data => {
                const tambonSelect = document.getElementById('searchTambon');
                tambonSelect.innerHTML = '<option value="">All Tambons</option>';
                data.forEach(tambon => {
                    tambonSelect.innerHTML += `<option value="${tambon.name_tambons}">${tambon.name_tambons}</option>`;
                });
            });
    }

    // ฟังก์ชัน reset การค้นหา
    function resetSearch() {
        $('#searchName').val('');
        $('#searchType').val('');
        $('#searchAmphure').val('');
        $('#searchTambon').html('<option value="">All Tambons</option>');
        $('#customerTable').DataTable().search('').columns().search('').draw();
    }

    function openModal() {
        document.getElementById('customerModal').classList.remove('hidden');
        document.getElementById('modalTitle').innerText = 'Add Customer';
        document.getElementById('customerForm').reset();
    }

    function editCustomer(id_customer) {
        fetch(`../function/get_customer.php?id_customer=${id_customer}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('id_customer').value = data.id_customer;
                document.getElementById('name_customer').value = data.name_customer;
                document.getElementById('type_customer').value = data.type_customer;
                document.getElementById('phone_customer').value = data.phone_customer;
                document.getElementById('status_customer').value = data.status_customer;
                document.getElementById('id_amphures').value = data.id_amphures;
                if (data.id_amphures) {
                    loadTambons(data.id_amphures).then(() => {
                        document.getElementById('id_tambons').value = data.id_tambons;
                    });
                }
                document.getElementById('id_address').value = data.id_address;
                document.getElementById('info_address').value = data.info_address;
                document.getElementById('modalTitle').innerText = 'Edit Customer';
                document.getElementById('customerModal').classList.remove('hidden');
            });
    }

    function deleteCustomer(id_customer) {
        if (confirm('Are you sure you want to delete this customer?')) {
            fetch(`../function/delete_customer.php?id_customer=${id_customer}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
        }
    }

    document.getElementById('customerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const id_customer = formData.get('id_customer');
        const url = id_customer ? `../function/update_customer.php?id_customer=${id_customer}` : '../function/create_customer.php';

        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message); // แสดงข้อความแจ้งเตือนเมื่อมีชื่อลูกค้าซ้ำ
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาดในการดำเนินการ');
        });
    });
    // JavaScript สำหรับอัปโหลดไฟล์ Excel
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../function/import_excel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Import successful!');
                location.reload(); // รีเฟรชหน้าเพื่อแสดงข้อมูลใหม่
            } else {
                alert('Import failed: ' + data.message); // แจ้งเตือนข้อผิดพลาด
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    });
    </script>
</body>
</html>