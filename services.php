<?php
session_start();

// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root"; // แก้ตาม config ของคุณ
$password = "";
$dbname = "home";   // แก้ตามชื่อฐานข้อมูลของคุณ

$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลผู้ใช้ถ้ามี session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql_user = "SELECT full_name FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql_user);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $_SESSION['full_name'] = $user['full_name'] ?? 'ไม่ระบุชื่อ';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>บริการแจ้งซ่อมอุปกรณ์ภายในบ้าน</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit&display=swap" rel="stylesheet" />
  <style>
    body {
      font-family: 'Kanit', sans-serif;
    }
  </style>
</head>
<body class="bg-white min-h-screen flex flex-col text-gray-800">

<!-- Navbar -->
<nav class="bg-white shadow-md">
  <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
    <div class="text-xl font-bold">บริการแจ้งซ่อมอุปกรณ์ภายในบ้าน</div>
    <div class="space-x-4 flex items-center">
      <a href="index.php" class="hover:underline">หน้าแรก</a>
      <a href="About_service.html" class="hover:underline">บริการของเรา</a>
      <a href="contact.html" class="hover:underline">ติดต่อเรา</a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <!-- แสดงชื่อผู้ใช้และเมนู -->
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
        <!-- ยังไม่เข้าสู่ระบบ -->
        <div class="relative inline-block z-20">
          <button id="userMenuButton" class="hover:underline">เข้าสู่ระบบ / สมัครสมาชิก</button>
          <div id="userMenu" class="absolute right-0 mt-2 bg-white shadow-lg rounded hidden z-30">
            <a href="login.html" class="block px-4 py-2 hover:bg-gray-100 whitespace-nowrap w-max">เข้าสู่ระบบ</a>
            <a href="register.html" class="block px-4 py-2 hover:bg-gray-100 whitespace-nowrap w-max">สมัครสมาชิก</a>
            <a href="register_repairman.php" class="block px-4 py-2 hover:bg-gray-100 whitespace-nowrap w-max">สมัครสมาชิกสำหรับช่าง</a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Main -->
<main class="flex-grow">
  <!-- Hero Header -->
  <header class="relative bg-cover bg-center text-white text-center py-20" style="background-image: linear-gradient(to right, rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://cdn.pixabay.com/photo/2024/07/01/20/41/ai-generated-8866051_1280.jpg');">
    <div class="relative z-10">
      <h1 class="text-4xl font-bold">ยินดีต้อนรับสู่บริการแจ้งซ่อม</h1>
      <p class="mt-4 text-lg">ให้บริการแจ้งซ่อมออนไลน์ สะดวก รวดเร็ว และมีประสิทธิภาพ</p>

          <div class="mt-8 flex justify-center">
      <div class="bg-white rounded-full shadow-lg flex items-center w-full max-w-2xl px-4 py-2 space-x-2 relative">
        <input
          type="text"
          id="technicianSearch"
          placeholder="ค้นหาประเภทช่างที่ต้องการ"
          class="flex-grow px-4 py-2 rounded-l-full focus:outline-none text-gray-800"
        />
        <div id="searchResults" class="absolute z-40 bg-white border border-gray-300 mt-1 rounded shadow w-full max-w-2xl hidden text-left" style="top: 100%; left: 0; color: black;"></div>

      <button
          onclick="searchByType()"class="bg-yellow-400 hover:bg-yellow-500 text-white font-bold px-6 py-2 rounded-full"
        >
          ค้นหา
      </button>

        <a href="start_repair.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold px-6 py-2 rounded-full">
          เริ่มต้นแจ้งซ่อม
        </a>
      </div>
    </div>
    </div>
  </header>

  <!-- Services -->
  <section class="bg-white py-16">
    <div class="max-w-6xl mx-auto text-center">
      <h2 class="text-3xl font-bold mb-6">บริการของเรา</h2>
      <p class="text-gray-600 mb-12">เราให้บริการหลากหลายครอบคลุมทุกความต้องการ</p>
      <div class="grid md:grid-cols-3 gap-8">
        <div>
          <img src="https://cdn-icons-png.freepik.com/256/8701/8701163.png?semt=ais_hybrid" alt="ซ่อมแซมอุปกรณ์" class="w-24 h-auto mx-auto mb-4 transform transition-transform hover:scale-110" />
          <h4 class="text-xl font-semibold">ซ่อมแซมอุปกรณ์</h4>
          <p class="text-gray-600">บริการซ่อมแซมอุปกรณ์ที่ชำรุด พร้อมการรับประกัน</p>
        </div>
        <div>
          <img src="https://cdn-icons-png.freepik.com/256/5261/5261327.png?semt=ais_hybrid" alt="ตรวจเช็คปัญหา" class="w-24 h-auto mx-auto mb-4 transform transition-transform hover:scale-110" />
          <h4 class="text-xl font-semibold">ตรวจเช็คปัญหา</h4>
          <p class="text-gray-600">วิเคราะห์และตรวจสอบปัญหาที่เกิดขึ้นอย่างมืออาชีพ</p>
        </div>
        <div>
          <img src="https://cdn-icons-png.freepik.com/256/17237/17237778.png?semt=ais_hybrid" alt="บริการถึงที่" class="w-24 h-auto mx-auto mb-4 transform transition-transform hover:scale-110" />
          <h4 class="text-xl font-semibold">บริการถึงที่</h4>
          <p class="text-gray-600">ให้บริการถึงสถานที่ของคุณโดยช่างผู้เชี่ยวชาญ</p>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Footer -->
<footer class="bg-gray-800 text-white text-center py-6">
  <p>&copy; 2025 บริการซ่อมอุปกรณ์ภายในบ้าน</p>
</footer>

<script>
  // เปิด/ปิดเมนู dropdown
  document.getElementById('userMenuButton').addEventListener('click', function (e) {
    e.stopPropagation();
    document.getElementById('userMenu').classList.toggle('hidden');
  });

  // ปิด dropdown เมื่อคลิกที่อื่น
  document.addEventListener('click', function () {
    document.getElementById('userMenu').classList.add('hidden');
  });

  // ระบบค้นหาช่างแบบ Live
  const searchInput = document.getElementById('technicianSearch');
  const resultBox = document.getElementById('searchResults');

  searchInput.addEventListener('input', function () {
    const query = this.value.trim();
    if (query.length < 2) {
      resultBox.innerHTML = '';
      resultBox.classList.add('hidden');
      return;
    }

    fetch('search_technicians.php?q=' + encodeURIComponent(query))
      .then(res => res.json())
          .then(data => {
      if (data.length === 0) {
        resultBox.innerHTML = '<div class="px-4 py-2 text-gray-500">ไม่พบผลลัพธ์</div>';
      } else {
        resultBox.innerHTML = data.map(item =>
          `<a href="technician_detail.php?id=${item.id}" class="block px-4 py-2 hover:bg-gray-100">${item.name} (${item.technician_type})</a>`
        ).join('');
      }
      resultBox.classList.remove('hidden');
    });
  });

  // ปิด dropdown ผลลัพธ์ค้นหาเมื่อคลิกที่อื่น
  document.addEventListener('click', function (event) {
    if (!searchInput.contains(event.target)) {
      resultBox.classList.add('hidden');
    }
  });
  function searchByType() {
  const query = document.getElementById('technicianSearch').value.trim();
  if (query.length < 2) {
    alert("กรุณาพิมพ์ประเภทช่างที่ต้องการค้นหาอย่างน้อย 2 ตัวอักษร");
    return;
  }
  window.location.href = "technician_detail.php?type=" + encodeURIComponent(query);
}

</script>

</body>
</html>
