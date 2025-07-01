<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "home";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['technician_name'];
    $email = $_POST['technician_email'];
    $raw_password = $_POST['technician_password'];
    $confirm_password = $_POST['confirm_password'];
    $technician_type = $_POST['technician_type'];
    $specialization = $_POST['specialization'];
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];

    if ($raw_password !== $confirm_password) {
        die("รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน");
    }

    $password = password_hash($raw_password, PASSWORD_BCRYPT);

    if (empty($technician_type)) {
        die("กรุณาเลือกประเภทของช่าง");
    }

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = uniqid() . "_" . basename($_FILES['profile_image']['name']);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($imageFileType, $allowed_types)) {
            die("ประเภทไฟล์ไม่ถูกต้อง (อนุญาตเฉพาะ JPG, JPEG, PNG, GIF)");
        }

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $sql = "INSERT INTO technicians (name, email, password, technician_type, specialization, address, phone_number, profile_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $name, $email, $password, $technician_type, $specialization, $address, $phone_number, $target_file);

            if ($stmt->execute()) {
                $success_message = 'สมัครสมาชิกสำเร็จ!';
            } else {
                die("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error);
            }
            $stmt->close();
        } else {
            die("เกิดข้อผิดพลาดในการอัปโหลดไฟล์");
        }
    } else {
        die("กรุณาอัปโหลดรูปภาพ");
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>สมัครสมาชิกสำหรับช่างซ่อม</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    body {
      font-family: 'Kanit', sans-serif;
      background: radial-gradient(ellipse at bottom, #1e3a8a 0%, #0f172a 100%);
      color: #e0f2fe;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    .container {
      background-color: rgba(255, 255, 255, 0.08);
      backdrop-filter: blur(12px);
      border-radius: 1rem;
      padding: 40px;
      max-width: 600px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
      position: relative;
      color: white;
      z-index: 1;
    }

    .input-group {
      border: 2px solid #d1d5db;
      border-radius: 1rem;
      overflow: hidden;
      margin-bottom: 1rem;
      background-color: rgba(255,255,255,0.1);
      position: relative;
    }

    .input-group-text {
      background-color: transparent;
      color: #6b7280;
      border: none;
      width: 48px;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }

    .input-group-text::after {
      content: '';
      position: absolute;
      right: 0;
      top: 25%;
      bottom: 25%;
      width: 1px;
      background-color: #e5e7eb;
    }

    .form-control {
      border: none;
      padding: 12px;
      font-weight: 700;
      background-color: transparent;
      color: #fff;
    }

    .form-control::placeholder {
      color: #cbd5e1;
    }

    .btn-primary {
      background-color: #3b82f6;
      border: 2px solid #3b82f6;
      color: white;
      font-weight: 700;
      border-radius: 1rem;
      padding: 12px;
      width: 100%;
      transition: background-color 0.3s, border-color 0.3s;
    }

    .btn-primary:hover {
      background-color: #2563eb;
      border-color: #2563eb;
    }

    .toggle-password-icon {
      position: absolute;
      top: 50%;
      right: 40px;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6b7280;
      z-index: 10;
    }

    .password-check-icon {
      position: absolute;
      top: 50%;
      right: 10px;
      transform: translateY(-50%);
      z-index: 10;
      display: none;
    }

    .check-icon, .cross-icon {
      font-size: 18px;
    }

    .check-icon { color: green; }
    .cross-icon { color: red; }

    h2 {
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-align: center;
    }

    /* ดาวตก */
    .shooting-star {
      position: absolute;
      width: 2px;
      height: 80px;
      background: linear-gradient(-45deg, white, rgba(255, 255, 255, 0));
      opacity: 0;
      transform: translate(0, 0) rotate(-45deg);
      animation: shooting 3s ease-in-out infinite;
      filter: blur(1px);
      z-index: 0;
    }

    @keyframes shooting {
      0% {
        opacity: 0;
        transform: translate(0, 0) rotate(-45deg);
      }
      10% {
        opacity: 1;
      }
      90% {
        opacity: 1;
      }
      100% {
        opacity: 0;
        transform: translate(500px, 500px) rotate(-45deg);
      }
    }
  </style>
</head>
<body>
  <!-- ดาวตกหลายดวง -->
  <div class="shooting-star" style="top: 10%; left: 20%; animation-delay: 1s;"></div>
  <div class="shooting-star" style="top: 30%; left: 50%; animation-delay: 2.5s; animation-duration: 2.5s;"></div>
  <div class="shooting-star" style="top: 60%; left: 70%; animation-delay: 3.2s; animation-duration: 3s;"></div>
  <div class="shooting-star" style="top: 80%; left: 40%; animation-delay: 4.5s; animation-duration: 2.8s;"></div>

  <div class="container">
    <h2>สมัครสมาชิกสำหรับช่างซ่อม</h2>

    <?php if ($success_message): ?>
      <div class="alert alert-success rounded-3"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <form id="technicianForm" method="POST" enctype="multipart/form-data" novalidate>
      <form id="technicianForm" method="POST" enctype="multipart/form-data" novalidate>
  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-user"></i></span>
    <input type="text" name="technician_name" class="form-control" placeholder="ชื่อ-นามสกุล" required />
  </div>

  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
    <input type="email" name="technician_email" class="form-control" placeholder="อีเมล" required />
  </div>

  <div class="input-group position-relative">
    <span class="input-group-text"><i class="fas fa-lock"></i></span>
    <input type="password" id="password" name="technician_password" class="form-control" placeholder="รหัสผ่าน" required />
    <span class="toggle-password-icon" data-target="#password"><i class="fas fa-eye-slash"></i></span>
    <span class="password-check-icon" id="passwordCheckIcon1">
      <i class="fas fa-check-circle check-icon"></i>
      <i class="fas fa-times-circle cross-icon"></i>
    </span>
  </div>

  <div class="input-group position-relative">
    <span class="input-group-text"><i class="fas fa-lock"></i></span>
    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="ยืนยันรหัสผ่าน" required />
    <span class="toggle-password-icon" data-target="#confirm_password"><i class="fas fa-eye-slash"></i></span>
    <span class="password-check-icon" id="passwordCheckIcon2">
      <i class="fas fa-check-circle check-icon"></i>
      <i class="fas fa-times-circle cross-icon"></i>
    </span>
  </div>

  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-tools"></i></span>
    <select name="technician_type" class="form-control" required>
      <option value="">เลือกประเภทของช่าง</option>
      <option value="ช่างไฟฟ้า">⚡ ช่างไฟฟ้า</option>
      <option value="ช่างประปา">🚰 ช่างประปา</option>
      <option value="ช่างแอร์">❄️ ช่างแอร์</option>
      <option value="ช่างทาสี">🎨 ช่างทาสี</option>
      <option value="ช่างซ่อมอุปกรณ์เครื่องใช้ไฟฟ้า">🔧 ช่างซ่อมอุปกรณ์เครื่องใช้ไฟฟ้า</option>
      <option value="ช่างกุญแจ">🔑 ช่างกุญแจ</option>
    </select>
  </div>

  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-cogs"></i></span>
    <input type="text" name="specialization" class="form-control" placeholder="ความเชี่ยวชาญ" required />
  </div>

  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-phone"></i></span>
    <input type="tel" name="phone_number" class="form-control" placeholder="เบอร์โทร" pattern="[0-9]{10,15}" required />
  </div>

  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
    <textarea name="address" class="form-control" rows="3" placeholder="ที่อยู่" required></textarea>
  </div>

  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-image"></i></span>
    <input type="file" name="profile_image" accept="image/*" class="form-control" required />
  </div>

  <button type="submit" class="btn btn-primary">สมัครสมาชิก</button>
</form>

    </form>
  </div>

  <script>
    $(document).ready(function () {
      function checkPasswordMatch() {
        const pwd = $("#password").val();
        const cpwd = $("#confirm_password").val();

        if (pwd.length > 0) {
          $("#passwordCheckIcon1").show();
          if (pwd === cpwd && cpwd.length > 0) {
            $("#passwordCheckIcon1 .check-icon").show();
            $("#passwordCheckIcon1 .cross-icon").hide();
          } else {
            $("#passwordCheckIcon1 .check-icon").hide();
            $("#passwordCheckIcon1 .cross-icon").show();
          }
        } else {
          $("#passwordCheckIcon1").hide();
        }

        if (cpwd.length > 0) {
          $("#passwordCheckIcon2").show();
          if (pwd === cpwd) {
            $("#passwordCheckIcon2 .check-icon").show();
            $("#passwordCheckIcon2 .cross-icon").hide();
          } else {
            $("#passwordCheckIcon2 .check-icon").hide();
            $("#passwordCheckIcon2 .cross-icon").show();
          }
        } else {
          $("#passwordCheckIcon2").hide();
        }
      }

      $("#password, #confirm_password").on("input", checkPasswordMatch);

      $(".toggle-password-icon").click(function () {
        const targetInput = $($(this).data("target"));
        const icon = $(this).find("i");
        const type = targetInput.attr("type") === "password" ? "text" : "password";
        targetInput.attr("type", type);
        icon.toggleClass("fa-eye fa-eye-slash");
      });

      $("#passwordCheckIcon1, #passwordCheckIcon2").hide();
    });
  </script>
</body>
</html>
