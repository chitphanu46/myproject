<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "home";

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตรวจสอบว่ามีข้อมูลจากฟอร์มหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $technician_name = $_POST['technician_name'];
    $technician_type = $_POST['technician_type'];
    $specialization = $_POST['specialization'];
    $phone_number = $_POST['phone_number'];
    $profile_image = $_POST['profile_image'];
    $problem_description = $_POST['problem_description'];

    // SQL สำหรับบันทึกข้อมูล
    $sql = "INSERT INTO repair_requests (technician_name, technician_type, specialization, phone_number, profile_image, problem_description, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')";  // สถานะเริ่มต้นเป็น Pending
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $technician_name, $technician_type, $specialization, $phone_number, $profile_image, $problem_description);

    // ตรวจสอบว่า INSERT สำเร็จหรือไม่
    if ($stmt->execute()) {
        // การบันทึกสำเร็จ
        header("Location: start_repair.php?status=success"); // ส่งกลับไปที่หน้า start_repair พร้อมแสดงข้อความ
        exit();
    } else {
        // การบันทึกล้มเหลว
        echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "ไม่พบข้อมูลฟอร์ม";
}

$conn->close();
?>
