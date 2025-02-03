<?php
session_start();
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/config.php';
require_once '../function/functions.php';
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
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">ใบเสนอราคา</h1>
        
        <div class="bg-white rounded-lg shadow p-6">
            <form id="quotationForm">
                <!-- VAT Input -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        VAT %:
                        <input type="number" id="vatRate" class="border p-2 rounded w-32" value="7" min="0" max="100">
                    </label>
                </div>
                <button type="button" id="exportExcel" class="bg-yellow-500 text-white px-4 py-2 rounded ml-2">Export to Excel</button>
                <!-- ตารางรายการต้นทุน -->
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse border">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="border p-2">หมวดหมู่</th>
                                <th class="border p-2">ชื่อรายการ</th>
                                <th class="border p-2">จำนวนคน</th>
                                <th class="border p-2">จำนวน</th>
                                <th class="border p-2">ต้นทุน</th>
                                <th class="border p-2">ราคาขาย</th>           
                                <th class="border p-2">รวมต้นทุน</th>
                                <th class="border p-2">รวมราคาขาย</th>
                                <th class="border p-2">ส่วนต่าง</th>
                                <th class="border p-2">การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody id="items">
                            <!-- รายการจะถูกเพิ่มที่นี่ -->
                        </tbody>
                        <tfoot class="bg-gray-100 font-bold">
                            <tr>
                                <td colspan="6" class="border p-2 text-right">รวมทั้งหมด:</td>
                                <td class="border p-2" id="totalCost">0</td>
                                <td class="border p-2" id="totalPrice">0</td>
                                <td class="border p-2" id="totalProfit">0</td>
                                <td class="border p-2" id="totalPercent">0%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- ปุ่มเพิ่มหมวดหมู่และรายการ -->
                <div class="mt-4">
                    <button type="button" id="addCategory" class="bg-green-500 text-white px-4 py-2 rounded">เพิ่มหมวดหมู่</button>
                    <button type="button" id="addItem" class="bg-blue-500 text-white px-4 py-2 rounded ml-2">เพิ่มรายการ</button>
                </div>

                <!-- สรุปผลทั้งหมด -->
                <div class="summary mt-8 p-4 bg-gray-50 rounded">
                    <h2 class="text-xl font-bold mb-4">สรุปผลทั้งหมด</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded shadow">
                            <h3 class="font-bold mb-2 text-gray-700">ราคาต้นทุน</h3>
                            <p>รวมต้นทุนขาย: <span id="totalCostSummary">0</span> บาท</p>
                            <p>VAT ต้นทุน: <span id="vatCost">0</span> บาท</p>
                            <p class="font-bold text-red-600">รวมต้นทุนขาย + VAT: <span id="totalCostWithVat">0</span> บาท</p>
                        </div>
                        <div class="bg-white p-4 rounded shadow">
                            <h3 class="font-bold mb-2 text-gray-700">ราคาขาย</h3>
                            <p>รวมราคาขาย: <span id="totalPriceSummary">0</span> บาท</p>
                            <p>VAT ราคาขาย: <span id="vatPrice">0</span> บาท</p>
                            <p class="font-bold text-green-500">รวมราคาขาย + VAT: <span id="totalPriceWithVat">0</span> บาท</p>
                        </div>
                        <div class="bg-white p-4 rounded shadow">
                            <h3 class="font-bold mb-2 text-gray-700">กำไรสุทธิ์</h3>
                            <p>กำไรรวม: <span id="totalProfitSummary">0</span> บาท</p>
                            <p>VAT ส่วนต่าง: <span id="vatDifference">0</span> บาท</p>
                            <p class="font-bold text-blue-600">กำไรรวม + VAT: <span id="totalProfitWithVat">0</span> บาท</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const itemsContainer = document.getElementById('items');
            let currentCategory = '';

            // เพิ่มหมวดหมู่
            document.getElementById('addCategory').addEventListener('click', function() {
                currentCategory = prompt('กรุณาใส่ชื่อหมวดหมู่:');
                if (currentCategory) {
                    addCategoryRow(currentCategory);
                }
            });

            // เพิ่มรายการ
            document.getElementById('addItem').addEventListener('click', function() {
                if (!currentCategory) {
                    alert('กรุณาเพิ่มหมวดหมู่ก่อน');
                    return;
                }
                addItemRow(currentCategory);
            });

            // เพิ่มแถวหมวดหมู่
            function addCategoryRow(categoryName) {
                const tr = document.createElement('tr');
                tr.className = 'category-row bg-gray-200';
                tr.innerHTML = `
                    <td class="border p-2 font-bold" colspan="10">${categoryName}</td>
                `;
                itemsContainer.appendChild(tr);
            }

            // เพิ่มแถวรายการ
            function addItemRow(categoryName) {
                const tr = document.createElement('tr');
                tr.className = 'item-row';
                tr.innerHTML = `
                    <td class="border p-2">${categoryName}</td>
                    <td class="border p-2"><input type="text" class="item-name w-full border p-2 rounded" placeholder="ชื่อรายการ" required></td>
                    <td class="border p-2"><input type="number" class="people-count w-full border p-2 rounded" placeholder="จำนวนคน" value="0"></td>
                    <td class="border p-2"><input type="number" class="quantity w-full border p-2 rounded" placeholder="จำนวน" required></td>
                    <td class="border p-2"><input type="number" class="cost w-full border p-2 rounded" placeholder="ต้นทุน" required></td>
                    <td class="border p-2"><input type="number" class="price w-full border p-2 rounded" placeholder="ราคาขาย" required></td>
                    <td class="border p-2 total-cost">0</td>
                    <td class="border p-2 total-price">0</td>
                    <td class="border p-2 difference">0</td>
                    <td class="border p-2"><button type="button" class="remove-item bg-red-500 text-white px-4 py-2 rounded">ลบ</button></td>
                `;

                tr.querySelector('.remove-item').addEventListener('click', function() {
                    tr.remove();
                    calculateTotals();
                });

                tr.querySelectorAll('input').forEach(input => {
                    input.addEventListener('input', calculateTotals);
                });

                itemsContainer.appendChild(tr);
            }

            // คำนวณผลรวม
            function calculateTotals() {
                let totalCost = 0;
                let totalPrice = 0;
                let totalDifference = 0;
                let categories = new Map();
                let currentCategoryRows = [];

                // ลบแถวสรุปเก่าทั้งหมด
                document.querySelectorAll('.category-summary').forEach(row => row.remove());

                // วนลูปผ่านทุกแถวเพื่อคำนวณ
                document.querySelectorAll('#items tr').forEach(row => {
                    if (row.classList.contains('category-row')) {
                        if (currentCategoryRows.length > 0) {
                            addCategorySummary(currentCategoryRows[0], categories.get(currentCategoryRows[0].cells[0].textContent.trim()));
                        }
                        currentCategoryRows = [row];
                        categories.set(row.cells[0].textContent.trim(), {
                            cost: 0,
                            price: 0,
                            difference: 0
                        });
                    } else if (row.classList.contains('item-row')) {
                        currentCategoryRows.push(row);
                        const categoryName = row.cells[0].textContent.trim();
                        const cost = parseFloat(row.querySelector('.cost').value) || 0;
                        const price = parseFloat(row.querySelector('.price').value) || 0;
                        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                        const peopleCount = parseInt(row.querySelector('.people-count').value) || 0;

                        const itemTotalCost = peopleCount === 0 ? cost * quantity : cost * quantity * peopleCount;
                        const itemTotalPrice = peopleCount === 0 ? price * quantity : price * quantity * peopleCount;
                        const itemDifference = itemTotalPrice - itemTotalCost;

                        row.querySelector('.total-cost').textContent = itemTotalCost.toFixed(2);
                        row.querySelector('.total-price').textContent = itemTotalPrice.toFixed(2);
                        row.querySelector('.difference').textContent = itemDifference.toFixed(2);

                        const categoryTotals = categories.get(categoryName);
                        if (categoryTotals) {
                            categoryTotals.cost += itemTotalCost;
                            categoryTotals.price += itemTotalPrice;
                            categoryTotals.difference += itemDifference;
                        }

                        totalCost += itemTotalCost;
                        totalPrice += itemTotalPrice;
                        totalDifference += itemDifference;
                    }
                });

                // เพิ่มสรุปสำหรับหมวดหมู่สุดท้าย
                if (currentCategoryRows.length > 0) {
                    addCategorySummary(currentCategoryRows[0], categories.get(currentCategoryRows[0].cells[0].textContent.trim()));
                }

                updateSummaryValues(totalCost, totalPrice, totalDifference);
            }


            // เพิ่มแถวสรุปหมวดหมู่
            function addCategorySummary(categoryRow, totals) {
                if (!totals) return;

                const lastItemInCategory = findLastItemInCategory(categoryRow);
                if (!lastItemInCategory) return;

                // คำนวณเปอร์เซ็นต์กำไรในหมวดหมู่
                const profitPercent = totals.cost > 0 ? (totals.difference * 100) / totals.cost : 0;

                const summaryRow = document.createElement('tr');
                summaryRow.className = 'category-summary bg-gray-100 font-bold';
                summaryRow.innerHTML = `
                    <td colspan="6" class="border p-2 text-right">รวมหมวดหมู่: ${categoryRow.cells[0].textContent.trim()}</td>
                    <td class="border p-2">${totals.cost.toFixed(2)}</td>
                    <td class="border p-2">${totals.price.toFixed(2)}</td>
                    <td class="border p-2">${totals.difference.toFixed(2)}</td>
                    <td class="border p-2">${profitPercent.toFixed(2)}%</td>
                `;

                lastItemInCategory.parentNode.insertBefore(summaryRow, lastItemInCategory.nextSibling);
            }


            // หาแถวสุดท้ายของหมวดหมู่
            function findLastItemInCategory(categoryRow) {
                let currentRow = categoryRow.nextElementSibling;
                let lastItemRow = null;

                while (currentRow && !currentRow.classList.contains('category-row')) {
                    if (currentRow.classList.contains('item-row')) {
                        lastItemRow = currentRow;
                    }
                    currentRow = currentRow.nextElementSibling;
                }

                return lastItemRow;
            }

            // อัพเดทค่าสรุปทั้งหมด
            function updateSummaryValues(totalCost, totalPrice, totalDifference) {
                const vatRate = parseFloat(document.getElementById('vatRate').value) || 0;
                const vatMultiplier = vatRate / 100;

                const vatCost = totalCost * vatMultiplier;
                const vatPrice = totalPrice * vatMultiplier;
                const vatDifference = vatPrice - vatCost;

                const totalCostWithVat = totalCost + vatCost;
                const totalPriceWithVat = totalPrice + vatPrice;
                const totalProfitWithVat = totalPriceWithVat - totalCostWithVat;

                const totalPercent = totalCost > 0 ? (totalDifference * 100) / totalCost : 0;

                // อัพเดทค่าในตาราง
                document.getElementById('totalCost').textContent = totalCost.toFixed(2);
                document.getElementById('totalPrice').textContent = totalPrice.toFixed(2);
                document.getElementById('totalProfit').textContent = totalDifference.toFixed(2);
                document.getElementById('totalPercent').textContent = totalPercent.toFixed(2) + "%";

                // อัพเดทค่า VAT
                document.getElementById('vatCost').textContent = vatCost.toFixed(2);
                document.getElementById('vatPrice').textContent = vatPrice.toFixed(2);
                document.getElementById('vatDifference').textContent = vatDifference.toFixed(2);

                // อัพเดทค่ารวม VAT
                document.getElementById('totalCostWithVat').textContent = totalCostWithVat.toFixed(2);
                document.getElementById('totalPriceWithVat').textContent = totalPriceWithVat.toFixed(2);
                document.getElementById('totalProfitWithVat').textContent = totalProfitWithVat.toFixed(2);

                // อัพเดทค่าสรุป
                document.getElementById('totalCostSummary').textContent = totalCost.toFixed(2);
                document.getElementById('totalPriceSummary').textContent = totalPrice.toFixed(2);
                document.getElementById('totalProfitSummary').textContent = totalDifference.toFixed(2);
            }

            document.getElementById('vatRate').addEventListener('input', calculateTotals);
            document.getElementById('exportExcel').addEventListener('click', function() {
                // เรียกคำนวณผลรวมล่าสุดก่อนส่งออก
                calculateTotals();

                // สร้างข้อมูลสำหรับ Excel
                const wb = XLSX.utils.book_new();
                const ws_data = [];
                
                // ส่วนหัวตาราง
                ws_data.push([
                    'หมวดหมู่',
                    'ชื่อรายการ',
                    'จำนวนคน',
                    'จำนวน',
                    'ต้นทุน',
                    'ราคาขาย',
                    'รวมต้นทุน',
                    'รวมราคาขาย',
                    'ส่วนต่าง',
                    'กำไร (%)'
                ]);

                // เพิ่มข้อมูลแต่ละรายการ
                let currentCategory = '';
                document.querySelectorAll('#items tr').forEach(row => {
                    if (row.classList.contains('category-row')) {
                        currentCategory = row.cells[0].textContent.trim();
                    } else if (row.classList.contains('item-row')) {
                        const itemData = [
                            currentCategory,
                            row.querySelector('.item-name').value,
                            row.querySelector('.people-count').value || '0',
                            row.querySelector('.quantity').value || '0',
                            row.querySelector('.cost').value || '0',
                            row.querySelector('.price').value || '0',
                            row.querySelector('.total-cost').textContent,
                            row.querySelector('.total-price').textContent,
                            row.querySelector('.difference').textContent,
                            ''
                        ];
                        ws_data.push(itemData);
                    } else if (row.classList.contains('category-summary')) {
                        ws_data.push([
                            'ผลรวมของ ' + currentCategory,
                            '', '', '', '', '',
                            row.cells[1].textContent,
                            row.cells[2].textContent,
                            row.cells[3].textContent,
                            row.cells[4].textContent
                        ]);
                    }
                });
                
                // เพิ่มแถวสรุปทั้งหมดแบบ category-summary
                ws_data.push([
                    'ผลรวมทั้งหมด', '', '', '', '', '',
                    document.getElementById('totalCost').textContent,
                    document.getElementById('totalPrice').textContent,
                    document.getElementById('totalProfit').textContent,
                    document.getElementById('totalPercent').textContent
                ]);

                // เพิ่มแถวว่าง 2 แถวเพื่อแบ่งส่วน
                ws_data.push([], []);

                // ส่วนสรุปผลทั้งหมด
                const vatRate = document.getElementById('vatRate').value;
                
                // สรุปก่อน VAT
                ws_data.push(['สรุปผลทั้งหมด']);
                ws_data.push(['ราคาต้นทุน']);
                ws_data.push(['รวมต้นทุนขาย:', `${document.getElementById('totalCostSummary').textContent} บาท`]);
                ws_data.push(['VAT ต้นทุน:', `${document.getElementById('vatCost').textContent} บาท`]);
                ws_data.push(['รวมต้นทุน + VAT:', `${document.getElementById('totalCostWithVat').textContent} บาท`]);
                
                // สรุป VAT
                ws_data.push([]);
                ws_data.push([`ราาคาขาย`]);
                ws_data.push(['รวมราคาขาย:', `${document.getElementById('totalPriceSummary').textContent} บาท`]);
                ws_data.push(['VAT ราคาขาย:', `${document.getElementById('vatPrice').textContent} บาท`]);
                ws_data.push(['รวมราคาขาย + VAT:', `${document.getElementById('totalPriceWithVat').textContent} บาท`]);
                
                // สรุปรวม VAT
                ws_data.push([]);
                ws_data.push(['กำไรสุทธิ']);
                ws_data.push(['กำไรรวม:', `${document.getElementById('totalProfitSummary').textContent} บาท`]);
                ws_data.push(['VAT ส่วนต่าง:', `${document.getElementById('vatDifference').textContent} บาท`]);
                ws_data.push(['กำไรสุทธิ + VAT:', `${document.getElementById('totalProfitWithVat').textContent} บาท`]);

                // สร้างไฟล์ Excel
                const ws = XLSX.utils.aoa_to_sheet(ws_data);
                XLSX.utils.book_append_sheet(wb, ws, "ใบเสนอราคา");
                XLSX.writeFile(wb, `ใบเสนอราคา_${new Date().toISOString().slice(0,10)}.xlsx`);
            });
        });
        
    </script>
</body>
</html>
