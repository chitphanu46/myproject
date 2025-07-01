<?php 
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "home";

// เชื่อมต่อกับฐานข้อมูล
$conn = new mysqli($servername, $username, $password, $dbname);

// เช็คการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตรวจสอบว่าเป็นการร้องขอแบบ POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับข้อมูลจากฟอร์ม
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $profile_image_path = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_tmp = $_FILES['profile_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid('profile_', true) . '.' . $file_ext;
            $target_path = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $target_path)) {
                $profile_image_path = $target_path;
            }
        } else {
            echo "ไฟล์รูปภาพไม่รองรับ";
            exit;
        }
    }

    // เตรียม SQL statement พร้อมช่องใส่ profile_image
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $full_name, $email, $password, $phone, $address, $profile_image_path);

    if ($stmt->execute()) {
        echo "สมัครสมาชิกสำเร็จ";
    } else {
        echo $conn->error;
    }
}

$conn->close();
?>
