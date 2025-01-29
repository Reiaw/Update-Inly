<div id="taskModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">เพิ่มงานใหม่</h3>
            <form id="taskForm" method="POST" action="../function/create_task.php">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name_task">
                        ชื่องาน
                    </label>
                    <input type="text" name="name_task" id="name_task" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="detail_task">
                        รายละเอียด
                    </label>
                    <textarea name="detail_task" id="detail_task"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        rows="3"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="start_date">
                        วันที่เริ่ม
                    </label>
                    <input type="date" name="start_date" id="start_date" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="end_date">
                        วันที่สิ้นสุด
                    </label>
                    <input type="date" name="end_date" id="end_date" required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="reminder_date">
                        แจ้งเตือนก่อน (วัน)
                    </label>
                    <input type="number" name="reminder_date" id="reminder_date"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="assigned_users">
                        มอบหมายให้
                    </label>
                    <select name="assigned_users[]" id="assigned_users" multiple required
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <?php
                        $users = getAllUsers();
                        foreach ($users as $user) {
                            echo "<option value='" . $user['id'] . "'>" . htmlspecialchars($user['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="button" onclick="closeTaskModal()"
                        class="mr-2 px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">ยกเลิก</button>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTaskModal() {
    document.getElementById('taskModal').classList.remove('hidden');
}

function closeTaskModal() {
    document.getElementById('taskModal').classList.add('hidden');
}
</script>