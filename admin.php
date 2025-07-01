<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "home";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$technicianCount = 0;
$taskCount = 0;
$userCount = 0;

$result = $conn->query("SELECT COUNT(*) AS technician_count FROM technicians");
if ($result->num_rows > 0) {
    $technicianCount = $result->fetch_assoc()['technician_count'];
}

$result = $conn->query("SELECT COUNT(*) AS task_count FROM repair_requests");
if ($result->num_rows > 0) {
    $taskCount = $result->fetch_assoc()['task_count'];
}

$result = $conn->query("SELECT COUNT(*) AS user_count FROM users");
if ($result->num_rows > 0) {
    $userCount = $result->fetch_assoc()['user_count'];
}

// ดึงข้อมูลช่างที่ถูกแจ้งซ่อมมากที่สุด (5 อันดับ)
$topTechnicians = [];
$sqlTopTech = "
    SELECT technician_id, COUNT(*) AS task_count 
    FROM repair_requests 
    GROUP BY technician_id 
    ORDER BY task_count DESC 
    LIMIT 5
";
$result = $conn->query($sqlTopTech);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // ดึงชื่อช่างจากตาราง technicians
        $techId = $row['technician_id'];
        $count = $row['task_count'];
        $nameResult = $conn->query("SELECT name FROM technicians WHERE id = $techId");
        $name = ($nameResult && $nameResult->num_rows > 0) ? $nameResult->fetch_assoc()['name'] : "ช่าง ID $techId";
        $topTechnicians[] = ['name' => $name, 'count' => (int)$count];
    }
}
// ดึงข้อมูลสาเหตุซ่อมที่พบบ่อยที่สุด 5 อันดับ
// ดึงข้อมูลสาเหตุซ่อมที่พบบ่อยที่สุด 5 อันดับ
$commonCauses = [];
$sqlCause = "
    SELECT problem_description, COUNT(*) AS count
    FROM repair_requests
    GROUP BY problem_description
    ORDER BY count DESC
    LIMIT 5
";
$result = $conn->query($sqlCause);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $commonCauses[] = ['problem_description' => $row['problem_description'], 'count' => (int)$row['count']];
    }
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบเเจ้งซ่อม - แอดมิน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Kanit&display=swap');
        body {
            margin: 0;
            font-family: 'Kanit', sans-serif !important;
        }

/* === Sidebar หลัก === */
.sidebar {
  width: 80px;
  height: 100vh;
  background: #ffffff;
  position: fixed;
  overflow: hidden;
  border-right: 1px solid #e5e7eb;
  box-shadow: 2px 0 5px rgba(0,0,0,0.05);
  transition: width 0.3s ease-in-out;
  padding-top: 20px;
  z-index: 100;
}

/* ขยายเมื่อ hover */
.sidebar:hover {
  width: 240px;
}

/* โลโก้ (ซ่อนก่อน) */
.logo {
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  margin-bottom: 20px;
}

.logo h2 {
  font-size: 20px;
  color: #374151;
  opacity: 0;
  transform: translateY(-10px);
  transition: opacity 0.3s ease, transform 0.3s ease;
  white-space: nowrap;
}

.sidebar:hover .logo h2 {
  opacity: 1;
  transform: translateY(0);
}

/* รายการเมนู */
.sidebar ul {
  list-style: none;
  padding: 0;
  margin: 0;
  margin-top: 20px;
}

.sidebar ul li {
  margin: 4px 8px;
}

.sidebar ul li a {
  display: flex;
  align-items: center;
  color: #374151;
  text-decoration: none;
  padding: 12px 16px;
  border-radius: 12px;
  transition: background-color 0.2s ease, padding 0.3s ease;
  cursor: pointer;
  white-space: nowrap;
  overflow: hidden;
}

.sidebar ul li a:hover {
  background-color: #e0f2fe;
}

.sidebar ul li a i {
  font-size: 20px;
  width: 30px;
  text-align: center;
  flex-shrink: 0;
  transition: transform 0.3s ease;
}

.sidebar ul li a span {
  opacity: 0;
  margin-left: 0;
  max-width: 0;
  overflow: hidden;
  white-space: nowrap;
  transition: opacity 0.3s ease, margin-left 0.3s ease, max-width 0.3s ease;
}

.sidebar:hover ul li a span {
  opacity: 1;
  margin-left: 10px;
  max-width: 200px;
}

/* === Main content ขยับตามแถบ === */
.main-content {
  margin-left: 80px;
  padding: 20px;
  transition: margin-left 0.3s ease-in-out;
}

.sidebar:hover ~ .main-content {
  margin-left: 240px;
}

/* === เนื้อหาอื่น ๆ === */
.top-bar {
  background-color: #f4f4f4;
  padding: 10px;
  border-bottom: 1px solid #ccc;
}

.dashboard {
  display: flex;
  gap: 20px;
  margin-top: 20px;
  flex-wrap: wrap;
  justify-content: flex-start;
}

.stat-card {
  width: 200px;
  height: 120px;
  background-color: #fff;
  border: 1px solid #ccc;
  border-radius: 8px;
  padding: 15px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: relative;
  color: #333;
}

.stat-card p {
  font-size: 20px;
  margin: 0;
}

.stat-card i {
  font-size: 30px;
  margin-top: 8px;
}

.stat-card h3 {
  font-size: 26px;
  margin: 0;
  position: absolute;
  bottom: 10px;
  right: 15px;
}

#userCard i {
  color: #e74c3c;
}

#technicianCard i {
  color: #27ae60;
}

#taskCard i {
  color: #2980b9;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}

th, td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

th {
  background-color: #f2f2f2;
}

/* กราฟ container */
#chartContainer {
  margin-top: 40px;
  max-width: 700px;
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

#chartContainer h2 {
  margin-bottom: 20px;
  color: #333;
  font-weight: 600;
}

.charts-wrapper {
  display: flex;
  gap: 30px;
  margin-top: 40px;
  flex-wrap: nowrap;
  justify-content: flex-start;
}

.chart-box {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  width: 600px;
  flex-shrink: 0;
}

.chart-box canvas {
  width: 100% !important;
  height: auto !important;
}


    </style>
</head>
<body>
    <div class="sidebar">
  <div class="logo">
    <h2>ระบบเเจ้งซ่อม</h2>
  </div>
  <ul>
  <li><a href="javascript:void(0);" id="goHome"><i class="fas fa-home"></i><span>หน้าหลัก</span></a></li>
  <li><a href="javascript:void(0);" id="viewRepairRequests"><i class="fas fa-tools"></i><span>ดูงานเเจ้งซ่อม</span></a></li>
  <li><a href="#" id="viewTechnicians"><i class="fas fa-user-cog"></i><span>ดูข้อมูลช่าง</span></a></li>
  <li><a href="#" id="viewUsers"><i class="fas fa-users"></i><span>ข้อมูลผู้ใช้</span></a></li>
  <li><a href="#"><i class="fas fa-sign-out-alt"></i><span>ออกจากระบบ</span></a></li>
</ul>


</div>


    <div class="main-content">
        <div class="top-bar">
            <span>ยินดีต้อนรับ, แอดมิน</span>
        </div>

        <div class="dashboard">
            <div class="stat-card" id="userCard">
                <p>จำนวนของผู้ใช้</p>
                <h3><?= $userCount ?></h3>
            </div>
            <div class="stat-card" id="technicianCard">
                <p>จำนวนของช่าง</p>
                <h3><?= $technicianCount ?></h3>
            </div>
            <div class="stat-card" id="taskCard">
                <p>จำนวนการแจ้งซ่อม</p>
                <h3><?= $taskCount ?></h3>
            </div>
        </div>

        <div class="charts-wrapper">
    <div class="chart-box" id="topTechnicianChartContainer">
        <h2>ช่างที่ถูกแจ้งซ่อมมากที่สุด (5 อันดับ)</h2>
        <canvas id="topTechnicianChart"></canvas>
    </div>
    <div class="chart-box" id="causeChartContainer">
        <h2>สาเหตุที่พบบ่อยที่สุด (5 อันดับ)</h2>
        <canvas id="causeChart"></canvas>
    </div>
</div>

        <div id="technicianList" style="display: none;">
            <table id="technicianTable">
                <thead>
                    <tr>
                      <th>ID</th>
                      <th>ชื่อ</th>
                      <th>อีเมล</th>
                      <th>ประเภทช่าง</th>
                      <th>ความเชี่ยวชาญ</th>
                      <th>ที่อยู่</th>
                      <th>เบอร์โทร</th>
                      <th>รูปโปรไฟล์</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div id="repairRequests" style="display: none;">
            <h2>งานแจ้งซ่อมทั้งหมด</h2>
            <table id="repairRequestsTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ไอดีผู้แจ้ง</th>
                    <th>ชื่อของช่าง</th>
                    <th>สาเหตุ</th>
                    <th>สถานะ</th>
                    <th>วันที่แจ้ง</th>
                    <th>ชื่อของผู้แจ้ง</th>
                    <th>รูปภาพ</th>
                </tr>
            </thead>
            <tbody id="repairRequestsBody"></tbody>
        </table>
        </div>

        <div id="userList" style="display: none;">
            <h2>ข้อมูลผู้ใช้</h2>
            <table id="userTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อเต็ม</th>
                        <th>อีเมล</th>
                        <th>รหัสผ่าน</th> 
                        <th>เบอร์โทร</th>  
                        <th>ที่อยู่</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // ข้อมูลช่างที่ถูกแจ้งซ่อมจาก PHP ไปเป็น JS
        const topTechnicians = <?= json_encode($topTechnicians) ?>;

        const ctx = document.getElementById('topTechnicianChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar', // แบบแท่งกราฟแนวตั้งเหมือนเดิม
            data: {
                labels: topTechnicians.map(t => t.name),
                datasets: [{
                    label: 'จำนวนงานแจ้งซ่อม',
                    data: topTechnicians.map(t => t.count),
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true },
                    datalabels: {
                        display: false // ไม่ใช้ datalabels แบบนี้
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 14, weight: 'bold' },
                            color: '#333'
                        },
                        grid: { color: '#eee' }
                    },
                    x: {
                        ticks: {
                            font: { size: 14, weight: 'bold' },
                            color: '#333'
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>

    <script src="admin.js"></script>
    <script>
       window.addEventListener('DOMContentLoaded', () => {
    const icons = {
        userCard: 'fas fa-users',
        technicianCard: 'fas fa-user-cog',
        taskCard: 'fas fa-tools'
    };

    for (const [id, iconClass] of Object.entries(icons)) {
        const card = document.getElementById(id);
        const title = card.querySelector('p');
        const icon = document.createElement('i');
        icon.className = iconClass;
        title.after(icon);

        // ซ่อนกราฟเมื่อคลิกการ์ดสถิติ
        card.addEventListener('click', () => {
            const topTechContainer = document.getElementById('topTechnicianChartContainer');
            const causeContainer = document.getElementById('causeChartContainer');

            if (topTechContainer) topTechContainer.style.display = 'none';
            if (causeContainer) causeContainer.style.display = 'none';
        });
    }

    // ซ่อนกราฟเมื่อคลิกปุ่มเมนูใน sidebar
    document.getElementById('viewRepairRequests').addEventListener('click', () => {
        document.getElementById('topTechnicianChartContainer').style.display = 'none';
        document.getElementById('causeChartContainer').style.display = 'none';
    });

    document.getElementById('viewTechnicians').addEventListener('click', () => {
        document.getElementById('topTechnicianChartContainer').style.display = 'none';
        document.getElementById('causeChartContainer').style.display = 'none';
    });

    document.getElementById('viewUsers').addEventListener('click', () => {
        document.getElementById('topTechnicianChartContainer').style.display = 'none';
        document.getElementById('causeChartContainer').style.display = 'none';
    });
});


        const commonCauses = <?= json_encode($commonCauses) ?>;
         // กราฟสาเหตุที่พบบ่อยที่สุด
        const ctxCause = document.getElementById('causeChart').getContext('2d');
        const colors = [
            'rgba(255, 99, 132, 0.7)',
            'rgba(54, 162, 235, 0.7)',
            'rgba(255, 206, 86, 0.7)',
            'rgba(75, 192, 192, 0.7)',
            'rgba(153, 102, 255, 0.7)'
        ];

        new Chart(ctxCause, {
            type: 'bar',
            data: {
                labels: commonCauses.map(c => c.problem_description),
                datasets: [{
                    label: 'จำนวนครั้ง',
                    data: commonCauses.map(c => c.count),
                    backgroundColor: colors,
                    borderColor: colors.map(c => c.replace('0.7', '1')),
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 14, weight: 'bold' },
                            color: '#333'
                        },
                        grid: { color: '#eee' }
                    },
                    x: {
                        ticks: {
                            font: { size: 14, weight: 'bold' },
                            color: '#333'
                        },
                        grid: { display: false }
                    }
                }
            }
        });
        document.getElementById('goHome').addEventListener('click', () => {
    // ซ่อนทุก section อื่น ๆ
    document.getElementById('repairRequests').style.display = 'none';
    document.getElementById('technicianList').style.display = 'none';
    document.getElementById('userList').style.display = 'none';

    // แสดง dashboard และกราฟ
    document.querySelector('.dashboard').style.display = 'flex';
    document.getElementById('topTechnicianChartContainer').style.display = 'block';
    document.getElementById('causeChartContainer').style.display = 'block';
});

    </script> 
</body>
</html>
