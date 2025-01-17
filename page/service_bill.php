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
    $sql = "SELECT * FROM gedget WHERE id_bill = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bill);
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
                                    <a href="service_detail.php?id_service=<?php echo $service['id_service']; ?>" class="bg-blue-500 text-white px-2 py-1 rounded-md">
                                        <i class="fas fa-info-circle"></i> Info<!-- ไอคอน Info -->
                                    </a>
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
        <div class="mt-6">
            <button onclick="openGroupModal()" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">สร้างกลุ่ม</button>
            <h2 class="text-xl font-bold mb-4">ข้อมูลกลุ่ม</h2>
            <table id="groupTable" class="common-table min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ลำดับที่</th>
                        <th class="py-2 px-4 border-b">ชื่อกลุ่ม</th>
                        <th class="py-2 px-4 border-b">บริการ</th>
                        <th class="py-2 px-4 border-b">อุปกรณ์</th>
                        <th class="py-2 px-4 border-b">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM group_service WHERE id_bill = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $id_bill);
                    $stmt->execute();
                    $groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    if (!empty($groups)): ?>
                        <?php foreach ($groups as $group): ?>
                            <?php
                            // ดึงข้อมูล service และ gedget ที่อยู่ในกลุ่ม
                            $sql = "SELECT * FROM group_servicedetail WHERE id_group = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $group['id_group']);
                            $stmt->execute();
                            $groupDetails = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                            $servicesInGroup = [];
                            $gedgetsInGroup = [];

                            foreach ($groupDetails as $detail) {
                                if ($detail['id_service']) {
                                    $sql = "SELECT * FROM service_customer WHERE id_service = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $detail['id_service']);
                                    $stmt->execute();
                                    $service = $stmt->get_result()->fetch_assoc();
                                    if ($service) {
                                        $servicesInGroup[] = $service['code_service'];
                                    }
                                }
                                if ($detail['id_gedget']) {
                                    $sql = "SELECT * FROM gedget WHERE id_gedget = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $detail['id_gedget']);
                                    $stmt->execute();
                                    $gedget = $stmt->get_result()->fetch_assoc();
                                    if ($gedget) {
                                        $gedgetsInGroup[] = $gedget['name_gedget'];
                                    }
                                }
                            }
                            ?>
                            <tr>
                                <td class="py-2 px-4 border-b text-center"><?php echo $group['id_group']; ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo htmlspecialchars($group['group_name']); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo implode(', ', $servicesInGroup); ?></td>
                                <td class="py-2 px-4 border-b text-center"><?php echo implode(', ', $gedgetsInGroup); ?></td>
                                <td class="py-2 px-4 border-b text-center">
                                    <button onclick="openEditGroupModal(<?php echo $group['id_group']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded"> <i class="fas fa-edit"></i></button>
                                    <button onclick="deleteGroup(<?php echo $group['id_group']; ?>)" class="bg-red-500 text-white px-2 py-1 rounded"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="py-2 px-4 border-b text-center">ไม่มีข้อมูลกลุ่ม</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include './components/service_modal.php'; ?>
    <?php include './components/gedget_modal.php'; ?>
    <?php include './components/group_modal.php'; ?>
    <!-- jQuery และ DataTables JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script>
        $(document).ready(function() {
            // ฟังก์ชันสำหรับตั้งค่า DataTables
            function initializeDataTable(tableId) {
                // ตรวจสอบว่ามีตารางอยู่จริง
                const tableElement = $(`#${tableId}`);
                if (!tableElement.length) return;

                // ตรวจสอบว่ามีข้อมูลในตารางหรือไม่
                const hasData = tableElement.find('tbody tr').length > 0 && 
                            !tableElement.find('tbody tr td[colspan]').length;

                const config = {
                    "pageLength": 10,
                    "lengthMenu": [[10, 20, 50, 100, -1], [10, 20, 50, 100, "ทั้งหมด"]],
                    "language": {
                        "search": "ค้นหา:",
                        "lengthMenu": "แสดง _MENU_ แถวต่อหน้า",
                        "info": "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ แถว",
                        "infoEmpty": "ไม่มีข้อมูลที่แสดง",
                        "emptyTable": "ไม่มีข้อมูลในตาราง",
                        "zeroRecords": "ไม่พบข้อมูลที่ค้นหา",
                        "paginate": {
                            "first": "แรก",
                            "last": "สุดท้าย",
                            "next": "ถัดไป",
                            "previous": "ก่อนหน้า"
                        }
                    },
                    "ordering": hasData, // เปิดการเรียงลำดับเฉพาะเมื่อมีข้อมูล
                    "paging": hasData,   // เปิดการแบ่งหน้าเฉพาะเมื่อมีข้อมูล
                    "info": hasData,     // แสดงข้อมูลเพิ่มเติมเฉพาะเมื่อมีข้อมูล
                    "searching": hasData, // เปิดการค้นหาเฉพาะเมื่อมีข้อมูล
                    "columnDefs": hasData ? [
                        {
                            "targets": 0,
                            "render": function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        }
                    ] : []
                };

                const table = tableElement.DataTable(config);

                // จัดการกรณีไม่มีข้อมูล
                if (!hasData) {
                    tableElement.find('tbody').addClass('empty-table');
                }

                return table;
            }

            // สร้าง instance ของ DataTable สำหรับแต่ละตาราง
            const serviceTable = initializeDataTable('serviceTable');
            const gedgetTable = initializeDataTable('gedgetTable');
            const groupTable = initializeDataTable('groupTable');

            // อัพเดทลำดับเมื่อมีการเปลี่ยนหน้าหรือค้นหา
            if (serviceTable) {
                serviceTable.on('draw', function() {
                    updateRowNumbers(this);
                });
            }
            if (gedgetTable) {
                gedgetTable.on('draw', function() {
                    updateRowNumbers(this);
                });
            }
            if (groupTable) {
                groupTable.on('draw', function() {
                    updateRowNumbers(this);
                });
            }
        });

        // ฟังก์ชันอัพเดทลำดับแถว
        function updateRowNumbers(table) {
            $(table).find('tbody tr').each(function(index) {
                const firstCell = $(this).find('td:first');
                if (!firstCell.attr('colspan')) {
                    firstCell.text(index + 1);
                }
            });
        }
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
            const id = formData.get('id_group'); // ตรวจสอบ id_group
            const url = id ? `../function/update_group.php` : `../function/create_group_with_items.php`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_group: id,
                    id_bill: formData.get('id_bill'),
                    group_name: formData.get('group_name'),
                    services: Array.from(formData.getAll('services[]')).map(Number),
                    gedgets: Array.from(formData.getAll('gedgets[]')).map(Number)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                }
            });
        }
        function openGroupModal() {
            const modalElement = document.getElementById('groupModal');
            modalElement.classList.remove('hidden');
        }

        // ฟังก์ชันปิด Modal
        function closeGroupModal() {
            const modalElement = document.getElementById('groupModal');
            modalElement.classList.add('hidden');
        }

        // ฟังก์ชันส่งข้อมูลกลุ่มและบริการ/อุปกรณ์ไปยังเซิร์ฟเวอร์
        document.getElementById('groupForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);
            const idGroup = formData.get('id_group'); // ตรวจสอบ id_group
            const selectedServices = Array.from(document.querySelectorAll('input[name="services[]"]:checked')).map(input => input.value);
            const selectedGedgets = Array.from(document.querySelectorAll('input[name="gedgets[]"]:checked')).map(input => input.value);

            const data = {
                id_bill: formData.get('id_bill'),
                group_name: formData.get('group_name'),
                services: selectedServices,
                gedgets: selectedGedgets
            };

            // ถ้ามี id_group แสดงว่าเป็นการแก้ไขกลุ่ม
            if (idGroup) {
                data.id_group = idGroup; // เพิ่ม id_group เข้าไปในข้อมูล
            }

            // เลือก URL ตามว่ากำลังสร้างหรือแก้ไขกลุ่ม
            const url = idGroup ? '../function/update_group.php' : '../function/create_group_with_items.php';

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                }
            });
        });
        // ในส่วนของ JavaScript
        function openEditGroupModal(id_group) {
            fetch(`../function/get_group.php?id=${id_group}`)
                .then(response => response.json())
                .then(data => {
                    const modalElement = document.getElementById('groupModal');
                    const modalTitle = document.getElementById('modalTitle');
                    const groupForm = document.getElementById('groupForm');
                    const idGroupInput = document.getElementById('id_group');
                    const groupNameInput = document.getElementById('group_name');
                    const serviceList = document.getElementById('serviceList');
                    const gedgetList = document.getElementById('gedgetList');

                    modalTitle.innerText = 'แก้ไขกลุ่ม';
                    idGroupInput.value = data.group.id_group; // ตั้งค่า id_group
                    groupNameInput.value = data.group.group_name;

                    // เลือกบริการที่อยู่ในกลุ่ม
                    serviceList.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = data.services.includes(parseInt(checkbox.value));
                    });

                    // เลือกอุปกรณ์ที่อยู่ในกลุ่ม
                    gedgetList.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = data.gedgets.includes(parseInt(checkbox.value));
                    });

                    modalElement.classList.remove('hidden');
                });
        }
        
        function deleteGroup(id_group) {
            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบกลุ่มนี้?')) {
                fetch(`../function/delete_group.php?id=${id_group}`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาดในการลบกลุ่ม');
                        }
                    });
            }
        }
    </script>
</body>
</html>