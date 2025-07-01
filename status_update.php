<?php
session_start();
if (!isset($_SESSION['technician_id'])) {
    header("Location: login.php");
    exit;
}

$technician_id = $_SESSION['technician_id'];

require_once 'db_config.php';

$sql = "SELECT * FROM repair_requests WHERE technician_id = ? AND status IN ('in_progress', 'accepted')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();
$repairs = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>ติดตามสถานะงานซ่อม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: #f5f5f5;
            margin: 0;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #ffffff;
            position: fixed;
            overflow: hidden;
            border-right: 1px solid #e5e7eb;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            padding-top: 20px;
            z-index: 100;
        }
        .sidebar h4 {
            font-size: 20px;
            color: #374151;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #374151;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 12px;
            transition: background-color 0.2s ease;
            font-weight: 500;
        }
        .sidebar a:hover {
            background-color: #e0f2fe;
        }
        .sidebar a i {
            font-size: 18px;
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }
        .main {
            margin-left: 240px;
            padding: 2rem;
            min-height: 100vh;
        }
        .card-box {
            background: white;
            border-left: 5px solid #0d6efd;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }
        /* ใช้ flexbox ให้ข้อความชิดซ้าย รูปภาพชิดขวา */
        .card-box.d-flex {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }
        .card-content {
            flex: 1;
        }
        .card-box img {
            max-width: 200px;
            height: auto;
            border-radius: 8px;
            object-fit: contain;
            margin-left: 20px;
        }
        h1, h3 {
            color: #0d6efd;
        }
        label {
            font-weight: 600;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        h1, h3 {
        color: #000000; /* เปลี่ยนหัวข้อเป็นสีดำ */
        }
        .card-box {
            background: white;
            border-left: 5px solid #0d6efd; /* ขอบซ้ายยังคงสีฟ้า */
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            padding: 1.5rem;
        }

    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h4>ระบบแจ้งซ่อม</h4>
    <a href="c.php"><i class="fas fa-home"></i> หน้าหลัก</a>
    <a href="repair_list.php"><i class="fas fa-clipboard-list"></i> ดูงานแจ้งซ่อม</a>
    <a href="status_update.php"><i class="fas fa-edit"></i> อัปเดตสถานะงาน</a>
    <a href="review.php"><i class="fas fa-star"></i> คะแนน/รีวิว</a>
    <a href="inbox_technician.php"><i class="fas fa-comments"></i> ติดต่อ</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
</div>

<!-- Main content -->
<div class="main">
    <h1 class="mb-4">งานที่รับซ่อมของฉัน</h1>

    <?php if (count($repairs) === 0): ?>
        <p>ยังไม่มีงานที่รับไว้ในขณะนี้</p>
    <?php else: ?>
        <?php foreach ($repairs as $repair): ?>
            <div class="card-box d-flex">
                <div class="card-content">
                    <h3>งานหมายเลข: <?= htmlspecialchars($repair['id']) ?></h3>
                    <p><strong>รายละเอียดปัญหา:</strong> <?= htmlspecialchars($repair['problem_description']) ?></p>
                    <p><strong>สถานะงาน:</strong> <?= htmlspecialchars($repair['status']) ?></p>

                    <form action="update_repair_status.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="repair_id" value="<?= $repair['id'] ?>" />

                        <label for="status_<?= $repair['id'] ?>">อัปเดตสถานะงาน:</label>
                        <select name="status" id="status_<?= $repair['id'] ?>" class="form-select mb-3" required onchange="toggleFields(<?= $repair['id'] ?>)">
                            <option value="completed" <?= $repair['status'] == 'completed' ? 'selected' : '' ?>>ซ่อมเสร็จแล้ว</option>
                            <option value="cancelled" <?= $repair['status'] == 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                        </select>

                        <!-- ช่องเหตุผลการยกเลิก -->
                        <div id="cancelReasonDiv_<?= $repair['id'] ?>" style="display: none;">
                            <label for="cancel_reason_<?= $repair['id'] ?>" class="text-danger">เหตุผลการยกเลิก:</label>
                            <textarea name="cancel_reason" id="cancel_reason_<?= $repair['id'] ?>" rows="3" class="form-control mb-3"></textarea>
                        </div>

                        <!-- ฟิลด์สำหรับสถานะซ่อมเสร็จแล้ว -->
                        <div id="progressFields_<?= $repair['id'] ?>" style="display: block;">
                            <label for="message_<?= $repair['id'] ?>">ข้อความแจ้งความคืบหน้า:</label>
                            <textarea name="message" id="message_<?= $repair['id'] ?>" rows="4" class="form-control mb-3"></textarea>

                            <label for="image_<?= $repair['id'] ?>">อัปโหลดรูปภาพประกอบ (ถ้ามี):</label>
                            <input type="file" name="image" id="image_<?= $repair['id'] ?>" accept="image/*" class="form-control mb-3" />
                        </div>

                        <button type="submit" class="btn btn-primary">อัปเดตสถานะงาน</button>
                    </form>
                </div>

                <?php if (!empty($repair['repair_image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($repair['repair_image']) ?>" alt="รูปภาพงานซ่อม" />
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
<script>
function toggleFields(id) {
    const status = document.getElementById('status_' + id).value;
    const cancelDiv = document.getElementById('cancelReasonDiv_' + id);
    const progressFields = document.getElementById('progressFields_' + id);

    if (status === 'cancelled') {
        cancelDiv.style.display = 'block';
        progressFields.style.display = 'none';
    } else {
        cancelDiv.style.display = 'none';
        progressFields.style.display = 'block';
    }
}

// ล้างข้อความ textarea ถ้ามี success=1 ใน URL
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        document.querySelectorAll('textarea[name="message"]').forEach(el => el.value = '');
        document.querySelectorAll('textarea[name="cancel_reason"]').forEach(el => el.value = '');

        // ลบพารามิเตอร์ success ออกจาก URL
        urlParams.delete('success');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
    }
});
</script>
</html>