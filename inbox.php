<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงช่างที่ผู้ใช้กดแจ้งซ่อมไปแล้ว พร้อมเวลาข้อความล่าสุดหรือเวลาการแจ้งซ่อม
$sql = "
    SELECT 
        t.id,
        t.name,
        t.profile_image,
        MAX(m.created_at) AS last_message_time,
        MAX(r.created_at) AS last_request_time
    FROM technicians t
    INNER JOIN repair_requests r ON t.id = r.technician_id AND r.user_id = ?
    LEFT JOIN messages m ON ((m.sender_id = t.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = t.id))
    GROUP BY t.id, t.name, t.profile_image
    ORDER BY 
        COALESCE(MAX(m.created_at), MAX(r.created_at)) DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$chat_techs = [];
while ($row = $result->fetch_assoc()) {
    $chat_techs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <title>Inbox - รายชื่อช่างที่แจ้งซ่อม</title>
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
    <h3>ช่างที่แจ้งซ่อมแล้ว</h3>
    <ul class="list-group mb-4">
      <?php if (count($chat_techs) > 0): ?>
        <?php foreach ($chat_techs as $tech): ?>
          <li class="list-group-item chat-item">
            <a href="chat_room.php?technician_id=<?= htmlspecialchars($tech['id']) ?>" class="d-flex align-items-center text-decoration-none">
              <img src="<?= htmlspecialchars($tech['profile_image'] ?: 'default.jpg') ?>" alt="profile" class="profile-img">
              <span class="chat-name"><?= htmlspecialchars($tech['name']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      <?php else: ?>
        <li class="list-group-item">ยังไม่มีช่างที่แจ้งซ่อม</li>
      <?php endif; ?>
    </ul>
  </div>
</body>
</html>
