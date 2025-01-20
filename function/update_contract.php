<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_bill = $_POST['id_bill'];
    $contract_action = $_POST['contract_action'];
    $contract_duration = isset($_POST['contract_duration']) ? intval($_POST['contract_duration']) : 0;

    // ดึงข้อมูลบิลปัจจุบัน
    $sql = "SELECT end_date, contact_count FROM bill_customer WHERE id_bill = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_bill);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill = $result->fetch_assoc();

    if ($contract_action === 'ต่อสัญญา') {
        // คำนวณ end_date ใหม่
        $new_end_date = date('Y-m-d', strtotime($bill['end_date'] . " + $contract_duration days"));
        $contact_status = 'ต่อสัญญา';
        $contact_count = $bill['contact_count'] + 1; // เพิ่ม contact_count ขึ้น 1
    } else {
        // ไม่ต้องคำนวณ end_date ใหม่
        $new_end_date = $bill['end_date'];
        $contact_status = 'ยกเลิกสัญญา';
        $contact_count = $bill['contact_count']; // ไม่เพิ่ม contact_count
    }

    // อัปเดตข้อมูลบิล
    $sql = "UPDATE bill_customer SET end_date = ?, contact_status = ? , date_count = ?, contact_count = ? WHERE id_bill = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiii", $new_end_date, $contact_status, $contract_duration, $contact_count, $id_bill);
    $stmt->execute();

    echo "<script>alert('อัปเดตสัญญาเรียบร้อยแล้ว'); window.location.href = '../page/bill.php';</script>";
}
?>