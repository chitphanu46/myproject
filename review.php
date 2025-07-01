<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['technician_id'])) {
    echo "<p class='text-danger text-center'>กรุณาเข้าสู่ระบบก่อน</p>";
    exit();
}

$technician_id = $_SESSION['technician_id'];

// ดึงรีวิวทั้งหมด
$stmt = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, r.user_name
    FROM reviews r
    WHERE r.technician_id = ?
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
$rating_count = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
$total_rating = 0;
$total_reviews = 0;

while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
    $rating = (int)$row['rating'];
    if ($rating >= 1 && $rating <= 5) {
        $rating_count[$rating]++;
        $total_rating += $rating;
        $total_reviews++;
    }
}

$average_rating = $total_reviews ? round($total_rating / $total_reviews, 1) : 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>คะแนนและรีวิว</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .review-card {
            background: white;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .rating-stars {
            color: #f39c12;
            font-size: 20px;
        }
        .average-rating {
            font-size: 24px;
            color: #f39c12;
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
    <h3 class="mb-4">คะแนนและรีวิวจากผู้ใช้</h3>

    <div class="mb-4">
        <strong>คะแนนเฉลี่ย:</strong>
        <span class="average-rating">
            <?= str_repeat('★', floor($average_rating)) ?>
            <?= str_repeat('☆', 5 - floor($average_rating)) ?>
            (<?= $average_rating ?>/5)
        </span>
    </div>

    <div style="max-width: 600px; margin-bottom: 2rem;">
        <canvas id="ratingChart" style="width: 100%; height: 300px;"></canvas>
    </div>

    <?php foreach ($reviews as $row): ?>
        <div class="review-card">
            <h5><?= htmlspecialchars($row['user_name']) ?></h5>
            <div class="rating-stars">
                <?= str_repeat('★', $row['rating']) ?>
                <?= str_repeat('☆', 5 - $row['rating']) ?>
            </div>
            <p class="mb-1"><?= nl2br(htmlspecialchars($row['comment'])) ?></p>
            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></small>
        </div>
    <?php endforeach; ?>

    <?php if (count($reviews) === 0): ?>
        <p class="text-muted">ยังไม่มีรีวิว</p>
    <?php endif; ?>
</div>

<script>
const ctx = document.getElementById('ratingChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['1 ดาว', '2 ดาว', '3 ดาว', '4 ดาว', '5 ดาว'],
        datasets: [{
            label: 'จำนวนรีวิว',
            data: [
                <?= $rating_count[1] ?>,
                <?= $rating_count[2] ?>,
                <?= $rating_count[3] ?>,
                <?= $rating_count[4] ?>,
                <?= $rating_count[5] ?>
            ],
            backgroundColor: [
                '#e74c3c',
                '#e67e22',
                '#f1c40f',
                '#2ecc71',
                '#3498db'
            ],
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'จำนวนรีวิวในแต่ละระดับดาว'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});
</script>

</body>
</html>
