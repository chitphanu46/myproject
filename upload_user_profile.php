<?php
session_start();
require_once 'db_config.php';

// ตรวจสอบว่าเข้าสู่ระบบแล้วหรือยัง
if (!isset($_SESSION['user_id'])) {
    exit("คุณยังไม่ได้เข้าสู่ระบบ");
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["profile_image"])) {
    $upload_dir = "uploads/";

    $file_type = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($file_type, $allowed)) {
        exit("ไฟล์ประเภทนี้ไม่รองรับ");
    }

    if ($_FILES["profile_image"]["size"] > 5 * 1024 * 1024) {
        exit("ไฟล์ใหญ่เกิน 5MB");
    }

    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            exit("ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้");
        }
    }

    $file_name = uniqid('profile_', true) . '.' . $file_type;
    $target_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_path)) {
        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $target_path, $user_id);

        if ($stmt->execute()) {
            header("Location: Start_repair.php"); // กลับไปยังหน้าหลักผู้ใช้
            exit();
        } else {
            exit("อัปเดตฐานข้อมูลไม่สำเร็จ: " . $stmt->error);
        }
    } else {
        exit("ไม่สามารถย้ายไฟล์อัปโหลดได้");
    }
} else {
    exit("ไม่พบไฟล์อัปโหลด");
}
?>
