<?php
date_default_timezone_set('Asia/Bangkok');
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['technician_id'])) {
    echo "<p class='text-danger text-center'>กรุณาเข้าสู่ระบบก่อน</p>";
    exit();
}

$technician_id = $_SESSION['technician_id'];

$sql = "SELECT r.id, r.problem_description, r.created_at, r.user_name, r.repair_image,
               u.phone, u.address
        FROM repair_requests r 
        JOIN users u ON r.user_id = u.id
        WHERE r.technician_id = ? AND r.status = 'pending'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการแจ้งซ่อม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
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
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #374151;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 12px;
            transition: background-color 0.2s ease;
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
        }
        .repair-item {
            background: white;
            border-left: 5px solid #0d6efd;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            font-size: 18px;
        }
        .img-fluid {
            max-width: 200px;
            height: auto;
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

<!-- Main Content -->
<div class="main">
    <h3 class="mb-4">รายการแจ้งซ่อมใหม่</h3>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card mb-4 repair-item p-3" data-repair-id="<?= $row['id'] ?>">
                <div class="card-header bg-transparent border-bottom-0 fw-bold">
                    หมายเลขแจ้งซ่อม: <?= $row['id'] ?>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between flex-wrap">
                        <div class="flex-grow-1 pe-3">
                            <p><strong>ชื่อผู้แจ้งซ่อม:</strong> <?= $row['user_name'] ?></p>
                            <p><strong>รายละเอียด:</strong> <?= $row['problem_description'] ?></p>
                            <p><strong>เวลาส่งแจ้ง:</strong> <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></p>
                            <p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($row['phone']) ?></p>
                            <p><strong>ที่อยู่:</strong> <?= htmlspecialchars($row['address']) ?></p>

                            <form action="javascript:void(0);" class="accept-repair-form mt-3">
                                <input type="hidden" name="repair_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-success">รับงาน</button>
                                <button type="button" class="btn btn-danger btn-cancel">ยกเลิก</button>

                                <div class="status-buttons mt-3" style="display:none;">
                                    <div class="cancel-reason mt-2" style="display:none;">
                                        <input type="text" class="form-control mb-2" placeholder="ระบุเหตุผลในการยกเลิก">
                                        <button class="btn btn-warning btn-send-cancel">ส่งเหตุผล</button>
                                    </div>
                                </div>
                            </form>
                            <div class="message-box mt-2" style="display:none;"></div>
                        </div>

                        <?php if (!empty($row['repair_image'])): ?>
                            <div class="text-end">
                                <img src="uploads/<?= htmlspecialchars($row['repair_image']); ?>" 
                                    alt="Repair Image" 
                                    class="img-fluid rounded mt-2">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="no-repair text-center mt-5">ไม่มีการแจ้งซ่อมใหม่</p>
    <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.accept-repair-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const repairId = form.find('input[name="repair_id"]').val();
        const parentCard = form.closest('.repair-item');
        const messageBox = form.next('.message-box');

        $.post('accept_repair.php', { repair_id: repairId }, function(response) {
            if (response.status === 'success') {
                parentCard.slideUp('fast', function() {
                    $(this).remove();
                    if ($('.repair-item').length === 0) {
                        $('.no-repair').show();
                }
            });
    messageBox.html('<div class="alert alert-success">รับงานเรียบร้อยแล้ว</div>').show();
}
            else {
                messageBox.html('<div class="alert alert-danger">' + response.message + '</div>').show();
            }
        }, 'json');
    });

    $('.btn-cancel').on('click', function() {
        const form = $(this).closest('.accept-repair-form');
        form.find('.status-buttons').slideDown();
        form.find('.cancel-reason').slideDown();
    });

    $('.btn-send-cancel').on('click', function() {
        console.log("คลิกส่งเหตุผลแล้ว"); // debug
        const form = $(this).closest('.accept-repair-form');
        const reasonInput = form.find('input[type="text"]');
        const reason = reasonInput.val().trim();
        const repairId = form.find('input[name="repair_id"]').val();
        const parentCard = form.closest('.repair-item');
        const messageBox = form.next('.message-box');

        if (reason === '') {
            alert('กรุณาระบุเหตุผลในการยกเลิก');
            return;
        }

        $.post('cancel_repair.php', { repair_id: repairId, reason: reason }, function(response) {
            if (response.status === 'success') {
                parentCard.slideUp('fast', function() {
                    $(this).remove();
                    if ($('.repair-item').length === 0) {
                        $('.no-repair').show();
                    }
                });
            } else {
                messageBox.html('<div class="alert alert-danger">' + response.message + '</div>').show();
            }
        }, 'json');
    });
});
</script>
</body>
</html>