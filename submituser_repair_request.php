<?php
date_default_timezone_set('Asia/Bangkok');
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "home";

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตรวจสอบว่ามีค่า POST ครบถ้วน
if (!isset($_POST['user_id'], $_POST['technician_id'], $_POST['problem_description'])) {
    die("❌ ข้อมูลไม่ครบ กรุณากรอกข้อมูลให้ครบถ้วน");
}

$user_id = $_POST['user_id'];
$technician_id = $_POST['technician_id'];
$problem_description = $_POST['problem_description'];
$status = 'pending'; // ค่าเริ่มต้นของสถานะ
$created_at = date('Y-m-d H:i:s'); // ใช้ timestamp ปัจจุบัน

// อัพโหลดไฟล์รูปภาพ
$repair_image = '';
if (isset($_FILES['repair_image']) && $_FILES['repair_image']['error'] == 0) {
    $image_name = $_FILES['repair_image']['name'];
    $image_tmp = $_FILES['repair_image']['tmp_name'];
    $image_size = $_FILES['repair_image']['size'];
    $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
    
    // ตั้งชื่อไฟล์ใหม่
    $image_new_name = 'repair_' . time() . '.' . $image_extension;
    $image_upload_dir = 'uploads/'; // โฟลเดอร์สำหรับเก็บไฟล์
    $image_path = $image_upload_dir . $image_new_name;

    // ตรวจสอบนามสกุลไฟล์
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($image_extension, $allowed_extensions)) {
        // ตรวจสอบขนาดไฟล์
        if ($image_size <= 5000000) { // ขนาดไม่เกิน 5MB
            // ย้ายไฟล์ไปยังโฟลเดอร์
            if (move_uploaded_file($image_tmp, $image_path)) {
                $repair_image = $image_new_name;
            } else {
                die("❌ ไม่สามารถอัพโหลดไฟล์ได้");
            }
        } else {
            die("❌ ขนาดไฟล์ใหญ่เกินไป");
        }
    } else {
        die("❌ ไฟล์ที่อัพโหลดไม่ถูกต้อง");
    }
}

// ดึงชื่อผู้แจ้งซ่อมจากตาราง users
$sql_user = "SELECT full_name FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();

// ตรวจสอบผลลัพธ์
if ($user_result->num_rows > 0) {
    $user_row = $user_result->fetch_assoc();
    $user_name = $user_row['full_name'];
} else {
    die("❌ ไม่พบข้อมูลผู้ใช้งาน");
}

// ใช้ Prepared Statement เพื่อความปลอดภัย
$sql = "INSERT INTO repair_requests (user_id, technician_id, problem_description, user_name, repair_image, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisssss", $user_id, $technician_id, $problem_description, $user_name, $repair_image, $status, $created_at);

// ตรวจสอบว่าการบันทึกสำเร็จหรือไม่
if ($stmt->execute()) {
    echo "✅ แจ้งซ่อมเรียบร้อย กรุณารอช่างรับงาน";
} else {
    echo "❌ ผิดพลาด: " . $stmt->error;
}

// ปิดการเชื่อมต่อ
$stmt->close();
$conn->close();
?>
