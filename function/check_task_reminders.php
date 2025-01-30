<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../function/functions.php';

$result = checkTaskReminders($conn);
echo json_encode($result);

function checkTaskReminders($conn) {
    try {
        // Get current date in Y-m-d format
        $currentDate = date('Y-m-d');
        
        // Query to get tasks where reminder_date matches current date and start_date hasn't passed
        $sql = "SELECT t.*, tg.user_id, u.name,
                DATEDIFF(t.start_date, CURDATE()) as days_until_start 
                FROM task t
                JOIN task_group tg ON t.id_task = tg.task_id
                JOIN users u ON tg.user_id = u.id
                WHERE DATE_ADD(t.start_date, INTERVAL -t.reminder_date DAY) = ? 
                AND DATE(t.start_date) >= ?";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $currentDate, $currentDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $tasks = $result->fetch_all(MYSQLI_ASSOC);
        
        foreach ($tasks as $task) {
            // Check if notification already exists
            $check_sql = "SELECT 1 FROM notifications 
                         WHERE task_id = ? AND id_user = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ii", $task['id_task'], $task['user_id']);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows === 0) {
                // Create notification
                $message = "หัวข้องาน: " . $task['name_task'] . "\nเพิ่มโดย: " . $task['name'];
                
                $insert_sql = "INSERT INTO notifications 
                              (id_user, task_id, message, created_at, is_read) 
                              VALUES (?, ?, ?, NOW(), 0)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bind_param("iis", 
                    $task['user_id'], 
                    $task['id_task'], 
                    $message
                );
                $insert_stmt->execute();
            }
        }
        
        // Remove expired notifications
        $delete_sql = "DELETE FROM notifications 
                      WHERE task_id IN (
                          SELECT id_task 
                          FROM task 
                          WHERE start_date < CURDATE()
                      )";
        $conn->query($delete_sql);
        
        // Get all active task notifications
        $active_tasks_sql = "SELECT n.*, t.name_task, t.start_date,
                           DATEDIFF(t.start_date, CURDATE()) as days_until_start
                           FROM notifications n
                           JOIN task t ON n.task_id = t.id_task
                           WHERE n.is_read = 0
                           AND t.start_date >= CURDATE()";
        $active_tasks_result = $conn->query($active_tasks_sql);
        $active_tasks = $active_tasks_result->fetch_all(MYSQLI_ASSOC);

        return [
            'success' => true,
            'tasks' => $active_tasks
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

?>