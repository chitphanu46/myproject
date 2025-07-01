<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['technician_id'])) {
    exit("Unauthorized");
}

$technician_id = $_SESSION['technician_id'];

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

    // สร้างโฟลเดอร์ถ้าไม่มี
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            exit("สร้างโฟลเดอร์อัปโหลดไม่สำเร็จ");
        }
    }

    $file_name = uniqid('profile_', true) . '.' . $file_type;
    $target_path = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_path)) {
        $stmt = $conn->prepare("UPDATE technicians SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $target_path, $technician_id);
        if ($stmt->execute()) {
            // อัปเดตสำเร็จ
            header("Location: c.php");
            exit();
        } else {
            exit("อัปเดตฐานข้อมูลล้มเหลว: " . $stmt->error);
        }
    } else {
        exit("ย้ายไฟล์ล้มเหลว");
    }
} else {
    exit("ไม่พบไฟล์อัปโหลด");
}

