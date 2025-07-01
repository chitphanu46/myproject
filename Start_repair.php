<?php
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

// ดึงชื่อผู้ใช้จากเซสชั่น
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT full_name, profile_image FROM users WHERE id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();
    $user = $user_result->fetch_assoc();
    $_SESSION['full_name'] = $user['full_name'];
    $profile_image = $user['profile_image']; // มีค่ารูปโปรไฟล์
}
// นับจำนวนงานซ่อมตามสถานะสำหรับผู้ใช้
$sql_pending = "SELECT COUNT(*) AS count FROM repair_requests WHERE user_id = ? AND technician_id IS NULL";
$stmt_pending = $conn->prepare($sql_pending);
$stmt_pending->bind_param("i", $user_id);
$stmt_pending->execute();
$pending_result = $stmt_pending->get_result();
$pending_count = $pending_result->fetch_assoc()['count'];

$sql_inprogress = "SELECT COUNT(*) AS count FROM repair_requests WHERE user_id = ? AND technician_id IS NOT NULL AND status = 'in-progress'";
$stmt_inprogress = $conn->prepare($sql_inprogress);
$stmt_inprogress->bind_param("i", $user_id);
$stmt_inprogress->execute();
$inprogress_result = $stmt_inprogress->get_result();
$inprogress_count = $inprogress_result->fetch_assoc()['count'];

$sql_completed = "SELECT COUNT(*) AS count FROM repair_requests WHERE user_id = ? AND status = 'completed'";
$stmt_completed = $conn->prepare($sql_completed);
$stmt_completed->bind_param("i", $user_id);
$stmt_completed->execute();
$completed_result = $stmt_completed->get_result();
$completed_count = $completed_result->fetch_assoc()['count'];

$sql_cancelled = "SELECT COUNT(*) AS count FROM repair_requests WHERE user_id = ? AND status = 'cancelled'";
$stmt_cancelled = $conn->prepare($sql_cancelled);
$stmt_cancelled->bind_param("i", $user_id);
$stmt_cancelled->execute();
$cancelled_result = $stmt_cancelled->get_result();
$cancelled_count = $cancelled_result->fetch_assoc()['count'];


// ฟังก์ชันทำให้ปุ่มแท็บ active
function isActive($tabStatus) {
    return (isset($_GET['status']) && $_GET['status'] === $tabStatus) || (!isset($_GET['status']) && $tabStatus === 'all') ? 'active' : '';
}

// ดึงช่างตามสถานะ
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

if ($status === 'all') {
    $sql = "SELECT * FROM technicians";
    $result = $conn->query($sql);
} else {
    $sql = "SELECT * FROM technicians WHERE status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $result = $stmt->get_result();
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
?>


<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Repair</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
 <style>
        body {
            font-family: 'Kanit', sans-serif !important;
        }
       #mainContent {
            margin-left: 256px; /* เหลือพื้นที่ sidebar */
            padding: 20px 32px 40px 32px; /* padding ซ้าย-ขวา = 32px */
            padding-top: 100px;
            min-height: 400px;
            transition: margin-left 0.5s ease, min-height 0.5s ease;
            width: calc(100% - 256px); /* ทำให้ขยายเต็มพื้นที่ที่เหลือจาก sidebar */
            box-sizing: border-box;
        }

        #mainContent.sidebar-open {
            margin-left: 256px;      /* เลื่อนขวาให้ชิด sidebar */
            min-height: 700px;       /* ขยายความสูงขึ้น */
            max-width: 1144px; /* 1400px - 256px เพื่อไม่ล้น */
        }

        /* Welcome message */
        .welcome-message {
            position: relative;
            top: 500px;
            margin-bottom: 500px;
            text-align: center;
            font-size: 2rem;
            color: #333;
            font-weight: 600;
            z-index: 999;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.6s, transform 0.6s;
        }
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* สไตล์สำหรับการ์ดของช่าง */
        .product-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px;
            margin: 20px 0 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center; /* จัดรูปให้อยู่กลาง */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .product-card img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 16px;
            border: 4px solid #f0f0f0;
            display: block;
        }

        
        .product-card .product-info {
            flex-grow: 1; /* ให้ข้อมูลขยายเต็มพื้นที่ */
        }

        .product-card .product-info h5 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .product-card .product-info p {
            margin: 5px 0;
        }

        .profile-image {
            width: 48px;         /* กำหนดความกว้าง */
            height: 48px;        /* กำหนดความสูง */
            border-radius: 50%;  /* ทำให้เป็นวงกลม */
            object-fit: cover;   /* ครอบภาพให้เต็มกรอบโดยรักษาสัดส่วน */
            object-position: center center; /* จัดตำแหน่งตรงกลางภาพ */
            border: 2px solid #ddd;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }

        .product-card img {
            width: 180px;
            height: 180px;
            border-radius: 100px;
            object-fit: cover;
            margin-bottom: 16px;
            border: 4px solid #f0f0f0;
        }

        .product-info {
            width: 100%;
            text-align: left;
            padding: 0 10px;
        }

        .product-info h5 {
            font-size: 1.3rem;
            margin-bottom: 8px;
            font-weight: 700;
            color: #333;
        }

        .product-info p {
            margin: 4px 0;
            color: #555;
            font-size: 0.95rem;
        }

        textarea.form-control {
            margin-top: 12px;
            resize: vertical;
        }

        button.btn-primary {
            margin-top: 12px;
            width: 100%;
            border-radius: 8px;
        }
        .btn-primary {
            background: linear-gradient(145deg, #007bff, #0056b3);
            border: none;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-primary:hover {
            background: linear-gradient(145deg, #0056b3, #003b7b);
            transform: translateY(-3px);
        }

        /* Floating Button */
        .floating-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, rgb(255, 255, 255), rgb(157, 203, 255));
            border: none;
            padding: 15px;
            border-radius: 50%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 9999;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .floating-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.2);
        }

        .floating-button img {
            width: 40px;
            height: 40px;
        }

        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s, transform 0.6s;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* แถบรายการสถานะการแจ้งซ่อม */
        .tabs-bar {
            margin-top: 80px; /* ระยะห่างจาก navbar */
            text-align: center;
        }

        .tabs-bar a {
            font-size: 1.1rem;
            margin: 0 15px;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 20px;
            background-color: #f1f1f1;
            color: #333;
            transition: background-color 0.3s, color 0.3s;
        }

        .tabs-bar a:hover {
            background-color: #007bff;
            color: white;
        }

        .tabs-bar a.active {
            background-color: #007bff;
            color: white;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #ffffff;
            position: fixed;
            top: 64px; /* ความสูง Navbar */
            left: 0;
            overflow-y: auto;
            border-right: 1px solid #e5e7eb;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            padding-top: 20px;
            z-index: 100;
        }

        nav.navbar, nav {
            z-index: 2000; /* navbar อยู่บนสุด */
            position: fixed;
            top: 0;
            width: 100%;
        }

        .sidebar h4 {
            font-size: 20px;
            color: #374151;
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: #374151;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 12px;
            transition: background-color 0.2s ease;
        }

        .sidebar a:hover {
            background-color: #e0f2fe;
        }

        .sidebar a i {
            font-size: 18px;
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }

        .main {
            margin-left: 240px;
            padding: 2rem;
            padding-top: 100px; /* เพิ่มให้เนื้อหาหลักอยู่ต่ำกว่า navbar */
        }
        .hidden {
            display: none;
        }
        .profile-container:hover .profile-hover-box {
    display: block;
    
}

    </style>
</head>
<body>
<!-- Navbar -->
<nav class="bg-white shadow-md">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
    <div class="text-xl font-bold">บริการแจ้งซ่อมอุปกรณ์ภายในบ้าน</div>
    <div class="flex items-center space-x-4">
      <a href="index.php" class="hover:underline">หน้าแรก</a>
      <a href="About_service.html" class="hover:underline">บริการของเรา</a>
      <a href="contact.html" class="hover:underline">ติดต่อเรา</a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <div class="relative inline-block z-20">
          <button id="userMenuButton" class="hover:underline text-blue-700 font-semibold">
            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
          </button>
          <div id="userMenu" class="absolute right-0 mt-2 bg-white shadow-lg rounded hidden z-30 min-w-[12rem]">
            <a href="profile.php" class="flex px-4 py-2 hover:bg-gray-100 whitespace-nowrap">โปรไฟล์</a>
            <a href="change_password.php" class="flex px-4 py-2 hover:bg-gray-100 whitespace-nowrap">เปลี่ยนรหัสผ่าน</a>
            <a href="my_repairs.php" class="flex px-4 py-2 hover:bg-gray-100 whitespace-nowrap">ประวัติแจ้งซ่อม</a>
            <a href="logout.php" class="flex px-4 py-2 text-red-500 hover:bg-red-100 whitespace-nowrap">ออกจากระบบ</a>
          </div>
        </div>
      <?php else: ?>
        <div class="relative inline-block z-20">
          <button id="userMenuButton" class="hover:underline text-blue-700 font-semibold">
            เข้าสู่ระบบ / สมัครสมาชิก
          </button>
          <div id="userMenu" class="absolute right-0 mt-2 bg-white shadow-lg rounded hidden z-30 min-w-[12rem]">
            <a href="login.html" class="block px-4 py-2 hover:bg-gray-100 whitespace-nowrap">เข้าสู่ระบบ</a>
            <a href="register.html" class="block px-4 py-2 hover:bg-gray-100 whitespace-nowrap">สมัครสมาชิก</a>
            <a href="register_repairman.php" class="block px-4 py-2 hover:bg-gray-100 whitespace-nowrap">สมัครสมาชิกสำหรับช่าง</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Profile top-right -->
<div class="profile-topbar" style="position: fixed; top: 10px; right: 20px; z-index: 3000;">
    <div class="profile-container position-relative" style="display: inline-block; cursor: pointer;">
        <?php
        $img_path = (!empty($profile_image) && file_exists(__DIR__ . '/' . $profile_image)) ? $profile_image : 'uploads/default.png';
        ?>
        <img src="<?= htmlspecialchars($img_path) ?>" alt="Profile"
             width="48" height="48"
             class="rounded-circle profile-image"
             style="object-fit: cover; border: 2px solid #ddd;">

        <div class="profile-hover-box" 
             style="display: none; position: absolute; top: 60px; right: 0; background: white; padding: 12px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); min-width: 220px; z-index: 999; border: 1px solid #ddd; text-align: left;">
            <strong><?= htmlspecialchars($user['full_name'] ?? '') ?></strong><br>
            <small><?= htmlspecialchars($_SESSION['user_id']) ?></small><br>
            <span class="text-muted">ผู้ใช้ทั่วไป</span>

            <form action="upload_user_profile.php" method="POST" enctype="multipart/form-data" style="margin-top: 10px;">
                <input type="file" name="profile_image" id="profileUploadUser" onchange="this.form.submit()" hidden>
                <label for="profileUploadUser" class="btn btn-sm btn-outline-secondary" style="cursor:pointer;">เปลี่ยนรูป</label>
            </form>
        </div>
    </div>
</div>

<!-- Sidebar -->
<div id="sidebar" class="sidebar">
  <h4>เมนูหลัก</h4>
  <a href="services.php"><i class="bi bi-house-door-fill"></i> หน้าหลัก</a>
  <a href="my_repairs.php" class="d-flex align-items-center gap-2">
    <!-- SVG icon -->
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:20px; height:20px;">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
    </svg>
    สถานะการแจ้งซ่อม
  </a>

  <!-- ปุ่มออกจากระบบ -->
  <a href="logout.php" class="mt-3 text-danger d-flex align-items-center gap-2" style="font-weight: 600;">
    <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
  </a>
</div>

<!-- JavaScript สำหรับ toggle submenu -->
<script>
function toggleSubmenu(event) {
  event.preventDefault(); // ป้องกันลิงก์ # กระโดดขึ้นบน
  document.getElementById('submenu').classList.toggle('hidden');
}
</script>

<div id="mainContent" class="main fade-in container-fluid">
        <?php if ($message): ?>
            <div class="message-box">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
<div class="row gx-4 gy-4">
  <?php
  if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          $id = $row['id'];
          $name = $row['name'];
          $technician_type = $row['technician_type'];
          $specialization = $row['specialization'];
          $phone_number = $row['phone_number'];
          $profile_image = $row['profile_image'];
          $address = $row['address'];
  ?>
      <div class="col-12 col-sm-6 col-md-4 col-lg-3 fade-in">
          <div class="product-card h-100">
              <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image">
              <div class="product-info">
                  <h5><?php echo htmlspecialchars($name); ?></h5>
                  <p><i class="bi bi-tools text-primary me-2"></i>ประเภท : <?php echo htmlspecialchars($technician_type); ?></p>
                <p><i class="bi bi-star-fill text-warning me-2"></i>ความเชี่ยวชาญ : <?php echo htmlspecialchars($specialization); ?></p>
                <p><i class="bi bi-telephone-fill text-success me-2"></i>เบอร์โทร : <?php echo htmlspecialchars($phone_number); ?></p>
                <p><i class="bi bi-geo-alt-fill text-danger me-2"></i>ที่อยู่ : <?php echo htmlspecialchars($address); ?></p>
                  <form action="submituser_repair_request.php" method="POST" enctype="multipart/form-data">
                      <input type="hidden" name="user_id" value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : ''; ?>">
                      <input type="hidden" name="technician_id" value="<?php echo htmlspecialchars($id); ?>">
                      <textarea class="form-control" name="problem_description" placeholder="กรุณากรอกปัญหาที่เกิดขึ้น" required></textarea>
                      <input type="file" name="repair_image" class="form-control mt-2">
                      <button type="submit" class="btn btn-primary mt-2">แจ้งซ่อม</button>
                  </form>
              </div>
          </div>
      </div>
  <?php
      }
  } else {
      echo "<p class='text-center text-danger'>❌ ไม่พบข้อมูลช่าง</p>";
  }
  ?>
</div>
    </div>
    <!-- Floating Button -->
    <a href="inbox.php" class="floating-button">
    <img src="https://images.icon-icons.com/2582/PNG/512/message_bubble_chat_icon_154003.png" alt="Inbox">
</a>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const fadeIns = document.querySelectorAll('.fade-in');
            fadeIns.forEach(section => section.classList.add('visible'));
        });
    </script>
<?php $conn->close(); ?>
    <script>
    const userButton = document.getElementById("userMenuButton");
    const userMenu = document.getElementById("userMenu");

    if (userButton) {
        userButton.addEventListener("click", () => {
        userMenu.classList.toggle("hidden");
        });
    }

    document.addEventListener("click", function(event) {
        if (!userButton.contains(event.target) && !userMenu.contains(event.target)) {
        userMenu.classList.add("hidden");
        }
    });
    document.addEventListener("DOMContentLoaded", function () {
    const profileContainer = document.querySelector(".profile-container");
    const hoverBox = document.querySelector(".profile-hover-box");

    let hoverTimeout;

    if (profileContainer && hoverBox) {
        profileContainer.addEventListener("mouseenter", () => {
            clearTimeout(hoverTimeout);
            hoverBox.style.display = "block";
        });

        hoverBox.addEventListener("mouseenter", () => {
            clearTimeout(hoverTimeout);
            hoverBox.style.display = "block";
        });

        profileContainer.addEventListener("mouseleave", () => {
            hoverTimeout = setTimeout(() => {
                if (!profileContainer.matches(':hover') && !hoverBox.matches(':hover')) {
                    hoverBox.style.display = "none";
                }
            }, 200); // หน่วงเวลา 200ms
        });

        hoverBox.addEventListener("mouseleave", () => {
            hoverTimeout = setTimeout(() => {
                if (!profileContainer.matches(':hover') && !hoverBox.matches(':hover')) {
                    hoverBox.style.display = "none";
                }
            }, 200); // หน่วงเวลา 200ms
        });
    }
});
    </script>
</body>
</html>