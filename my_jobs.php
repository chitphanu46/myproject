<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['technician_id'])) {
    echo "กรุณาเข้าสู่ระบบก่อน";
    exit();
}

$technician_id = $_SESSION['technician_id'];

$sql = "SELECT r.id, r.problem_description, r.created_at, r.user_name, r.repair_image, r.status,
               u.phone, u.address
        FROM repair_requests r
        JOIN users u ON r.user_id = u.id
        WHERE r.technician_id = ? AND r.status != 'pending'
        ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>งานของฉัน - อัปเดตสถานะ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container my-4">
    <h2 class="mb-4">งานของฉัน (สถานะงานที่ได้รับแล้ว)</h2>

    <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="card mb-3" data-repair-id="<?= $row['id'] ?>">
                <div class="card-header">
                    หมายเลขแจ้งซ่อม: <?= $row['id'] ?> - สถานะ: <strong><?= $row['status'] ?></strong>
                </div>
                <div class="card-body">
                    <p><strong>ชื่อผู้แจ้งซ่อม:</strong> <?= htmlspecialchars($row['user_name']) ?></p>
                    <p><strong>รายละเอียด:</strong> <?= htmlspecialchars($row['problem_description']) ?></p>
                    <p><strong>เวลาส่งแจ้ง:</strong> <?= $row['created_at'] ?></p>
                    <p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($row['phone']) ?></p>
                    <p><strong>ที่อยู่:</strong> <?= htmlspecialchars($row['address']) ?></p>

                    <?php if (!empty($row['repair_image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($row['repair_image']) ?>" alt="ภาพแจ้งซ่อม" style="max-width: 200px;" />
                    <?php endif; ?>

                    <div class="mt-3">
                        <?php if ($row['status'] != 'completed' && $row['status'] != 'cancelled'): ?>
                        <button class="btn btn-primary btn-start me-2">🛠 เริ่มงาน</button>
                        <button class="btn btn-success btn-complete me-2">✅ เสร็จแล้ว</button>
                        <button class="btn btn-danger btn-cancel">🚫 ยกเลิก</button>

                        <div class="cancel-reason mt-2" style="display:none;">
                            <textarea class="form-control mb-2" rows="2" placeholder="ระบุเหตุผลการยกเลิก"></textarea>
                            <button class="btn btn-warning btn-submit-cancel">ส่งเหตุผล</button>
                        </div>
                        <?php else: ?>
                            <p><em>งานนี้ปิดเรียบร้อยแล้ว</em></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>ยังไม่มีงานที่ได้รับหรือดำเนินการ</p>
    <?php endif; ?>
</div>

<script>
function sendStatusUpdate(repairId, status, reason = '') {
    $.ajax({
        url: 'update_status.php',
        method: 'POST',
        data: { repair_id: repairId, status: status, reason: reason },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert(response.message);
                if (status === 'completed' || status === 'cancelled') {
                    $('div.card[data-repair-id="'+repairId+'"]').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    location.reload();  // รีเฟรชหน้าให้แสดงสถานะล่าสุด
                }
            } else {
                alert(response.message);
            }
        },
        error: function() {
            alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
        }
    });
}

$(document).on('click', '.btn-start', function() {
    var repairId = $(this).closest('.card').data('repair-id');
    sendStatusUpdate(repairId, 'in_progress');
});

$(document).on('click', '.btn-complete', function() {
    var repairId = $(this).closest('.card').data('repair-id');
    sendStatusUpdate(repairId, 'completed');
});

$(document).on('click', '.btn-cancel', function() {
    var card = $(this).closest('.card');
    card.find('.cancel-reason').slideDown();
});

$(document).on('click', '.btn-submit-cancel', function() {
    var card = $(this).closest('.card');
    var repairId = card.data('repair-id');
    var reason = card.find('textarea').val().trim();

    if (!reason) {
        alert('กรุณาระบุเหตุผลการยกเลิก');
        return;
    }

    sendStatusUpdate(repairId, 'cancelled', reason);
});
</script>
</body>
</html>
