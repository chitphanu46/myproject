<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "home";

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// รับค่าจากฟอร์ม โดยใช้ชื่อที่ตรงกับ HTML
$name = $_POST['technician_name'];
$email = $_POST['technician_email'];
$password = password_hash($_POST['technician_password'], PASSWORD_BCRYPT);
$specialization = $_POST['specialization'];
$address = $_POST['address'];
$phone_number = $_POST['phone_number'];

// ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
$sql = "INSERT INTO technicians (name, email, password, specialization, address, phone_number) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $name, $email, $password, $specialization, $address, $phone_number);

if ($stmt->execute()) {
    // ส่งข้อความ "สมัครสมาชิกสำเร็จ" ไปยัง AJAX
    echo "สมัครสมาชิกสำเร็จ";
} else {
    // ส่งข้อความข้อผิดพลาด
    echo "ข้อผิดพลาด: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
