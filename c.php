<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['technician_id'])) {
    echo "กรุณาเข้าสู่ระบบก่อน";
    exit();
}

$technician_id = $_SESSION['technician_id'];

// ดึงข้อมูลช่างจากตาราง technicians
$stmt_profile = $conn->prepare("SELECT name, email, technician_type, profile_image FROM technicians WHERE id = ?");
$stmt_profile->bind_param("i", $technician_id);
$stmt_profile->execute();
$profile_result = $stmt_profile->get_result()->fetch_assoc();

$sql_pending = "SELECT COUNT(*) as count FROM repair_requests WHERE technician_id = ? AND status = 'pending'";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->bind_param("i", $technician_id);
$stmt_pending->execute();
$pending_count = $stmt_pending->get_result()->fetch_assoc()['count'];

$sql_in_progress = "SELECT COUNT(*) as count FROM repair_requests WHERE technician_id = ? AND status IN ('accepted', 'in-progress')";
$stmt_in_progress = $conn->prepare($sql_in_progress);
$stmt_in_progress->bind_param("i", $technician_id);
$stmt_in_progress->execute();
$in_progress_count = $stmt_in_progress->get_result()->fetch_assoc()['count'];

$sql_total = "SELECT COUNT(*) as count FROM repair_requests WHERE technician_id = ?";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("i", $technician_id);
$stmt_total->execute();
$total_count = $stmt_total->get_result()->fetch_assoc()['count'];

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แดชบอร์ดช่าง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Kanit', sans-serif;
            background: #f5f5f5;
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
            padding-top: 1.5rem; /* ลดระยะห่างด้านบน */
        }

        .card-box {
            border-left: 5px solid #0d6efd;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            padding: 1.5rem;
            border-radius: 10px;
        }
        .card-box h5 {
            font-size: 18px;
            color: #333;
        }
        .card-box p {
            font-size: 32px;
            margin: 0;
        }
        .text-primary {
            color: #e74c3c !important;
        }
        .text-warning {
            color: #27ae60 !important;
        }
        .text-success {
            color: #2980b9 !important;
        }
        .profile-topbar {
        position: absolute;
        top: 1rem;
        right: 2rem;
        z-index: 2000;
        }
            .profile-container:hover .profile-hover-info {
            display: block;
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
   <div class="profile-topbar">
    <div class="profile-container position-relative" style="display: inline-block;">
        <?php
        $img_path = (!empty($profile_result['profile_image']) && file_exists($profile_result['profile_image']))
            ? $profile_result['profile_image']
            : 'uploads/default.png';
        ?>
            <img src="<?= htmlspecialchars($img_path) . '?v=' . time() ?>" alt="Profile"
                width="48" height="48"
                class="rounded-circle profile-image"
                style="cursor:pointer; object-fit: cover; border: 2px solid #ddd;">

        <!-- กล่องข้อมูลทั้งหมด -->
        <div class="profile-hover-box" style="display: none; position: absolute; top: 60px; right: 0; background: white; padding: 12px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); min-width: 220px; z-index: 999; border: 1px solid #ddd; text-align: left;">
            <strong><?= htmlspecialchars($profile_result['name']) ?></strong><br>
            <small><?= htmlspecialchars($profile_result['email']) ?></small><br>
            <span class="text-muted"><?= htmlspecialchars($profile_result['technician_type']) ?></span>

            <form action="upload_profile.php" method="POST" enctype="multipart/form-data" style="margin-top: 10px;">
                <input type="file" name="profile_image" id="profileUpload" onchange="this.form.submit()" hidden>
                <label for="profileUpload" class="btn btn-sm btn-outline-secondary">เปลี่ยนรูป</label>
            </form>
        </div>
    </div> <!-- ปิด .profile-container -->
   </div> <!-- ปิด .profile-topbar -->

    <h3 class="mb-2 mt-1">ยินดีต้อนรับ, ช่าง</h3>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card-box">
                <h5>งานรอรับ</h5>
                <p class="text-primary"><?= $pending_count ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-box">
                <h5>งานดำเนินการ</h5>
                <p class="text-warning"><?= $in_progress_count ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card-box">
                <h5>รวมงานทั้งหมด</h5>
                <p class="text-success"><?= $total_count ?></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const profileContainer = document.querySelector(".profile-container");
    const hoverBox = document.querySelector(".profile-hover-box");

    if (profileContainer && hoverBox) {
        // เมื่อเอาเม้าส์เข้า container หรือ hoverBox ให้แสดงกล่อง
        profileContainer.addEventListener("mouseenter", () => {
            hoverBox.style.display = "block";
        });
        hoverBox.addEventListener("mouseenter", () => {
            hoverBox.style.display = "block";
        });

        // เมื่อเม้าส์ออกจากทั้ง container และ hoverBox ให้ซ่อนกล่อง
        profileContainer.addEventListener("mouseleave", () => {
            // รอเวลาสั้น ๆ ก่อนซ่อนเผื่อเม้าส์เลื่อนไป hoverBox
            setTimeout(() => {
                if (!profileContainer.matches(':hover') && !hoverBox.matches(':hover')) {
                    hoverBox.style.display = "none";
                }
            }, 100);
        });

        hoverBox.addEventListener("mouseleave", () => {
            // รอเวลาสั้น ๆ ก่อนซ่อนเผื่อเม้าส์เลื่อนไป container
            setTimeout(() => {
                if (!profileContainer.matches(':hover') && !hoverBox.matches(':hover')) {
                    hoverBox.style.display = "none";
                }
            }, 100);
        });
    }
});

</script>
</body>
</html>