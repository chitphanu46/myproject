<?php
// search_technicians.php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "home";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$q = $_GET['q'] ?? '';
$sql = "SELECT id, name, technician_type FROM technicians WHERE name LIKE ? OR technician_type LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$like = "%$q%";
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
header('Content-Type: application/json');
echo json_encode($data);
?>
