<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['technician_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit();
}

$technician_id = $_SESSION['technician_id'];
$repair_id = $_POST['repair_id'] ?? null;
$status = $_POST['status'] ?? null;
$reason = $_POST['reason'] ?? '';

if (!$repair_id || !$status) {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit();
}

// ตรวจสอบว่างานนี้เป็นของช่างที่ล็อกอินอยู่จริงไหม
$sql_check = "SELECT * FROM repair_requests WHERE id = ? AND technician_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $repair_id, $technician_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบงานซ่อมที่คุณสามารถแก้ไขได้']);
    exit();
}

// อัปเดตสถานะ พร้อมเก็บเหตุผลถ้ายกเลิก
if ($status === 'cancelled') {
    $sql_update = "UPDATE repair_requests SET status = ?, cancel_reason = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssi", $status, $reason, $repair_id);
} else {
    $sql_update = "UPDATE repair_requests SET status = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $status, $repair_id);
}

if ($stmt_update->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะเรียบร้อยแล้ว']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปเดตสถานะได้']);
}
?>
