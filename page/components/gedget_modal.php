
<!-- Modal สำหรับสร้างและแก้ไข Gedget -->
<div id="gedgetModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <h3 id="modalTitle" class="text-lg leading-6 font-medium text-gray-900"></h3>
            <form id="gedgetForm" class="mt-2">
                <input type="hidden" id="id_gedget" name="id_gedget">
                <input type="hidden" id="id_bill" name="id_bill" value="<?php echo $id_bill; ?>">
                <div class="mb-4">
                    <label for="name_gedget" class="block text-sm font-medium text-gray-700">ชื่อ Gedget</label>
                    <input type="text" id="name_gedget" name="name_gedget" class="mt-1 p-2 w-full border rounded-md">
                </div>
                <div class="mb-4">
                    <label for="quantity_gedget" class="block text-sm font-medium text-gray-700">จำนวน</label>
                    <input type="number" id="quantity_gedget" name="quantity_gedget" class="mt-1 p-2 w-full border rounded-md">
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeGedgetModal()" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">ยกเลิก</button>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>