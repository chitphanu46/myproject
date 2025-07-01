<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['technician_id'])) {
    header("Location: login.php");
    exit;
}

$technician_id = $_SESSION['technician_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repair_id = $_POST['repair_id'] ?? null;
    $status = $_POST['status'] ?? '';
    $cancel_reason = trim($_POST['cancel_reason'] ?? '');

    if (!$repair_id || !$status) {
        header("Location: status_update.php?error=missing");
        exit;
    }

    if ($status === 'cancelled') {
        // ตรวจสอบว่ากรอกเหตุผลหรือไม่
        if (empty($cancel_reason)) {
            header("Location: status_update.php?error=empty_reason");
            exit;
        }

        // ✅ วางตรงนี้เลย
        $sql = "UPDATE repair_requests SET status=?, cancel_reason=? WHERE id=? AND technician_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $status, $cancel_reason, $repair_id, $technician_id);
    }
    elseif ($status === 'completed') {
        $completion_image = null;

        if (!empty($_FILES['image']['name'])) {
            $completion_image = time() . '_' . basename($_FILES['image']['name']);
            $target = 'uploads/' . $completion_image;
            move_uploaded_file($_FILES['image']['tmp_name'], $target);
        }

        $sql = "UPDATE repair_requests SET status=?, completion_image=? WHERE id=? AND technician_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $status, $completion_image, $repair_id, $technician_id);
    } else {
        header("Location: status_update.php?error=invalid_status");
        exit;
    }

    if ($stmt->execute()) {
        header("Location: status_update.php?success=1");
    } else {
        header("Location: status_update.php?error=update_fail");
    }

    $stmt->close();
    $conn->close();
}
?>
