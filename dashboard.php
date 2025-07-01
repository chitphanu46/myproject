<?php
session_start();

// ตรวจสอบว่ามี session ของผู้ใช้หรือไม่
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // ถ้าไม่มี ให้กลับไปหน้า login
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก</title>
</head>
<body>
    <h1>ยินดีต้อนรับ, <?php echo $_SESSION['user_name']; ?>!</h1>
    <p>นี่คือหน้าหลักของคุณ</p>
    <a href="logout.php">ออกจากระบบ</a>
</body>
</html>
