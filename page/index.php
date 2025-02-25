<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/config.php';
require_once '../function/functions.php';
// Get bills data
$sql = "
    SELECT bc.number_bill, bc.end_date, bc.type_bill, bc.status_bill, c.name_customer, c.phone_customer 
    FROM bill_customer bc
    JOIN customers c ON bc.id_customer = c.id_customer
";
$result = $conn->query($sql);

$bills = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bills[] = $row;
    }
}
// Get tasks for the logged-in user
$user_id = $_SESSION['user_id']; // Assuming you store user_id in session
$task_sql = "
    SELECT t.*, tg.user_id 
    FROM task t
    JOIN task_group tg ON t.id_task = tg.task_id
    WHERE tg.user_id = ?
";
$stmt = $conn->prepare($task_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$task_result = $stmt->get_result();

$tasks = [];
if ($task_result->num_rows > 0) {
    while ($row = $task_result->fetch_assoc()) {
        // Only add task if it's not already in the tasks array
        $tasks[] = $row;
    }
}

if (isset($_POST['task_id'])) {
    $task_id = intval($_POST['task_id']);

    // ดึงข้อมูล task จากฐานข้อมูล
    $task_sql = "SELECT * FROM task WHERE id_task = ?";
    $stmt = $conn->prepare($task_sql);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $task_result = $stmt->get_result();

    if ($task_result->num_rows > 0) {
        $task = $task_result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ด</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #ffffff;
            color: #333333;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-top: auto;
            text-align: center;
        }
        .calendar-section {
            flex: 1;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: fit-content;
        }

        #calendar {
            max-width: 100%;
            height: 600px;
            margin: 0 auto;
        }
        .main-content {
            display: flex;
            flex-direction: row;
            gap: 20px;
            padding: 20px;
        }
        .timeline-section {
            flex: 2;
        }
        .calendar-section {
            flex: 1;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navbar -->
    <?php include './components/navbar.php'; ?>

    <!-- Hero Section -->
    <div class="bg-gray-900 text-yellow-500 py-24">
        <div class="container mx-auto px-4">
            <h1 class="text-4xl font-bold mb-4">ยินดีต้อนรับ, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
            <p class="text-lg max-w-2xl">
                นี่คือแดชบอร์ดของคุณ คุณสามารถใช้เมนูด้านบนเพื่อนำทาง
            </p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-12 main-content">
        <div class="bg-white shadow-lg rounded-lg p-8 timeline-section">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">ข้อมูลโดยสรุป</h2>
            <div class="grid grid-cols-1">
                <?php include './components/charts.php'; ?>
            </div>
        </div>
        <!-- Calendar Section -->
        <div class="bg-white shadow-lg rounded-lg p-8 calendar-section">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">ปฏิทิน</h2>
            <button onclick="openTaskModal()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">เพิ่มงานใหม่</button>
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer bg-gray-800 text-white">
        <div class="container mx-auto px-4">
            <span>© 2023 บริษัทของคุณ สงวนลิขสิทธิ์.</span>
        </div>
    </footer>

    <!-- Include Modal -->
    <?php include './components/info_calender.php'; ?>
    <?php include './components/info_task.php'; ?>
    <?php include './components/task_modal.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($task)): ?>
                // แสดง modal info_task
                document.getElementById('taskDetailModal').classList.remove('hidden');
                document.getElementById('taskTitle').innerText = 'หัวข้อ: ' + '<?php echo htmlspecialchars($task['name_task']); ?>';
                document.getElementById('taskDetail').innerText = 'รายละเอียด: ' + '<?php echo htmlspecialchars($task['detail_task']); ?>';
                document.getElementById('taskDates').innerText = 'วันที่เริ่ม: ' + '<?php echo $task['start_date']; ?>' + 
                                                                '\nวันที่สิ้นสุด: ' + '<?php echo $task['end_date']; ?>';
            <?php endif; ?>
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    // Bill events
                    <?php foreach ($bills as $bill): ?>
                    {
                        title: 'หมดสัญญาบิล',
                        start: '<?php echo $bill['end_date']; ?>',
                        backgroundColor: '#FF5722',
                        extendedProps: {
                            type: 'bill',
                            phone: '<?php echo htmlspecialchars($bill['phone_customer']); ?>',
                            billnum: '<?php echo $bill['number_bill']; ?>',
                            billtype: '<?php echo $bill['type_bill']; ?>',
                            customername: '<?php echo htmlspecialchars($bill['name_customer']); ?>',
                            billstatus: '<?php echo htmlspecialchars($bill['status_bill']); ?>'
                        }
                    },
                    <?php endforeach; ?>
                    
                    // Task events
                    <?php foreach ($tasks as $task): ?>
                    {
                        title: '<?php echo htmlspecialchars($task['name_task']); ?>',
                        start: '<?php echo $task['start_date']; ?>',
                        end: '<?php echo $task['end_date']; ?>',
                        extendedProps: {
                            type: 'task',
                            detail: '<?php echo htmlspecialchars($task['detail_task']); ?>',
                            reminder: '<?php echo $task['reminder_date']; ?>',
                            task_id: '<?php echo $task['id_task']; ?>' // เพิ่ม task_id เข้าไป
                        }
                    },
                    <?php endforeach; ?>
                ],
                eventClick: function(info) {
                    if (info.event.extendedProps.type === 'bill') {
                        // Show bill modal
                        document.getElementById('modalCustomerName').innerText = 'ชื่อลูกค้า: ' + info.event.extendedProps.customername;
                        document.getElementById('modalBillCode').innerText = 'Bill Code: ' + info.event.extendedProps.billnum;
                        document.getElementById('modalBillType').innerText = 'ประเภทบิล: ' + info.event.extendedProps.billtype;
                        document.getElementById('modalPhone').innerText = 'เบอร์ติดต่อ: ' + info.event.extendedProps.phone;
                        document.getElementById('modalBillStatus').innerText = 'สถานะบิล: ' + info.event.extendedProps.billstatus;
                        document.getElementById('eventModal').classList.remove('hidden');
                    } else {
                        // Show task modal
                        document.getElementById('taskDetailModal').classList.remove('hidden');
                        document.getElementById('taskTitle').innerText = 'หัวข้อ: ' + info.event.title;
                        document.getElementById('taskDetail').innerText = 'รายละเอียด: ' + info.event.extendedProps.detail;
                        document.getElementById('taskDates').innerText = 'วันที่เริ่ม: ' + info.event.start.toLocaleDateString() + 
                                                                        '\nวันที่สิ้นสุด: ' + (info.event.end ? info.event.end.toLocaleDateString() : 'ไม่ระบุ');
                        
                        // ดึง task_id จาก extendedProps
                        const taskId = info.event.extendedProps.task_id;
                        
                        // ตัวอย่างการส่ง task_id ไปยังปุ่มลบ task
                        document.getElementById('deleteTaskButton').dataset.taskId = taskId;
                    }
                }
            });
            calendar.render();
            
            // Close modals
            document.getElementById('okBtn').addEventListener('click', function() {
                document.getElementById('eventModal').classList.add('hidden');
            });
            
            document.getElementById('closeTaskModal').addEventListener('click', function() {
                document.getElementById('taskDetailModal').classList.add('hidden');
            });
        });
    </script>
</body>
</html>