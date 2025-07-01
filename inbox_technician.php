<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['technician_id'])) {
    header("Location: login.php");
    exit();
}

$technician_id = $_SESSION['technician_id'];

// ดึงข้อมูลโปรไฟล์ช่าง
$sqlTech = "SELECT profile_image FROM technicians WHERE id = ?";
$stmtTech = $conn->prepare($sqlTech);
$stmtTech->bind_param("i", $technician_id);
$stmtTech->execute();
$resultTech = $stmtTech->get_result();
$tech = $resultTech->fetch_assoc();

// ดึง ID ลูกค้าที่เคยแจ้งซ่อมกับช่างคนนี้
$sql = "
    SELECT DISTINCT r.user_id
    FROM repair_requests r
    WHERE r.technician_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();

$chat_user_ids = [];
while ($row = $result->fetch_assoc()) {
    $chat_user_ids[] = $row['user_id'];
}

// ดึงชื่อผู้ใช้ที่เคยแชท
$chat_users = [];
if (count($chat_user_ids) > 0) {
    $in = implode(',', array_fill(0, count($chat_user_ids), '?'));
    $sql = "SELECT id, full_name FROM users WHERE id IN ($in) ORDER BY full_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($chat_user_ids)), ...$chat_user_ids);
    $stmt->execute();
    $chat_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <title>Inbox - รายชื่อลูกค้า</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Kanit', sans-serif;
      background: #f5f5f5;
    }
    .profile-img {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 15px;
    }
    .chat-item {
      display: flex;
      align-items: center;
    }
    .chat-name {
      flex-grow: 1;
      font-size: 1.25rem;
      font-weight: 500;
      color: #222;
    }
    a.text-decoration-none {
      color: #222;
      text-decoration: none;
    }
    a.text-decoration-none:hover {
      color: #000;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container mt-5">
    <h3>ลูกค้าที่เคยแชท</h3>
    <ul class="list-group mb-4">
      <?php if (count($chat_users) > 0): ?>
        <?php foreach ($chat_users as $user): ?>
          <li class="list-group-item chat-item">
            <a href="chat_room_technician.php?user_id=<?= $user['id'] ?>" class="d-flex align-items-center text-decoration-none">
              <img src="uploads/<?= htmlspecialchars($tech['profile_image'] ?: 'default.jpg') ?>" alt="profile" class="profile-img">
              <span class="chat-name"><?= htmlspecialchars($user['full_name']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="list-group-item">ยังไม่มีลูกค้าที่เคยแชท</li>
      <?php endif; ?>
    </ul>
  </div>
</body>
</html>
