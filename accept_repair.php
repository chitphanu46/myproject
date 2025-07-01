<?php  
session_start();
require_once 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['technician_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาเข้าสู่ระบบก่อน']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['repair_id'])) {
    $technician_id = $_SESSION['technician_id'];
    $repair_id = $_POST['repair_id'];

    $sql = "UPDATE repair_requests SET status = 'accepted', technician_id = ? WHERE id = ? AND status = 'pending'";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("ii", $technician_id, $repair_id);

    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error]);
        exit();
    }

    if ($stmt->affected_rows > 0) {
        $user_sql = "SELECT user_id FROM repair_requests WHERE id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("i", $repair_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();

        if ($user_row = $user_result->fetch_assoc()) {
            $user_id = $user_row['user_id'];
            $message = "งานซ่อมหมายเลข #$repair_id ถูกช่างรับงานแล้ว";

            $msg_sql = "INSERT INTO inbox (user_id, message, is_read) VALUES (?, ?, 0)";
            $msg_stmt = $conn->prepare($msg_sql);
            $msg_stmt->bind_param("is", $user_id, $message);
            $msg_stmt->execute();
        }

        echo json_encode(['status' => 'success', 'message' => 'รับงานสำเร็จ']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถรับงานนี้ได้ (อาจมีผู้รับไปแล้วหรือสถานะไม่ใช่ pending)']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'ข้อมูลไม่ถูกต้อง']);
}
?>
