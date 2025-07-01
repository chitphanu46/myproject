<?php  
session_start();
require_once 'db_config.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $userType = $_POST['userType'] ?? '';

    if (empty($email) || empty($password) || empty($userType)) {
        echo "กรุณากรอกข้อมูลให้ครบถ้วน";
        exit();
    }

    if ($userType == 'user') {
        $sql = "SELECT * FROM users WHERE email = ?";
    } else if ($userType == 'technician') {
        $sql = "SELECT * FROM technicians WHERE email = ?";
    } else if ($userType == 'admins') {
        $sql = "SELECT * FROM admins WHERE email = ?";
    } else {
        echo "ประเภทผู้ใช้ไม่ถูกต้อง";
        exit();
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // ตั้งค่า SESSION
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['name'] = $row['name'] ?? '';

            if ($userType == 'technician') {
                $_SESSION['technician_id'] = $row['id'];
                $_SESSION['technician_name'] = $row['name'];
            } else if ($userType == 'admins') {
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_name'] = $row['name'];
            }

            echo "success";
            exit();
        } else {
            echo "รหัสผ่านไม่ถูกต้อง";
            exit();
        }
    } else {
        echo "ไม่พบอีเมล์ในระบบ";
        exit();
    }
}
?>
