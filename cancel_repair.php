<?php
require_once 'db_config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repair_id = $_POST['repair_id'] ?? null;
    $reason = trim($_POST['cancel_reason'] ?? $_POST['reason'] ?? '');

    if (!$repair_id || $reason === '') {
        echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE repair_requests 
                            SET status = 'cancelled', cancel_reason = ? 
                            WHERE id = ?");
    $stmt->bind_param("si", $reason, $repair_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถยกเลิกงานได้']);
    }
}
?>
