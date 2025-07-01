<?php
session_start();

// ตรวจสอบว่าเข้าสู่ระบบแล้ว
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>โปรไฟล์ของคุณ</title>
</head>
<body>
  <h2>โปรไฟล์ของคุณ</h2>
  <p>ชื่อ: <?php echo $fullname; ?></p>
  <p>อีเมล์: <?php echo $email; ?></p>
  <a href="logout.php">ออกจากระบบ</a>
</body>
</html>
