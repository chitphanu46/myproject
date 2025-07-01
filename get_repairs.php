<?php
$conn = new mysqli("localhost", "root", "", "home");
header('Content-Type: application/json');

$sql = "SELECT r.*, t.name AS technician_name, u.full_name AS user_name
        FROM repair_requests r
        LEFT JOIN technicians t ON r.technician_id = t.id
        LEFT JOIN users u ON r.user_id = u.id";

$result = $conn->query($sql);

$data = [
    "total_repairs" => 0,
    "repairs" => []
];

while ($row = $result->fetch_assoc()) {
    $data["repairs"][] = $row;
}
$data["total_repairs"] = count($data["repairs"]);

echo json_encode($data);
?>
