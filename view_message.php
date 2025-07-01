<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if (isset($_GET['id'])) {
    $message_id = $_GET['id'];
    
    // ดึงข้อความที่เลือกจากฐานข้อมูล
    $sql = "SELECT * FROM inbox WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $message_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $message = $result->fetch_assoc();
    } else {
        echo "ไม่พบข้อความนี้";
        exit();
    }

    // อัปเดตสถานะข้อความว่าอ่านแล้ว
    $update_sql = "UPDATE inbox SET is_read = 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $message_id);
    $update_stmt->execute();
} else {
    echo "ไม่มีข้อมูล";
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดข้อความ</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .container {
            max-width: 700px;
        }
        .card {
            border-radius: 12px;
        }
        h2 {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">รายละเอียดข้อความ</h2>
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-primary text-white">
                <strong>ข้อความจากช่าง</strong>
            </div>
            <div class="card-body">
                <p><?= htmlspecialchars($message['message']); ?></p>
                <p><small class="text-muted"><?= $message['created_at']; ?></small></p>
            </div>
        </div>
        <a href="inbox.php" class="btn btn-secondary">กลับไปที่กล่องจดหมาย</a>
    </div>
</body>
</html>
