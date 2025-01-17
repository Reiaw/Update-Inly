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
        .dataTables_filter {
            display: none;
        }

        .common-table thead th {
            background-color: #4a5568;
            color: white;
        }

        .common-table tbody tr {
            background-color: rgb(255, 255, 255);
        }

        .common-table tbody tr:hover {
            background-color: rgb(198, 198, 198);
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include './components/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-4">ข้อมูลบริการสำหรับบิล: <?php echo htmlspecialchars($bill['number_bill']); ?></h1>

        <div class="mt-6">
            <button onclick="openModal('service')" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">เพิ่มบริการ</button>
            <h2 class="text-xl font-bold mb-4">ข้อมูลหมายเลขบริการบิลนี้</h2>
            <table id="serviceTable" class="common-table min-w-full bg-white border border-gray-300">
                <thead>
                    <tr>
                        <th class="py-2 px-4 border-b">ลำดับที่</th>
                        <th class="py-2 px-4 border-b">รหัสบริการ</th>
                        <th class="py-2 px-4 border-b">ประเภทบริการ</th>
                        <th class="py-2 px-4 border-b">ประเภทอุปกรณ์</th>
                        <th class="py-2 px-4 border-b">สถานะบริการ</th>
                        <th class="py-2 px-4 border-b">การดำเนินการ</th>
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
                                    <button onclick="openModal('service', <?php echo $service['id_service']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded"> <i class="fas fa-edit"></i></button>
                                    <button onclick="deleteItem('service', <?php echo $service['id_service']; ?>)" class="bg-red-500 text-white px-2 py-1 rounded"><i class="fas fa-trash"></i></button>
                                    <a href="service_detail.php?id_service=<?php echo $service['id_service']; ?>" class="bg-blue-500 text-white px-2 py-1 rounded-md">
                                        <i class="fas fa-info-circle"></i> Info
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
            <button onclick="openModal('gedget')" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">เพิ่มอุปกรณ์</button>
            <h2 class="text-xl font-bold mb-4">ข้อมูลอุปกรณ์ของบิลนี้</h2>
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
                                    <button onclick="openModal('gedget', <?php echo $gedget['id_gedget']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded"> <i class="fas fa-edit"></i></button>
                                    <button onclick="deleteItem('gedget', <?php echo $gedget['id_gedget']; ?>)" class="bg-red-500 text-white px-2 py-1 rounded"><i class="fas fa-trash"></i></button>
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
            <button onclick="openModal('group')" class="bg-blue-500 text-white px-4 py-2 rounded mb-4">สร้างกลุ่ม</button>
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
                                    <button onclick="openModal('group', <?php echo $group['id_group']; ?>)" class="bg-yellow-500 text-white px-2 py-1 rounded"> <i class="fas fa-edit"></i></button>
                                    <button onclick="deleteItem('group', <?php echo $group['id_group']; ?>)" class="bg-red-500 text-white px-2 py-1 rounded"><i class="fas fa-trash"></i></button>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
    <script>
        $(document).ready(function() {
            function initializeDataTable(tableId) {
                const tableElement = $(`#${tableId}`);
                if (!tableElement.length) return;

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
                    "ordering": hasData,
                    "paging": hasData,
                    "info": hasData,
                    "searching": hasData,
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

                if (!hasData) {
                    tableElement.find('tbody').addClass('empty-table');
                }

                return table;
            }

            const serviceTable = initializeDataTable('serviceTable');
            const gedgetTable = initializeDataTable('gedgetTable');
            const groupTable = initializeDataTable('groupTable');

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
                fetch(`../function/get_${type}.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        modalTitle.innerText = `แก้ไข ${type === 'service' ? 'บริการ' : type === 'gedget' ? 'อุปกรณ์' : 'กลุ่ม'}`;
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
                modalTitle.innerText = `สร้าง ${type === 'service' ? 'บริการ' : type === 'gedget' ? 'อุปกรณ์' : 'กลุ่ม'}`;
                modalForm.reset();
                modalElement.classList.remove('hidden');
            }
        }

        function closeModal(type) {
            const modalElement = document.getElementById(`${type}Modal`);
            modalElement.classList.add('hidden');
        }

        function deleteItem(type, id) {
            const itemName = type === 'service' ? 'บริการ' : type === 'gedget' ? 'อุปกรณ์' : 'กลุ่ม';
            if (confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบ ${itemName} นี้?`)) {
                fetch(`../function/delete_${type}.php?id=${id}`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(`เกิดข้อผิดพลาดในการลบ ${itemName}`);
                        }
                    });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const serviceForm = document.getElementById('serviceForm');
            const gedgetForm = document.getElementById('gedgetForm');
            const groupForm = document.getElementById('groupForm');

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
            if (groupForm) {
                groupForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    handleFormSubmit('group', this);
                });
            }
        });

        function handleFormSubmit(type, form) {
            const formData = new FormData(form);
            const data = {};
            
            formData.forEach((value, key) => {
                data[key] = value;
            });

            data.id_bill = <?php echo $id_bill; ?>;

            if (type === 'service') {
                if (!data.code_service || !data.type_service || !data.type_gadget) {
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                    return;
                }
            } else if (type === 'gedget') {
                if (!data.name_gedget || !data.quantity_gedget) {
                    alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                    return;
                }
            } else if (type === 'group') {
                if (!data.group_name) {
                    alert('กรุณากรอกชื่อกลุ่ม');
                    return;
                }
            }

            const isEdit = formData.get(`id_${type}`) ? true : false;
            const url = isEdit ? 
                `../function/update_${type}.php` : 
                `../function/create_${type}.php`;

            fetch(url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert(result.message || `เกิดข้อผิดพลาดในการ${isEdit ? 'แก้ไข' : 'บันทึก'}ข้อมูล`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            });
        }
    </script>
</body>
</html>