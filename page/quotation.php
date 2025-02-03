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

                <!-- หมวดต้นทุน -->
                <div id="categories">
                    <div class="category-group mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center gap-4 w-full">
                                <input type="text" class="category-name border p-2 rounded w-1/3" placeholder="ชื่อหมวดต้นทุน" required>
                                <button type="button" class="add-item bg-blue-500 text-white px-4 py-2 rounded">เพิ่มรายการ</button>
                                <button type="button" class="delete-category bg-red-500 text-white px-4 py-2 rounded ml-auto">ลบหมวดหมู่</button>
                            </div>
                        </div>
                        
                        <!-- ตารางรายการต้นทุน -->
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse border">
                                <thead>
                                    <tr class="bg-gray-100">
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
                                <tbody class="items">
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 font-bold">
                                        <td colspan="5" class="border p-2 text-right">รวมทั้งหมด:</td>
                                        <td class="border p-2 category-total-cost">0</td>
                                        <td class="border p-2 category-total-price">0</td>
                                        <td class="border p-2 category-total-profit">0</td>
                                        <td class="border p-2 category-total-profit-percent">0</td> 
                                        <td class="border p-2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <button type="button" id="addCategory" class="bg-green-500 text-white px-4 py-2 rounded mt-4">เพิ่มหมวดต้นทุน</button>
                
                <!-- สรุปผลทั้งหมด -->
                <div class="summary mt-8 p-4 bg-gray-50 rounded">
                    <h2 class="text-xl font-bold mb-4">สรุปผลทั้งหมด</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded shadow">
                            <h3 class="font-bold mb-2 text-gray-700">ก่อน VAT</h3>
                            <p>รวมต้นทุนขาย: <span id="totalCost">0</span> บาท</p>
                            <p>รวมราคาขาย: <span id="totalPrice">0</span> บาท</p>
                            <p class="font-bold text-green-600">กำไรรวม: <span id="totalProfit">0</span> บาท</p>
                        </div>
                        <div class="bg-white p-4 rounded shadow">
                            <h3 class="font-bold mb-2 text-gray-700">VAT</h3>
                            <p>VAT ต้นทุน: <span id="vatCost">0</span> บาท</p>
                            <p>VAT ราคาขาย: <span id="vatPrice">0</span> บาท</p>
                            <p class="font-bold text-red-500">VAT ส่วนต่าง: <span id="vatDifference">0</span> บาท</p>
                        </div>
                        <div class="bg-white p-4 rounded shadow">
                            <h3 class="font-bold mb-2 text-gray-700">รวมทั้งหมด (รวม VAT)</h3>
                            <p>รวมต้นทุนขาย + VAT: <span id="totalCostWithVat">0</span> บาท</p>
                            <p>รวมราคาขาย + VAT: <span id="totalPriceWithVat">0</span> บาท</p>
                            <p class="font-bold text-blue-600">กำไรรวม + VAT: <span id="totalProfitWithVat">0</span> บาท</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // เพิ่มหมวดต้นทุน
            document.getElementById('addCategory').addEventListener('click', function() {
                const categoriesDiv = document.getElementById('categories');
                const categoryTemplate = document.querySelector('.category-group').cloneNode(true);
                categoryTemplate.querySelector('.category-name').value = '';
                categoryTemplate.querySelector('.items').innerHTML = '';
                addItemRow(categoryTemplate.querySelector('.items'));
                setupEventListeners(categoryTemplate);
                categoriesDiv.appendChild(categoryTemplate);
                if (document.querySelectorAll('.category-group').length === 1) {
                    // Don't show delete button for the first category
                    categoryTemplate.querySelector('.delete-category').style.display = 'none';
                } else {
                    categoryTemplate.querySelector('.delete-category').style.display = 'block';
                }
                
            });

            // ตั้งค่า Event Listeners สำหรับปุ่มต่างๆ
            function setupEventListeners(element) {
                element.querySelector('.add-item').addEventListener('click', function() {
                    addItemRow(element.querySelector('.items'));
                });
                element.querySelector('.delete-category').addEventListener('click', function() {
                    if (document.querySelectorAll('.category-group').length > 1) {
                        element.remove();
                        calculateTotals();
                    }
                });
              
            }
            document.querySelector('.delete-category').style.display = 'none';
            document.getElementById('vatRate').addEventListener('input', calculateTotals);

            function addItemRow(itemsContainer) {
                const tr = document.createElement('tr');
                tr.className = 'item-row';
                tr.innerHTML = `
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

            function calculateTotals() {
                let totalCost = 0;
                let totalPrice = 0;
                let totalDifference = 0;

                document.querySelectorAll('.category-group').forEach(category => {
                    let categoryTotalCost = 0;
                    let categoryTotalPrice = 0;
                    let categoryTotalDifference = 0;

                    category.querySelectorAll('.item-row').forEach(row => {
                        const cost = parseFloat(row.querySelector('.cost').value) || 0;
                        const price = parseFloat(row.querySelector('.price').value) || 0;
                        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
                        const peopleCount = parseInt(row.querySelector('.people-count').value) || 0;

                        let itemTotalCost, itemTotalPrice;

                        if (peopleCount === 0) {
                            itemTotalCost = cost * quantity;
                            itemTotalPrice = price * quantity;
                        } else {
                            itemTotalCost = cost * quantity * peopleCount;
                            itemTotalPrice = price * quantity * peopleCount;
                        }

                        const itemDifference = itemTotalPrice - itemTotalCost;

                        row.querySelector('.total-cost').textContent = itemTotalCost.toFixed(2);
                        row.querySelector('.total-price').textContent = itemTotalPrice.toFixed(2);
                        row.querySelector('.difference').textContent = itemDifference.toFixed(2);

                        categoryTotalCost += itemTotalCost;
                        categoryTotalPrice += itemTotalPrice;
                        categoryTotalDifference += itemDifference;
                    });

                    // อัพเดทผลรวมในแถวสุดท้ายของตาราง
                    category.querySelector('.category-total-cost').textContent = categoryTotalCost.toFixed(2);
                    category.querySelector('.category-total-price').textContent = categoryTotalPrice.toFixed(2);
                    category.querySelector('.category-total-profit').textContent = categoryTotalDifference.toFixed(2);

                    // คำนวณเปอร์เซ็นต์กำไร
                    let profitPercent = 0;
                    if (categoryTotalCost > 0) {
                        profitPercent = (categoryTotalDifference * 100) / categoryTotalCost;
                    }
                    category.querySelector('.category-total-profit-percent').textContent = profitPercent.toFixed(2);

                    totalCost += categoryTotalCost;
                    totalPrice += categoryTotalPrice;
                    totalDifference += categoryTotalDifference;
                });

                const vatRate = parseFloat(document.getElementById('vatRate').value) || 0;
                const vatMultiplier = vatRate / 100;

                const vatCost = totalCost * vatMultiplier;
                const vatPrice = totalPrice * vatMultiplier;
                const vatDifference = vatPrice - vatCost;

                const totalCostWithVat = totalCost + vatCost;
                const totalPriceWithVat = totalPrice + vatPrice;
                const totalProfitWithVat = totalPriceWithVat - totalCostWithVat;

                // Update display - Before VAT
                document.getElementById('totalCost').textContent = totalCost.toFixed(2);
                document.getElementById('totalPrice').textContent = totalPrice.toFixed(2);
                document.getElementById('totalProfit').textContent = totalDifference.toFixed(2);

                // Update display - VAT amounts
                document.getElementById('vatCost').textContent = vatCost.toFixed(2);
                document.getElementById('vatPrice').textContent = vatPrice.toFixed(2);
                document.getElementById('vatDifference').textContent = vatDifference.toFixed(2);

                // Update display - After VAT
                document.getElementById('totalCostWithVat').textContent = totalCostWithVat.toFixed(2);
                document.getElementById('totalPriceWithVat').textContent = totalPriceWithVat.toFixed(2);
                document.getElementById('totalProfitWithVat').textContent = totalProfitWithVat.toFixed(2);
            }


            setupEventListeners(document.querySelector('.category-group'));
        });
    </script>
</body>
</html>
