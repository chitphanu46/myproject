<?php
$conn = new mysqli("localhost", "root", "", "home");
header('Content-Type: application/json');

$result = $conn->query("SELECT * FROM technicians");
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "total_technicians" => count($data),
    "technicians" => $data
]);
?>
