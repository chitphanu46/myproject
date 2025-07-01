<?php
$conn = new mysqli('localhost', 'root', '', 'home');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT COUNT(*) AS count FROM technicians");
$row = $result->fetch_assoc();
echo "Technicians: " . $row['count'] . "<br>";

$result = $conn->query("SELECT COUNT(*) AS count FROM repair_requests");
$row = $result->fetch_assoc();
echo "Repair Requests: " . $row['count'] . "<br>";

$result = $conn->query("SELECT COUNT(*) AS count FROM users");
$row = $result->fetch_assoc();
echo "Users: " . $row['count'] . "<br>";
?>
