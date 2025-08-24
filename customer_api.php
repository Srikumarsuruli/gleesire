<?php
$ACCESS_KEY = "MY_STATIC_KEY_123";

header("Content-Type: application/json");

$headers = getallheaders();
if (!isset($headers['Access-Key']) || $headers['Access-Key'] !== $ACCESS_KEY) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid Access Key"]);
    exit;
}

$input = file_get_contents("php://input");
$data  = json_decode($input, true);

if (!$data || !isset($data['customers']) || !is_array($data['customers'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid input data"]);
    exit;
}

require_once 'config/database.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB Connection failed"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO customers (name, phone, destination, time) VALUES (?, ?, ?, ?)");

$successCount = 0;
$failCount = 0;
foreach ($data['customers'] as $cust) {
    if (!isset($cust['name'], $cust['phone'], $cust['destination'], $cust['time'])) {
        $failCount++;
        continue;
    }

    $stmt->bind_param("ssss", $cust['name'], $cust['phone'], $cust['destination'], $cust['time']);

    if ($stmt->execute()) {
        $successCount++;
    } else {
        $failCount++;
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    "status" => "success",
    "message" => "Processed customers",
    "inserted" => $successCount,
    "failed" => $failCount
]);
?>
