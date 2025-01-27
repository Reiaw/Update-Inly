<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}
require_once '../config/config.php';
require_once '../function/functions.php';
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
        <!-- Timeline Section -->
        <div class="bg-white shadow-lg rounded-lg p-8 timeline-section">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">เส้นเวลาของบริษัท</h2>
            <div class="space-y-12">
                <!-- 2016 -->
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <div class="w-full md:w-1/3">
                        <h2 class="text-2xl font-bold mb-2 text-gray-800">2016</h2>
                        <h3 class="text-xl text-gray-700 mb-4">ความร่วมมือระหว่างโรงงานและสำนักงาน</h3>
                        <p class="text-gray-600">
                            บริษัทยึดมั่นในปรัชญาที่เน้นลูกค้าเป็นศูนย์กลาง ดำเนินการระบบการจัดการคุณภาพ และรักษาการสื่อสารที่ใกล้ชิดกับลูกค้าเพื่อให้บริการและผลิตภัณฑ์ที่เชื่อถือได้
                        </p>
                    </div>
                    <div class="w-full md:w-2/3">
                        <img src="/api/placeholder/800/400" alt="Office 2016" class="w-full h-64 object-cover rounded-lg shadow-lg">
                    </div>
                </div>

                <!-- 2018 -->
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <div class="w-full md:w-1/3">
                        <h2 class="text-2xl font-bold mb-2 text-gray-800">2018</h2>
                        <h3 class="text-xl text-gray-700 mb-4">การมาตรฐานของบริษัท</h3>
                        <p class="text-gray-600">
                            เมื่อบริษัทเติบโตขึ้น เรายังคงยึดมั่นในหลักการของความซื่อสัตย์ นวัตกรรม ความเป็นมืออาชีพ และประสิทธิภาพ พยายามให้บริการที่ดีขึ้นแก่ลูกค้าของเรา
                        </p>
                    </div>
                    <div class="w-full md:w-2/3 grid grid-cols-2 gap-4">
                        <img src="/api/placeholder/400/300" alt="Meeting 2018" class="w-full h-48 object-cover rounded-lg shadow-lg">
                        <img src="/api/placeholder/400/300" alt="Team 2018" class="w-full h-48 object-cover rounded-lg shadow-lg">
                    </div>
                </div>

                <!-- 2019 -->
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <div class="w-full md:w-1/3">
                        <h2 class="text-2xl font-bold mb-2 text-gray-800">2019</h2>
                        <h3 class="text-xl text-gray-700 mb-4">การควบคุมคุณภาพอย่างเข้มงวด</h3>
                        <p class="text-gray-600">
                            บริษัทยึดมั่นในแนวทาง "คุณภาพมาก่อน" ควบคุมคุณภาพผลิตภัณฑ์อย่างเข้มงวด และปรับปรุงกระบวนการผลิตอย่างต่อเนื่องเพื่อให้มั่นใจในผลิตภัณฑ์ที่มั่นคงและเชื่อถือได้
                        </p>
                    </div>
                    <div class="w-full md:w-2/3">
                        <img src="/api/placeholder/800/400" alt="Quality Control 2019" class="w-full h-64 object-cover rounded-lg shadow-lg">
                    </div>
                </div>

                <!-- 2020 -->
                <div class="flex flex-col md:flex-row gap-8 items-start">
                    <div class="w-full md:w-1/3">
                        <h2 class="text-2xl font-bold mb-2 text-gray-800">2020</h2>
                        <h3 class="text-xl text-gray-700 mb-4">ขยายตลาดต่างประเทศ</h3>
                        <p class="text-gray-600">
                            ด้วยการเปลี่ยนแปลงของเศรษฐกิจโลก บริษัทได้ขยายตลาดต่างประเทศอย่างแข็งขัน และสร้างความร่วมมือที่แข็งแกร่งกับหลายประเทศและภูมิภาค
                        </p>
                    </div>
                    <div class="w-full md:w-2/3 grid grid-cols-2 gap-4">
                        <img src="/api/placeholder/400/300" alt="International Meeting 2020" class="w-full h-48 object-cover rounded-lg shadow-lg">
                        <img src="/api/placeholder/400/300" alt="Global Partners 2020" class="w-full h-48 object-cover rounded-lg shadow-lg">
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="bg-white shadow-lg rounded-lg p-8 calendar-section">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">ปฏิทิน</h2>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: [
                    <?php foreach ($bills as $bill): ?>
                    {
                        title: 'หมดสัญญาบิล',
                        start: '<?php echo $bill['end_date']; ?>',
                        extendedProps: {
                            phone: '<?php echo htmlspecialchars($bill['phone_customer']); ?>',
                            billnum: '<?php echo $bill['number_bill']; ?>',
                            billtype: '<?php echo $bill['type_bill']; ?>',
                            customername: '<?php echo htmlspecialchars($bill['name_customer']); ?>',
                            billstatus: '<?php echo htmlspecialchars($bill['status_bill']); ?>'
                        }
                    },
                    <?php endforeach; ?>
                ],
                eventClick: function(info) {
                    // เติมข้อมูลลงใน modal
                    document.getElementById('modalCustomerName').innerText = 'ชื่อลูกค้า: ' + info.event.extendedProps.customername;
                    document.getElementById('modalBillCode').innerText = 'Bill Code: ' + info.event.extendedProps.billnum;
                    document.getElementById('modalBillType').innerText = 'ประเภทบิล: ' + info.event.extendedProps.billtype;
                    document.getElementById('modalPhone').innerText = 'เบอร์ติดต่อ: ' + info.event.extendedProps.phone;
                    document.getElementById('modalBillStatus').innerText = 'สถานะบิล: ' + info.event.extendedProps.billstatus;
                    // แสดง modal
                    document.getElementById('eventModal').classList.remove('hidden');
                }
            });
            calendar.render();

            // ปิด modal เมื่อคลิกปุ่ม OK
            document.getElementById('okBtn').addEventListener('click', function() {
                document.getElementById('eventModal').classList.add('hidden');
            });
        });
    </script>
</body>
</html>