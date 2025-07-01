<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// ฟังก์ชันนับจำนวนการแจ้งซ่อมแต่ละสถานะ
function countStatus($conn, $user_id, $statusCondition) {
    $sql = "SELECT COUNT(*) AS count FROM repair_requests WHERE user_id = ? AND $statusCondition";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'];
}

// ดึงจำนวนแจ้งซ่อมแต่ละสถานะ
$waiting    = countStatus($conn, $user_id, "status = 'pending'");
$inprogress = countStatus($conn, $user_id, "status IN ('accepted', 'in-progress')");
$completed  = countStatus($conn, $user_id, "status = 'completed'");
$cancelled  = countStatus($conn, $user_id, "status = 'cancelled'");

// ดึงรายการแจ้งซ่อมพร้อม JOIN ตาราง technicians เพื่อดึงชื่อช่าง
$sql = "
    SELECT rr.*, t.name AS technician_name
    FROM repair_requests rr
    LEFT JOIN technicians t
    ON rr.technician_id = t.id
    WHERE rr.user_id = ?
    ORDER BY rr.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$repairs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เรื่องแจ้งซ่อมของฉัน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .status-label {
            font-weight: bold;
            font-size: 18px;
            color: #555;
        }
        .status-value {
            font-size: 30px;
            font-weight: bold;
            color: #007bff;
        }
        .repair-img {
            max-width: 100px;
            max-height: 100px;
            border-radius: 8px;
        }
    </style>
</head>
<body class="container py-5">
    <h2 class="mb-4">เรื่องแจ้งซ่อมของฉัน</h2>

    <!-- การ์ดสถานะ -->
    <div class="row mb-4 text-center">
        <div class="col-md-3"><div class="status-card"><div class="status-value"><?= $waiting ?></div><div class="status-label">รอดำเนินการ</div></div></div>
        <div class="col-md-3"><div class="status-card"><div class="status-value"><?= $inprogress ?></div><div class="status-label">กำลังดำเนินการ</div></div></div>
        <div class="col-md-3"><div class="status-card"><div class="status-value"><?= $completed ?></div><div class="status-label">เสร็จสิ้น</div></div></div>
        <div class="col-md-3"><div class="status-card"><div class="status-value"><?= $cancelled ?></div><div class="status-label">ยกเลิก</div></div></div>
    </div>

    <!-- ตารางรายการแจ้งซ่อม -->
    <table class="table table-bordered table-hover">
        <thead class="table-light">
            <tr>
                <th>เลขที่</th>
                <th>ช่างผู้รับงาน</th>
                <th>รายละเอียดปัญหา</th>
                <th>ภาพปัญหา</th>
                <th>สถานะ</th>
                <th>วันที่แจ้ง</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($repairs->num_rows > 0): ?>
                <?php while($row = $repairs->fetch_assoc()): ?>
                    <tr>
                        <td><a href="repair_detail.php?id=<?= $row['id'] ?>"><?= htmlspecialchars($row['id']) ?></a></td>
                        <td><?= $row['technician_name'] ? htmlspecialchars($row['technician_name']) : '<span class="text-muted">-</span>'; ?></td>
                        <td><?= nl2br(htmlspecialchars($row['problem_description'])) ?></td>
                        <td>
                            <?php if(!empty($row['repair_image']) && file_exists(__DIR__."/uploads/".$row['repair_image'])): ?>
                                <img src="uploads/<?= htmlspecialchars($row['repair_image']) ?>" class="repair-img" alt="image">
                            <?php else: ?>
                                ไม่มีภาพ
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $status = $row['status'];
                            switch ($status) {
                                case 'pending':
                                    $badge = 'warning';
                                    $statusText = 'รอดำเนินการ';
                                    break;
                                case 'in-progress':
                                case 'accepted':
                                    $badge = 'info';
                                    $statusText = 'กำลังดำเนินการ';
                                    break;
                                case 'completed':
                                    $badge = 'success';
                                    $statusText = 'เสร็จสิ้น';
                                    break;
                                case 'cancelled':
                                    $badge = 'secondary';
                                    $statusText = 'ยกเลิก';
                                    break;
                                default:
                                    $badge = 'light';
                                    $statusText = 'ไม่ทราบ';
                                    break;
                            }

                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= $statusText ?></span>
                        </td>
                        <td><?= date("d M Y H:i", strtotime($row['created_at'])) ?></td>
                        <td><a href="repair_detail.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">ดู</a></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center text-muted">ไม่มีการแจ้งซ่อม</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
