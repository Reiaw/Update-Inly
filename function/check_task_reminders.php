<?php
require_once '../config/config.php';
require_once '../function/functions.php';

function checkTaskReminders() {
    global $conn;
    
    // Get current date in Y-m-d format
    $currentDate = date('Y-m-d');
    
    // Query to get tasks where reminder_date matches current date and start_date hasn't passed
    $sql = "SELECT t.*, tg.user_id, u.name,
            DATEDIFF(t.start_date, CURDATE()) as days_until_start 
            FROM task t
            JOIN task_group tg ON t.id_task = tg.task_id
            JOIN users u ON tg.user_id = u.id
            WHERE DATE_ADD(t.start_date, INTERVAL -t.reminder_date DAY) = ? 
            AND DATE(t.start_date) >= ? 
            AND NOT EXISTS (
                SELECT 1 
                FROM notifications n 
                WHERE n.task_id = t.id_task 
                AND n.id_user = tg.user_id
            )";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $currentDate, $currentDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($task = $result->fetch_assoc()) {
        // Create notification for each assigned user if start_date hasn't passed
        $days_until_start = $task['days_until_start'];
        $message = "หัวข้องาน: " . $task['name_task'] . "\nเพิ่มโดย: " . $task['name'];
        
        if ($days_until_start >= 0) { // Only send notifications if the task has not started yet
            $insertSql = "INSERT INTO notifications (id_user, task_id, message, created_at, id_bill) 
                          VALUES (?, ?, ?, NOW(), NULL)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("iis", $task['user_id'], $task['id_task'], $message);
            $insertStmt->execute();
        }
    }
    
    // Remove expired notifications (where the task has already started)
    $deleteSql = "DELETE FROM notifications 
                  WHERE task_id IN (
                      SELECT t.id_task 
                      FROM task t
                      WHERE t.start_date < CURDATE() -- Only remove tasks that have already started (start_date is less than today)
                  )";
    $conn->query($deleteSql);
}

// This function should be called by a cron job daily
checkTaskReminders();
?>