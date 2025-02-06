<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php?error=not_logged_in');
    exit;
}

require_once '../config/config.php';
require_once '../function/functions.php';

$groupNames = ['ค่าอุปกรณ์ Solution ใหม่', 'ค่าอุปกรณ์ทดแทน Solution เดิม', 'ค่าอุปกรณ์ Solution เดิม', 'ต้นทุนการดำเนินการ'];
$sql = "SELECT id_customer, name_customer FROM customers";
$result = $conn->query($sql);

$customers = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสนอราคา</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include './components/navbar.php'; ?>
    <div class="container mx-auto p-4">
        <!-- Tab Buttons -->
        <div class="flex mb-4">
            <button id="quote-tab" onclick="saveGroupData();saveSalesData();switchTab('quote-form')" 
                    class="px-4 py-2 rounded-t-lg bg-gray-200 ml-1 hover:bg-gray-300">
                ราคาประมาณ
            </button>
            <button id="summary-tab" onclick="saveGroupData();saveSalesData();switchTab('summary')" 
                    class="px-4 py-2 rounded-t-lg bg-gray-200 ml-1 hover:bg-gray-300">
                ขายขาด ISI (สรุป)
            </button>
        </div>

        <!-- Tab Content -->
        <div id="quote-form" class="tab-content bg-white p-4 rounded-b-lg shadow-md">
            <h1 class="text-2xl font-bold mb-4">ใบเสนอราคา</h1>
                <div class="mb-4 p-4 border rounded bg-gray-50">
                    <div class="grid grid-cols-2 gap-4">
                        <!-- เลือกลูกค้า -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ลูกค้า:</label>
                            <select id="customer-select" class="w-full p-2 border rounded mt-1">
                                <option value="">-- เลือกลูกค้า --</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id_customer'] ?>"><?= htmlspecialchars($customer['name_customer']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">โครงการ:</label>
                            <input type="text" id="project-name" class="w-full p-2 border rounded mt-1" placeholder="ระบุชื่อโครงการ..." >
                        </div>
                    </div>
                </div>
                <!-- Groups 1-3 Combined -->
                <div class="bg-white p-4 rounded-lg shadow-md mb-4">
                    <h2 class="text-lg font-semibold mb-4">รายละเอียดประมาณการต้นทุนค่าอุปกรณ์ Solution</h2>
                    <table class="w-full mb-4 border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-2 px-3 border border-gray-300 text-left">รายการ</th>
                                <th class="py-2 px-3 border border-gray-300">ราคาต่อหน่วย(บาท)</th>
                                <th class="py-2 px-3 border border-gray-300">จำนวน(หน่วย)</th>
                                <th class="py-2 px-3 border border-gray-300">หมายเหตุ</th>
                                <th class="py-2 px-3 border border-gray-300">เป็นเงินบาท(ไม่รวม vat)</th>
                                <th class="py-2 px-3 border border-gray-300"></th>
                            </tr>
                        </thead>
                        <?php foreach (array_slice($groupNames, 0, 3) as $index => $groupName): 
                            $groupId = $index + 1;
                        ?>
                            <tbody>
                                <tr class="bg-gray-100">
                                    <th colspan="5" class="text-left py-2 px-3">กลุ่ม <?= $groupName ?></th>
                                </tr>
                            </tbody>
                            <tbody id="group<?= $groupId ?>-items"></tbody>
                            <tbody>
                                <tr class="bg-gray-50">
                                    <td colspan="3" class="text-right font-semibold py-2 px-3 border border-gray-300">รวมกลุ่ม <?= $groupName ?></td>
                                    <td id="group<?= $groupId ?>-total" class="font-semibold py-2 px-3 border border-gray-300">0.00</td>
                                    <td class="py-2 px-3 border border-gray-300"></td>
                                </tr>
                            </tbody>
                        <?php endforeach; ?>
                        <tfoot>
                            <tr class="bg-gray-50">
                                <td colspan="3" class="text-right font-semibold py-2 px-3 border border-gray-300">รวมทังสิ้น</td>
                                <td id="combined-total" class="font-semibold py-2 px-3 border border-gray-300">0.00 บาท</td>
                                <td class="py-2 px-3 border border-gray-300"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php foreach (array_slice($groupNames, 0, 3) as $index => $groupName): 
                            $groupId = $index + 1;
                        ?>
                            <button onclick="addItem(<?= $groupId ?>)" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                                + เพิ่มรายการ<?= $groupName ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Group 4 -->
                <div class="bg-white p-4 rounded-lg shadow-md mb-4">
                    <h2 class="text-lg font-semibold mb-4">รายละเอียดประมาณการต้นทุนค่าดำเนินการ</h2>
                    <table class="w-full mb-4 border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-2 px-3 border border-gray-300 text-left">รายการ</th>
                                <th class="py-2 px-3 border border-gray-300">ราคาต่อหน่วย(บาท)</th>
                                <th class="py-2 px-3 border border-gray-300">จำนวน(หน่วย)</th>
                                <th class="py-2 px-3 border border-gray-300">หมายเหตุ</th>
                                <th class="py-2 px-3 border border-gray-300">บาท(ไม่รวม vat)</th>
                                <th class="py-2 px-3 border border-gray-300"></th>
                            </tr>
                        </thead>
                        <tbody id="group4-items"></tbody>
                            <tr class="bg-gray-100">
                                <th colspan="5" class="text-left py-2 px-3">กลุ่ม <?= $groupNames[3] ?></th>
                            </tr>
                        <tfoot>
                            <tr class="bg-gray-50">
                                <td colspan="3" class="text-right font-semibold py-2 px-3 border border-gray-300">รวมทั้งหมด</td>
                                <td id="group4-total" class="font-semibold py-2 px-3 border border-gray-300">0.00 บาท</td>
                                <td class="py-2 px-3 border border-gray-300"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <button onclick="addItem(4)" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg w-full">
                        + เพิ่มรายการ<?= $groupNames[3] ?>
                    </button>
                </div>
                <!-- เพิ่ม Block สำหรับต้นทุนรวมโครงการและงบประมาณที่ขอใช้ -->
                <div class="bg-white p-4 rounded-lg shadow-md mb-4">
                    <table class="w-full mb-4 border-collapse border border-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="py-2 px-3 border border-gray-300 text-left">รายละเอียด</th>
                                <th class="py-2 px-3 border border-gray-300">จำนวนเงิน (บาท)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-gray-100">
                                <td class="text-left py-2 px-3">ต้นทุนรวมโครงการทั้งโครงการ</td>
                                <td id="project-total" class="font-semibold py-2 px-3 border border-gray-300">0.00 บาท(ไม่รวม vat)</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="text-left py-2 px-3">งบประมาณที่ขอใช้</td>
                                <td id="requested-budget" class="font-semibold py-2 px-3 border border-gray-300">0.00 บาท(ไม่รวม vat)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="summary" class="tab-content hidden bg-white p-4 rounded-b-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4">สรุปรายการขายขาด ISI</h2>
            
            <!-- Update the summary tables HTML structure -->
            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-2"><?= $groupNames[0] ?></h3>
                <div class="flex">
                    <table class="w-5/6 border-collapse border border-gray-200">
                        <!-- Existing table header and content -->
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-3 border">รายการ</th>
                                <th class="py-2 px-3 border">จำนวน</th>
                                <th class="py-2 px-3 border">ราคาทุนต่อหน่วย</th>
                                <th class="py-2 px-3 border">รวมทุน</th>
                                <th class="py-2 px-3 border">ราคาขายต่อหน่วย</th>
                                <th class="py-2 px-3 border">รวมขาย</th>
                                <th class="py-2 px-3 border">ผลต่าง</th>
                            </tr>
                        </thead>
                        <tbody id="group1-summary" class="text-center"></tbody>
                        <tfoot>
                            <tr class="bg-gray-50 font-semibold">
                                <td colspan="3" class="py-2 px-3 border text-right">รวมทั้งสิ้น</td>
                                <td id="group1-total-cost" class="py-2 px-3 border"></td>
                                <td class="py-2 px-3 border"></td>
                                <td id="group1-total-sales" class="py-2 px-3 border"></td>
                                <td id="group1-total-difference" class="py-2 px-3 border"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="w-1/6 p-4 bg-gray-50 border-r border-t border-b">
                        <div class="text-lg font-semibold mb-2">% กำไร</div>
                        <div id="group1-profit-percentage" class="text-2xl font-bold text-green-600">0.00%</div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <h3 class="text-xl font-semibold mb-2"><?= $groupNames[3] ?></h3>
                <div class="flex">
                    <table class="w-5/6 border-collapse border border-gray-200">
                        <!-- Existing table header and content -->
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-3 border">รายการ</th>
                                <th class="py-2 px-3 border">จำนวน</th>
                                <th class="py-2 px-3 border">ราคาทุนต่อหน่วย</th>
                                <th class="py-2 px-3 border">รวมทุน</th>
                                <th class="py-2 px-3 border">ราคาขายต่อหน่วย</th>
                                <th class="py-2 px-3 border">รวมขาย</th>
                                <th class="py-2 px-3 border">ผลต่าง</th>
                            </tr>
                        </thead>
                        <tbody id="group4-summary" class="text-center"></tbody>
                        <tfoot>
                            <tr class="bg-gray-50 font-semibold">
                                <td colspan="3" class="py-2 px-3 border text-right">รวมทั้งสิ้น</td>
                                <td id="group4-total-cost" class="py-2 px-3 border"></td>
                                <td class="py-2 px-3 border"></td>
                                <td id="group4-total-sales" class="py-2 px-3 border"></td>
                                <td id="group4-total-difference" class="py-2 px-3 border"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="w-1/6 p-4 bg-gray-50 border-r border-t border-b">
                        <div class="text-lg font-semibold mb-2">% กำไร</div>
                        <div id="group4-profit-percentage" class="text-2xl font-bold text-green-600">0.00%</div>
                    </div>
                </div>
            </div>

            <!-- Add grand total section -->
             <!-- Add VAT rate control -->
             <div class="mb-4 p-4 bg-white rounded-lg shadow">
                <label class="font-semibold">อัตรา VAT:</label>
                <select id="vat-rate" class="ml-2 p-2 border rounded" onchange="updateGrandTotals()">
                    <option value="0">0%</option>
                    <option value="7" selected>7%</option>
                </select>
            </div>

            <!-- Update grand total section with VAT calculations -->
            <div class="mt-8 bg-white p-6 rounded-xl border shadow-md">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Cost Section -->
                    <div class="border-r pr-6">
                        <h3 class="font-bold text-xl mb-4">ต้นทุน</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">รวมต้นทุน (ก่อน VAT):</span>
                                <span id="grand-total-cost" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">VAT:</span>
                                <span id="cost-vat" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between border-t pt-2 font-bold text-lg">
                                <span>รวมต้นทุนทั้งหมด (รวม VAT):</span>
                                <span id="grand-total-cost-with-vat"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Section -->
                    <div class="pl-6">
                        <h3 class="font-bold text-xl mb-4">การขาย</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">รวมยอดขาย (ก่อน VAT):</span>
                                <span id="grand-total-sales" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">VAT:</span>
                                <span id="sales-vat" class="font-semibold"></span>
                            </div>
                            <div class="flex justify-between border-t pt-2 font-bold text-lg">
                                <span>รวมยอดขายทั้งหมด (รวม VAT):</span>
                                <span id="grand-total-sales-with-vat"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profit Section -->
                <div class="mt-8 pt-6 border-t">
                    <h3 class="font-bold text-xl mb-4">กำไร</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">กำไรขั้นต้น (ก่อน VAT):</span>
                            <span id="total-profit" class="font-semibold text-green-600"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">กำไรขั้นต้น (รวม VAT):</span>
                            <span id="total-profit-with-vat" class="font-semibold text-green-600"></span>
                        </div>
                        <div class="flex justify-between border-t pt-2 font-bold text-lg text-green-600">
                            <span>% กำไร (รวม VAT):</span>
                            <span id="total-profit-percentage-with-vat"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  
    
    <script>
        function addItem(groupId) {
            const itemRow = `
                <tr>
                    <td><input type="text" class="item-name w-full p-1 border rounded" oninput="calculateTotal(${groupId})"></td>
                    <td><input type="number" class="item-price w-full p-1 border rounded" oninput="calculateTotal(${groupId})"></td>
                    <td><input type="number" class="item-quantity w-full p-1 border rounded" oninput="calculateTotal(${groupId})"></td>
                    <td><input type="text" class="w-full p-1 border rounded" placeholder="หมายเหตุ..."></td>
                    <td><span class="item-total">0.00</span></td>
                    <td><button onclick="removeItem(this, ${groupId})" class="text-red-500"><i class="fas fa-trash"></i></button></td>
                </tr>
            `;
            document.getElementById(`group${groupId}-items`).insertAdjacentHTML('beforeend', itemRow);
            calculateTotal(groupId);
        }

        function removeItem(button, groupId) {
            const row = button.closest('tr');
            row.remove();
            calculateTotal(groupId);
        }

        function calculateTotal(groupId) {
            const rows = document.querySelectorAll(`#group${groupId}-items tr`);
            let total = 0;

            rows.forEach(row => {
                const priceInput = row.querySelector('.item-price');
                const quantityInput = row.querySelector('.item-quantity');

                const price = parseFloat(priceInput.value) || 0;
                const quantity = parseFloat(quantityInput.value) || 0;

                const itemTotal = price * quantity;
                row.querySelector('.item-total').textContent = itemTotal.toFixed(2);
                total += itemTotal;
            });

            document.getElementById(`group${groupId}-total`).textContent = total.toFixed(2) ;

            // Update combined total for groups 1-3
            if (groupId >= 1 && groupId <= 3) {
                updateCombinedTotal();
            }

            // Update total for group 4
            if (groupId === 4) {
                updateProjectTotal();
            }
        }

        function updateCombinedTotal() {
            const group1 = parseFloat(document.getElementById('group1-total').textContent) || 0;
            const group2 = parseFloat(document.getElementById('group2-total').textContent) || 0;
            const group3 = parseFloat(document.getElementById('group3-total').textContent) || 0;
            const total = group1 + group2 + group3;
            document.getElementById('combined-total').textContent = total.toFixed(2) ;

            // Update requested budget (group 1 + 2)
            const requestedBudget = group1 + group2;
            document.getElementById('requested-budget').textContent = requestedBudget.toFixed(2) + ' บาท(ไม่รวม vat)';
        }

        function updateProjectTotal() {
            const group1 = parseFloat(document.getElementById('group1-total').textContent) || 0;
            const group2 = parseFloat(document.getElementById('group2-total').textContent) || 0;
            const group3 = parseFloat(document.getElementById('group3-total').textContent) || 0;
            const group4 = parseFloat(document.getElementById('group4-total').textContent) || 0;

            const total = group1 + group2 + group3 + group4;
            document.getElementById('project-total').textContent = total.toFixed(2) + ' บาท(ไม่รวม vat)';
        }
        function switchTab(tabId) {
            // ซ่อนเนื้อหาทั้งหมดและรีเซ็ตสไตล์แท็บ
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Reset all tab buttons to default style
            const quoteBtnTab = document.getElementById('quote-tab');
            const summaryBtnTab = document.getElementById('summary-tab');
            
            [quoteBtnTab, summaryBtnTab].forEach(btn => {
                if (btn) {
                    btn.classList.replace('bg-blue-500', 'bg-gray-200');
                    btn.classList.replace('text-white', 'text-black');
                }
            });

            // แสดงเนื้อหาที่เลือก
            const selectedContent = document.getElementById(tabId);
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
            }

            // อัปเดตสไตล์แท็บที่เลือก
            let activeTabBtn;
            if (tabId === 'quote-form') {
                activeTabBtn = quoteBtnTab;
            } else if (tabId === 'summary') {
                activeTabBtn = summaryBtnTab;
            }

            if (activeTabBtn) {
                activeTabBtn.classList.replace('bg-gray-200', 'bg-blue-500');
                activeTabBtn.classList.replace('text-black', 'text-white');
            }

            // หากเป็นแท็บสรุปให้โหลดข้อมูล
            if (tabId === 'summary') {
                loadGroupData();
            }
        }
        // ฟังก์ชันสำหรับบันทึกราคาขาย
        function saveGroupData() {
            // Calculate totals for all groups before saving
            for (let i = 1; i <= 4; i++) {
                calculateTotal(i);
            }
            updateCombinedTotal();
            updateProjectTotal();

            let group1Data = [];
            let group4Data = [];

            // Get data from group 1
            document.querySelectorAll("#group1-items tr").forEach(row => {
                let item = {
                    name: row.querySelector(".item-name")?.value || "",
                    price: row.querySelector(".item-price")?.value || 0,
                    quantity: row.querySelector(".item-quantity")?.value || 0,
                    total: row.querySelector(".item-total")?.textContent || "0.00",
                    salesPrice: 0,
                    salesTotal: 0
                };
                group1Data.push(item);
            });

            // Get data from group 4 and combine into one item
            let group4Item = {
                name: "ค่าดำเนินการติดตั้ง",
                price: 0,
                quantity: 1,
                total: 0,
                salesPrice: 0,
                salesTotal: 0
            };

            document.querySelectorAll("#group4-items tr").forEach(row => {
                let price = parseFloat(row.querySelector(".item-price")?.value || 0);
                let total = parseFloat(row.querySelector(".item-total")?.textContent || "0.00");

                group4Item.price += price;
                group4Item.total += total;
            });

            group4Data.push(group4Item);

            // Save to localStorage
            localStorage.setItem("group1Data", JSON.stringify(group1Data));
            localStorage.setItem("group4Data", JSON.stringify(group4Data));
        }
        function saveSalesData() {
            const group1Data = JSON.parse(localStorage.getItem("group1Data")) || [];
            const group4Data = JSON.parse(localStorage.getItem("group4Data")) || [];

            // Save sales data for group 1
            document.querySelectorAll('#group1-summary tr').forEach((row, index) => {
                if (index < group1Data.length) {
                    const salesPriceInput = row.querySelector('.sales-price');
                    const salesTotalCell = row.querySelector('.sales-total');
                    if (salesPriceInput && salesTotalCell) {
                        group1Data[index].salesPrice = parseFloat(salesPriceInput.value) || 0;
                        group1Data[index].salesTotal = parseFloat(salesTotalCell.textContent.replace(/,/g, '')) || 0;
                    }
                }
            });

            // Save sales data for group 4
            document.querySelectorAll('#group4-summary tr').forEach((row, index) => {
                if (index < group4Data.length) {
                    const salesPriceInput = row.querySelector('.sales-price');
                    const salesTotalCell = row.querySelector('.sales-total');
                    if (salesPriceInput && salesTotalCell) {
                        group4Data[index].salesPrice = parseFloat(salesPriceInput.value) || 0;
                        group4Data[index].salesTotal = parseFloat(salesTotalCell.textContent.replace(/,/g, '')) || 0;
                    }
                }
            });

            // Update all totals
            updateGrandTotals();
            // Save updated data back to localStorage
            localStorage.setItem("group1Data", JSON.stringify(group1Data));
            localStorage.setItem("group4Data", JSON.stringify(group4Data));
        }
        function loadGroupData() {
            // ดึงข้อมูลจาก localStorage
            const group1Data = JSON.parse(localStorage.getItem("group1Data")) || [];
            const group4Data = JSON.parse(localStorage.getItem("group4Data")) || [];

            // สร้างแถวตารางสำหรับกลุ่ม 1
            const group1Summary = document.getElementById("group1-summary");
            let group1TotalCost = 0;
            let group1TotalSales = 0;

            group1Summary.innerHTML = group1Data.map((item, index) => {
                const cost = parseFloat(item.total);
                const quantity = parseFloat(item.quantity) || 0;
                const salesPrice = parseFloat(item.salesPrice) || 0;
                const salesTotal = quantity * salesPrice;  // คำนวณ salesTotal ใหม่จาก quantity และ salesPrice
                group1TotalCost += cost;
                group1TotalSales += salesTotal;
                const difference = salesTotal - cost;
                
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 border">${item.name}</td>
                        <td class="py-2 px-3 border">${quantity}</td>
                        <td class="py-2 px-3 border">${parseFloat(item.price).toLocaleString()}</td>
                        <td class="py-2 px-3 border">${cost.toLocaleString()}</td>
                        <td class="py-2 px-3 border">
                            <input type="number" 
                                class="sales-price w-full p-1 border rounded text-right" 
                                data-group="1" 
                                data-index="${index}" 
                                data-quantity="${quantity}"
                                value="${salesPrice}"
                                oninput="calculateSalesTotal(this)">
                        </td>
                        <td class="py-2 px-3 border sales-total font-semibold">
                            ${salesTotal.toLocaleString()}
                        </td>
                        <td class="py-2 px-3 border difference">${difference.toLocaleString()}</td>
                    </tr>
                `;
            }).join('');

            // สร้างแถวตารางสำหรับกลุ่ม 4
            const group4Summary = document.getElementById("group4-summary");
            let group4TotalCost = 0;
            let group4TotalSales = 0;

            group4Summary.innerHTML = group4Data.map((item, index) => {
                const cost = parseFloat(item.total);
                const quantity = parseFloat(item.quantity) || 0;
                const salesPrice = parseFloat(item.salesPrice) || 0;
                const salesTotal = quantity * salesPrice;  // คำนวณ salesTotal ใหม่จาก quantity และ salesPrice
                group4TotalCost += cost;
                group4TotalSales += salesTotal;
                const difference = salesTotal - cost;
                
                return `
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 border">${item.name}</td>
                        <td class="py-2 px-3 border">${quantity}</td>
                        <td class="py-2 px-3 border">${parseFloat(item.price).toLocaleString()}</td>
                        <td class="py-2 px-3 border">${cost.toLocaleString()}</td>
                        <td class="py-2 px-3 border">
                            <input type="number" 
                                class="sales-price w-full p-1 border rounded text-right" 
                                data-group="4" 
                                data-index="${index}" 
                                data-quantity="${quantity}"
                                value="${salesPrice}"
                                oninput="calculateSalesTotal(this)">
                        </td>
                        <td class="py-2 px-3 border sales-total font-semibold">
                            ${salesTotal.toLocaleString()}
                        </td>
                        <td class="py-2 px-3 border difference">${difference.toLocaleString()}</td>
                    </tr>
                `;
            }).join('');

            // แสดงยอดรวมทั้งหมด
            document.getElementById('group1-total-cost').textContent = group1TotalCost.toLocaleString();
            document.getElementById('group1-total-sales').textContent = group1TotalSales.toLocaleString();
            document.getElementById('group1-total-difference').textContent = (group1TotalSales - group1TotalCost).toLocaleString();

            document.getElementById('group4-total-cost').textContent = group4TotalCost.toLocaleString();
            document.getElementById('group4-total-sales').textContent = group4TotalSales.toLocaleString();
            document.getElementById('group4-total-difference').textContent = (group4TotalSales - group4TotalCost).toLocaleString();

            updateGrandTotals();
        }
        function calculateSalesTotal(input) {
            const row = input.closest('tr');
            const quantity = parseFloat(input.dataset.quantity);
            const salesPrice = parseFloat(input.value) || 0;
            const salesTotal = quantity * salesPrice;
            const cost = parseFloat(row.querySelector('td:nth-child(4)').textContent.replace(/,/g, '')) || 0;
            const difference = salesTotal - cost;

            row.querySelector('.sales-total').textContent = salesTotal.toLocaleString();
            row.querySelector('.difference').textContent = difference.toLocaleString();
            
            updateGrandTotals();
            saveSalesData();
        }
        function updateGrandTotals() {
        // Get VAT rate
        const vatRate = parseFloat(document.getElementById('vat-rate').value) / 100;

        // Calculate base totals
        const group1Cost = parseFloat(document.getElementById('group1-total-cost').textContent.replace(/,/g, '')) || 0;
        const group4Cost = parseFloat(document.getElementById('group4-total-cost').textContent.replace(/,/g, '')) || 0;
        const grandTotalCost = group1Cost + group4Cost;

        let group1Sales = 0;
        let group4Sales = 0;
        
        document.querySelectorAll('#group1-summary .sales-total').forEach(cell => {
            group1Sales += parseFloat(cell.textContent.replace(/,/g, '')) || 0;
        });
        
        document.querySelectorAll('#group4-summary .sales-total').forEach(cell => {
            group4Sales += parseFloat(cell.textContent.replace(/,/g, '')) || 0;
        });

        const grandTotalSales = group1Sales + group4Sales;

        // Calculate VAT amounts
        const costVat = grandTotalCost * vatRate;
        const salesVat = grandTotalSales * vatRate;

        // Calculate totals with VAT
        const grandTotalCostWithVat = grandTotalCost + costVat;
        const grandTotalSalesWithVat = grandTotalSales + salesVat;

        // Calculate profits
        const totalProfit = grandTotalSales - grandTotalCost;
        const totalProfitWithVat = grandTotalSalesWithVat - grandTotalCostWithVat;

        // Calculate profit percentages
        const totalProfitPercentage = grandTotalCost !== 0 ? ((grandTotalSales * 100) / grandTotalCost) - 100 : 0;
        const totalProfitPercentageWithVat = grandTotalCostWithVat !== 0 ? 
            ((grandTotalSalesWithVat * 100) / grandTotalCostWithVat) - 100 : 0;

        // Update group totals (remain unchanged)
        const group1Difference = group1Sales - group1Cost;
        const group4Difference = group4Sales - group4Cost;
        
        document.getElementById('group1-total-sales').textContent = group1Sales.toLocaleString();
        document.getElementById('group4-total-sales').textContent = group4Sales.toLocaleString();
        document.getElementById('group1-total-difference').textContent = group1Difference.toLocaleString();
        document.getElementById('group4-total-difference').textContent = group4Difference.toLocaleString();

        // Update group profit percentages
        const group1ProfitPercentage = group1Cost !== 0 ? ((group1Sales * 100) / group1Cost) - 100 : 0;
        const group4ProfitPercentage = group4Cost !== 0 ? ((group4Sales * 100) / group4Cost) - 100 : 0;
        
        document.getElementById('group1-profit-percentage').textContent = group1ProfitPercentage.toFixed(2) + '%';
        document.getElementById('group4-profit-percentage').textContent = group4ProfitPercentage.toFixed(2) + '%';

        // Update all totals
        document.getElementById('grand-total-cost').textContent = grandTotalCost.toLocaleString() + ' บาท';
        document.getElementById('cost-vat').textContent = costVat.toLocaleString() + ' บาท';
        document.getElementById('grand-total-cost-with-vat').textContent = grandTotalCostWithVat.toLocaleString() + ' บาท';

        document.getElementById('grand-total-sales').textContent = grandTotalSales.toLocaleString() + ' บาท';
        document.getElementById('sales-vat').textContent = salesVat.toLocaleString() + ' บาท';
        document.getElementById('grand-total-sales-with-vat').textContent = grandTotalSalesWithVat.toLocaleString() + ' บาท';

        document.getElementById('total-profit').textContent = totalProfit.toLocaleString() + ' บาท';
        document.getElementById('total-profit-with-vat').textContent = totalProfitWithVat.toLocaleString() + ' บาท';
        document.getElementById('total-profit-percentage-with-vat').textContent = totalProfitPercentageWithVat.toFixed(2) + '%';
    }
    </script>
</body>
</html>
