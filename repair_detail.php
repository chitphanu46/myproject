<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ไม่พบข้อมูลแจ้งซ่อม");
}

$repair_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลแจ้งซ่อมพร้อมช่าง
$sql = "
    SELECT rr.*, t.name AS technician_name
    FROM repair_requests rr
    LEFT JOIN technicians t ON rr.technician_id = t.id
    WHERE rr.id = ? AND rr.user_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $repair_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("ไม่พบข้อมูลแจ้งซ่อมนี้ หรือคุณไม่มีสิทธิ์เข้าถึง");
}

$repair = $result->fetch_assoc();

function translateStatus($status) {
    switch ($status) {
        case 'pending': return 'รอดำเนินการ';
        case 'accepted': return 'กำลังดำเนินการ';
        case 'completed': return 'สำเร็จ';
        case 'cancelled': return 'ยกเลิก';
        default: return htmlspecialchars($status);
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>รายละเอียดแจ้งซ่อม #<?= htmlspecialchars($repair['id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="container py-4">
    <h2>รายละเอียดแจ้งซ่อมเลขที่: <?= htmlspecialchars($repair['id']) ?></h2>

    <dl class="row">
        <dt class="col-sm-3">ช่างผู้รับงาน</dt>
        <dd class="col-sm-9"><?= $repair['technician_name'] ? htmlspecialchars($repair['technician_name']) : '<em>ยังไม่ระบุ</em>' ?></dd>

        <dt class="col-sm-3">รายละเอียดปัญหา</dt>
        <dd class="col-sm-9"><?= nl2br(htmlspecialchars($repair['problem_description'])) ?></dd>

        <dt class="col-sm-3">ภาพปัญหา</dt>
        <dd class="col-sm-9">
            <?php if (!empty($repair['repair_image']) && file_exists(__DIR__ . '/uploads/' . $repair['repair_image'])): ?>
                <img src="uploads/<?= htmlspecialchars($repair['repair_image']) ?>" alt="ภาพปัญหา" style="max-width:300px;" />
            <?php else: ?>
                ไม่มีภาพ
            <?php endif; ?>
        </dd>

        <dt class="col-sm-3">สถานะ</dt>
        <dd class="col-sm-9"><?= translateStatus($repair['status']) ?></dd>

        <dt class="col-sm-3">ข้อความช่าง</dt>
        <dd class="col-sm-9"><?= nl2br(htmlspecialchars($repair['cancel_reason'] ?? '-')) ?></dd>

        <dt class="col-sm-3">รูปภาพงานซ่อมเสร็จ</dt>
        <dd class="col-sm-9">
            <?php if (!empty($repair['completion_image']) && file_exists(__DIR__ . '/uploads/' . $repair['completion_image'])): ?>
                <img src="uploads/<?= htmlspecialchars($repair['completion_image']) ?>" alt="ภาพงานซ่อมเสร็จ" style="max-width:300px;" />
            <?php else: ?>
                ไม่มีภาพ
            <?php endif; ?>
        </dd>

        <dt class="col-sm-3">วันที่แจ้ง</dt>
        <dd class="col-sm-9"><?= date("d M Y H:i", strtotime($repair['created_at'])) ?></dd>
    </dl>

    <a href="my_repairs.php" class="btn btn-secondary">กลับ</a>
</body>
</html>
