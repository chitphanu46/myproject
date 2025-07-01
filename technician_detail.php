<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "home";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt_user = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($user = $result_user->fetch_assoc()) {
        $_SESSION['full_name'] = $user['full_name'];
    }
}

$sql = "SELECT * FROM technicians";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Start Repair</title>

    <!-- Google Fonts Kanit -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
            padding: 20px 10px;
        }

        .tech-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.1);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            padding: 20px;
        }

        .tech-card img {
            width: 260px;
            height: 260px;
            object-fit: cover;
            border-radius: 12px;
            margin-right: 30px;
            flex-shrink: 0;
        }

        .tech-info {
            flex-grow: 1;
        }

        .tech-info h5 {
            font-weight: 600;
            font-size: 1.6rem;
            margin-bottom: 12px;
        }

        .tech-info p {
            margin: 4px 0;
            font-size: 1rem;
        }

        .repair-form textarea {
            resize: vertical;
        }

        .repair-form label {
            font-weight: 500;
            margin-bottom: 6px;
            display: block;
        }

        .btn-submit {
            background: linear-gradient(145deg,rgb(27, 115, 209),rgb(54, 142, 236));
            border: none;
            padding: 10px 22px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 6px;
            cursor: pointer;
            color: white;
            transition: background 0.3s ease;
        }

        .btn-submit:hover {
            background: linear-gradient(145deg, #0056b3, #003b7b);
        }
    </style>
</head>

<body>

    <div class="container">
        <h2 class="mb-4">เลือกช่างและแจ้งซ่อม</h2>

        <?php if ($result && $result->num_rows > 0) : ?>
            <?php while ($tech = $result->fetch_assoc()) : ?>
                <div class="tech-card">
                    <img src="<?= htmlspecialchars($tech['profile_image']) ?>" alt="รูปช่าง <?= htmlspecialchars($tech['name']) ?>" />
                    <div class="tech-info">
                        <h5><?= htmlspecialchars($tech['name']) ?></h5>
                        <p><strong>ประเภท:</strong> <?= htmlspecialchars($tech['technician_type']) ?></p>
                        <p><strong>ความเชี่ยวชาญ:</strong> <?= htmlspecialchars($tech['specialization']) ?></p>
                        <p><strong>โทร:</strong> <?= htmlspecialchars($tech['phone_number']) ?></p>
                        <p><strong>ที่อยู่:</strong> <?= htmlspecialchars($tech['address']) ?></p>

                        <form class="repair-form mt-3" action="submituser_repair_request.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id) ?>" />
                            <input type="hidden" name="technician_id" value="<?= htmlspecialchars($tech['id']) ?>" />
                            
                            <textarea id="problem_description_<?= $tech['id'] ?>" name="problem_description" class="form-control mb-3" rows="3" required placeholder="โปรดระบุปัญหาที่พบ..."></textarea>

                            <label for="repair_image_<?= $tech['id'] ?>">แนบรูปภาพ:</label>
                            <input type="file" id="repair_image_<?= $tech['id'] ?>" name="repair_image" class="form-control mb-3" accept="image/*" />

                            <button type="submit" class="btn-submit">แจ้งซ่อม</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <p>ไม่พบข้อมูลช่าง</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
