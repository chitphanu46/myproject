<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['technician_id'])) {
    header("Location: inbox.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$technician_id = (int)$_GET['technician_id'];

// ดึงชื่อช่าง
$sql = "SELECT name FROM technicians WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $technician_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "ไม่พบข้อมูลช่างที่ระบุ";
    exit();
}
$technician = $result->fetch_assoc();
$technician_name = $technician['name'];

// ส่งข้อความใหม่ + อัปโหลดรูป
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']) ?: null;
    $imagePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        $filename = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = $targetFile;
        }
    }

    // อย่างน้อยต้องมีข้อความหรือรูปภาพ
    if ($message !== null || $imagePath !== null) {
        $sql = "INSERT INTO messages (sender_id, receiver_id, message, image_path, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $user_id, $technician_id, $message, $imagePath);
        $stmt->execute();
    }

    header("Location: chat_room.php?technician_id=$technician_id");
    exit();
}

// ดึงข้อความทั้งหมด
$sql = "SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $technician_id, $technician_id, $user_id);
$stmt->execute();
$messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ห้องแชทกับ <?= htmlspecialchars($technician_name) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      background: #f3f4f6;
    }
    .chat-container {
      max-width: 700px;
      margin: 60px auto;
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      display: flex;
      flex-direction: column;
      height: 80vh;
    }
    .chat-header {
      font-weight: 600;
      margin-bottom: 15px;
    }
    .chat-box {
      flex-grow: 1;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding-right: 10px;
      border-bottom: 1px solid #ddd;
    }
    .message {
      max-width: 70%;
      padding: 10px 15px;
      border-radius: 20px;
      word-wrap: break-word;
    }
    .me {
      background: #d1e7ff;
      align-self: flex-end;
      margin-left: auto;
    }
    .other {
      background: #f1f1f1;
      align-self: flex-start;
      margin-right: auto;
    }
    form.chat-form {
      margin-top: 15px;
      display: flex;
      gap: 10px;
    }
    input[type="text"] {
      flex-grow: 1;
      border-radius: 20px;
      border: 1px solid #ccc;
      padding: 10px 15px;
      font-size: 1rem;
    }
    button[type="submit"] {
      border-radius: 20px;
      padding: 10px 20px;
    }
    img.chat-image {
      max-width: 200px;
      margin-top: 5px;
      border-radius: 10px;
    }
  </style>
</head>
<body>
  <div class="chat-container">
    <div class="chat-header">ห้องแชทกับ <?= htmlspecialchars($technician_name) ?></div>
    <div class="chat-box" id="chatBox">
      <?php while ($msg = $messages->fetch_assoc()): ?>
        <div class="message <?= $msg['sender_id'] == $user_id ? 'me' : 'other' ?>">
          <?= nl2br(htmlspecialchars($msg['message'])) ?>
          <?php if (!empty($msg['image_path'])): ?>
            <br><img src="<?= htmlspecialchars($msg['image_path']) ?>" alt="รูปภาพ" class="chat-image" />
          <?php endif; ?>
          <div style="font-size:0.7rem; color:#666; margin-top:4px; text-align: right;">
            <?= date('H:i, d/m/Y', strtotime($msg['created_at'])) ?>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <form class="chat-form" method="POST" enctype="multipart/form-data" autocomplete="off">
      <input type="text" name="message" placeholder="พิมพ์ข้อความ..." />
      
      <!-- input file ซ่อนไว้ -->
      <input type="file" name="image" id="imageInput" accept="image/*" style="display:none;" />

      <!-- ปุ่มรูปภาพแบบไอคอน -->
      <label for="imageInput" class="btn btn-outline-secondary" title="แนบรูปภาพ">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-image" viewBox="0 0 16 16">
          <path d="M14.002 3a1 1 0 0 1 1 1v8.002a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4.002a1 1 0 0 1 1-1h12.002zM2 2a2 2 0 0 0-2 2v8.002a2 2 0 0 0 2 2h12.002a2 2 0 0 0 2-2V4.002a2 2 0 0 0-2-2H2z"/>
          <path d="M10.648 8.646a.5.5 0 0 1 .704 0l2.148 2.148V4.002a.5.5 0 0 0-.5-.5H2.002a.5.5 0 0 0-.5.5v7.336l3.15-3.15a.5.5 0 0 1 .707 0l2.65 2.65 2.639-2.64z"/>
          <path d="M4.502 5.502a1 1 0 1 0 0-2 1 1 0 0 0 0 2z"/>
        </svg>
      </label>

      <button type="submit" class="btn btn-primary">ส่ง</button>
    </form>

  </div>

  <script>
    const chatBox = document.getElementById('chatBox');
    chatBox.scrollTop = chatBox.scrollHeight;
  </script>
</body>
</html>
