<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/config.php';
require_once '../function/functions.php';

$id_bill = isset($_GET['id_bill']) ? intval($_GET['id_bill']) : 0;

if ($id_bill > 0) {
    // ดึงข้อมูลบิล
    $sql = "SELECT * FROM bill_customer WHERE id_bill = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bill);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill = $result->fetch_assoc();

    // ดึงข้อมูลบริการที่เกี่ยวข้องกับบิล
    $sql = "SELECT * FROM service_customer WHERE id_bill = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bill);
    $stmt->execute();
    $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // ดึงข้อมูล gedget
    $sql = "SELECT * FROM gedget";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $gedgets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    header('Location: bill.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Bill</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* ซ่อนปุ่มค้นหาของ DataTables */
        .dataTables_filter {
            display: none;
        }

        /* เพิ่มสีพื้นหลังให้หัวตาราง */
        .common-table thead th {
            background-color: #4a5568; /* สีเทาเข้ม */
            color: white; /* สีตัวอักษรขาว */
        }

        /* ปรับสีพื้นหลังของแถวตาราง */
        .common-table tbody tr {
            background-color: rgb(255, 255, 255); /* สีขาว */
        }

        /* ปรับสีพื้นหลังของแถวตารางเมื่อโฮเวอร์ */
        .common-table tbody tr:hover {
            background-color: rgb(198, 198, 198); /* สีเทาอ่อน */
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include './components/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-4">ข้อมูลบริการสำหรับบิล: <?php echo htmlspecialchars($bill['number_bill']); ?></h1>

        <div class="mt-6">
        <button onclick="openCreateServiceModal()" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">เพิ่มบริการ</button>
            <h2 class="text-xl font-bold mb-4">ข้อมูลหมายเลขบริการบิลนี้</h2>
            <table id="serviceTable" class="common-table min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ลำดับที่</th>
                        <th class="py-2 px-4 border-b">รหัสบริการ</th>
                        <th class="py-2 px-4 border-b">ประเภทบริการ</th>
                        <th class="py-2 px-4 border-b">ประเภทอุปกรณ์</th>
                        <th class="py-2 px-4 border-b">สถานะบริการ</th>
                        <th class="py-2 px-4 border-b">การดำเนินการ</th> <!-- เพิ่มคอลัมน์สำหรับปุ่มแก้ไข/ลบ -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td class="py-2 px-4 border-b text-center"></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($service['code_service']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($service['type_service']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($service['type_gadget']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($service['status_service']); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button onclick="openEditServiceModal(<?php echo $service['id_service']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded"> <i class="fas fa-edit"></i></button>
                                    <button onclick="deleteService(<?php echo $service['id_service']; ?>)" class="bg-red-500 text-white px-2 py-1 rounded"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-2 px-4 border-b text-center">ไม่มีข้อมูลบริการ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            <!-- เพิ่มปุ่มสร้าง Gedget -->
            <button onclick="openCreateGedgetModal()" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">เพิ่มอุปกรณ์</button>
                <h2 class="text-xl font-bold mb-4">ข้อมูลอุปกรณ์ของบิลนี้</h2>
            <!-- แก้ไขตาราง Gedget -->
            <table id="gedgetTable" class="common-table min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ลำดับที่</th>
                        <th class="py-2 px-4 border-b">ชื่อ Gedget</th>
                        <th class="py-2 px-4 border-b">จำนวน</th>
                        <th class="py-2 px-4 border-b">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($gedgets)): ?>
                        <?php foreach ($gedgets as $gedget): ?>
                            <tr>
                                <td class="py-2 px-4 border-b text-center"><?php echo $gedget['id_gedget']; ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($gedget['name_gedget']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($gedget['quantity_gedget']); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button onclick="openEditGedgetModal(<?php echo $gedget['id_gedget']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded"> <i class="fas fa-edit"></i></button>
                                    <button onclick="deleteGedget(<?php echo $gedget['id_gedget']; ?>)" class="bg-red-500 text-white px-2 py-1 rounded"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="py-2 px-4 border-b text-center">ไม่มีข้อมูล Gedget</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include './components/service_modal.php'; ?>
    <?php include './components/gedget_modal.php'; ?>
    <!-- jQuery และ DataTables JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script>
        $(document).ready(function() {
            // ฟังก์ชันสำหรับตั้งค่า DataTables
            function initializeDataTable(tableId) {
                const table = $(`#${tableId}`).DataTable({
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
            }

            // ตรวจสอบว่าเป็นตาราง serviceTable หรือ gedgetTable
            if ($('#serviceTable').length) {
                initializeDataTable('serviceTable');
            }

            if ($('#gedgetTable').length) {
                initializeDataTable('gedgetTable');
            }
        });
        function openModal(type, id = null) {
            const modalTitle = document.getElementById('modalTitle');
            const modalForm = document.getElementById(`${type}Form`);
            const modalElement = document.getElementById(`${type}Modal`);
            if (id) {
                // กรณีแก้ไข
                fetch(`../function/get_${type}.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        modalTitle.innerText = `แก้ไข ${type === 'service' ? 'บริการ' : 'อุปกรณ์'}`;
                        modalForm.reset();
                        Object.keys(data).forEach(key => {
                            const input = modalForm.querySelector(`[name="${key}"]`);
                            if (input) {
                                input.value = data[key];
                            }
                        });
                        modalElement.classList.remove('hidden');
                    });
            } else {
                // กรณีสร้างใหม่
                modalTitle.innerText = `สร้าง ${type === 'service' ? 'บริการ' : 'อุปกรณ์'}`;
                modalForm.reset();
                modalElement.classList.remove('hidden');
            }
        }
        function closeModal(type) {
            const modalElement = document.getElementById(`${type}Modal`);
            modalElement.classList.add('hidden');
        }
        function openCreateServiceModal() {
            openModal('service');
        }
        function openEditServiceModal(id_service) {
            openModal('service', id_service);
        }
        function openCreateGedgetModal() {
            openModal('gedget');
        }
        function openEditGedgetModal(id_gedget) {
            openModal('gedget', id_gedget);
        }
        function closeServiceModal() {
            closeModal('service');
        }
        function closeGedgetModal() {
            closeModal('gedget');
        }
        function deleteItem(type, id) {
            if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ ${type === 'service' ? 'บริการ' : 'อุปกรณ์'} นี้?`)) {
                fetch(`../function/delete_${type}.php?id=${id}`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(`เกิดข้อผิดพลาดในการลบ ${type === 'service' ? 'บริการ' : 'อุปกรณ์'}`);
                        }
                    });
            }
        }
        function deleteService(id_service) {
            deleteItem('service', id_service);
        }
        function deleteGedget(id_gedget) {
            deleteItem('gedget', id_gedget);
        } 
        document.addEventListener('DOMContentLoaded', function() {
            const serviceForm = document.getElementById('serviceForm');
            const gedgetForm = document.getElementById('gedgetForm');
            if (serviceForm) {
                serviceForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    handleFormSubmit('service', this);
                });
            }
            if (gedgetForm) {
                gedgetForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    handleFormSubmit('gedget', this);
                });
            }
        });
        function handleFormSubmit(type, form) {
            const formData = new FormData(form);
            const id = formData.get(`id_${type}`);
            const url = id ? `../function/update_${type}.php` : `../function/create_${type}.php`;
            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(`เกิดข้อผิดพลาดในการบันทึกข้อมูล ${type === 'service' ? 'บริการ' : 'อุปกรณ์'}`);
                }
            });
        }
           
    </script>
</body>
</html>