<?php
// ในส่วนการดึงข้อมูล
$billTypeSql = "SELECT type_bill, COUNT(*) as count FROM bill_customer GROUP BY type_bill";
$billTypeResult = $conn->query($billTypeSql);
$billTypes = [];
while($row = $billTypeResult->fetch_assoc()) {
    $billTypes[] = $row;
}
$billStatusSql = "SELECT status_bill, COUNT(*) as count FROM bill_customer GROUP BY status_bill";
$billStatusResult = $conn->query($billStatusSql);
$billStatuses = [];
while($row = $billStatusResult->fetch_assoc()) {
    $billStatuses[] = $row;
}
$customerTypeSql = "SELECT ct.type_customer, COUNT(c.id_customer) as count 
                   FROM customers c 
                   JOIN customer_types ct ON c.id_customer_type = ct.id_customer_type 
                   GROUP BY ct.type_customer";
$customerTypeResult = $conn->query($customerTypeSql);
$customerTypes = [];
while($row = $customerTypeResult->fetch_assoc()) {
    $customerTypes[] = $row;
}
$revenueSql = "SELECT c.id_customer, c.name_customer, SUM(o.all_price) as total_revenue
               FROM customers c
               JOIN bill_customer bc ON c.id_customer = bc.id_customer
               JOIN service_customer sc ON bc.id_bill = sc.id_bill
               JOIN package_list pl ON sc.id_service = pl.id_service
               JOIN product_list pr ON pl.id_package = pr.id_package
               JOIN overide o ON pr.id_product = o.id_product
               GROUP BY c.id_customer, c.name_customer";
$revenueResult = $conn->query($revenueSql);
$revenues = [];
while($row = $revenueResult->fetch_assoc()) {
    $revenues[] = $row;
}

// เพิ่มส่วนดึงข้อมูลประเภทบริการ
$serviceTypeSql = "SELECT type_service, COUNT(*) as count FROM service_customer GROUP BY type_service";
$serviceTypeResult = $conn->query($serviceTypeSql);
$serviceTypes = [];
while($row = $serviceTypeResult->fetch_assoc()) {
    $serviceTypes[] = $row;
}

// เพิ่มส่วนดึงข้อมูลประเภทอุปกรณ์
$deviceTypeSql = "SELECT type_gadget, COUNT(*) as count FROM service_customer GROUP BY type_gadget";
$deviceTypeResult = $conn->query($deviceTypeSql);
$deviceTypes = [];
while($row = $deviceTypeResult->fetch_assoc()) {
    $deviceTypes[] = $row;
}
?>
<div class="charts-carousel relative">
    <!-- แต่ละกราฟอยู่ในสไลด์ -->
    <div class="chart-slide active">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">สัดส่วนประเภทบิล</h3>
            <canvas id="billTypeChart" class="w-full h-64"></canvas>
        </div>
    </div>

    <div class="chart-slide">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">สถานะบิลทั้งหมด</h3>
            <canvas id="billStatusChart" class="w-full h-64"></canvas>
        </div>
    </div>
    <div class="chart-slide">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">ประเภทลูกค้า</h3>
            <canvas id="customerTypeChart" class="w-full h-64"></canvas>
        </div>
    </div>
    <div class="chart-slide">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">รายได้ต่อลูกค้า</h3>
            <canvas id="revenueChart" class="w-full h-64"></canvas>
        </div>
    </div>
    <div class="chart-slide">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">ประเภทของบริการ</h3>
            <canvas id="serviceTypeChart" class="w-full h-64"></canvas>
        </div>
    </div>
    <div class="chart-slide">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-bold mb-4">ประเภทของอุปกรณ์</h3>
            <canvas id="deviceTypeChart" class="w-full h-64"></canvas>
        </div>
    </div>
</div>
<div class="flex justify-center mt-4 space-x-4">
    <button id="prevBtn" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">ก่อนหน้า</button>
    <button id="nextBtn" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">ถัดไป</button>
</div>

<style>
.chart-slide {
    display: none;
}
.chart-slide.active {
    display: block;
}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartInstances = {};

function initChart(canvasId, config) {
    const ctx = document.getElementById(canvasId).getContext('2d');
    chartInstances[canvasId] = new Chart(ctx, config);
}

// การตั้งค่ากราฟทั้งหมด
initChart('billTypeChart', {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($billTypes, 'type_bill')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($billTypes, 'count')) ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
        }]
    }
});
initChart('billStatusChart', {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($billStatuses, 'status_bill')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($billStatuses, 'count')) ?>,
            backgroundColor: ['#FF6384', '#36A2EB']
        }]
    }
});
initChart('customerTypeChart', {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($customerTypes, 'type_customer')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($customerTypes, 'count')) ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
        }]
    }
});
initChart('revenueChart', {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($revenues, 'name_customer')) ?>,
        datasets: [{
            label: 'รายได้',
            data: <?= json_encode(array_column($revenues, 'total_revenue')) ?>,
            backgroundColor: '#36A2EB'
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
initChart('serviceTypeChart', {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($serviceTypes, 'type_service')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($serviceTypes, 'count')) ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
        }]
    }
});
initChart('deviceTypeChart', {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($deviceTypes, 'type_gadget')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($deviceTypes, 'count')) ?>,
            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
        }]
    }
});
let currentSlide = 0;
const slides = document.querySelectorAll('.chart-slide');

function updateSlide() {
    slides.forEach((slide, index) => {
        slide.classList.toggle('active', index === currentSlide);
        const canvas = slide.querySelector('canvas');
        if (index === currentSlide) {
            chartInstances[canvas.id].resize();
        }
    });
}

document.getElementById('prevBtn').addEventListener('click', () => {
    if (currentSlide > 0) {
        currentSlide--;
        updateSlide();
    }
});

document.getElementById('nextBtn').addEventListener('click', () => {
    if (currentSlide < slides.length - 1) {
        currentSlide++;
        updateSlide();
    }
});

// เริ่มต้นแสดงสไลด์แรก
updateSlide();
   
</script>