<?php
$ACCESS_KEY = "gls_abac0e85b30e648f36d058898463fabb98fd7d57bcca83795c4e2170";

header("Content-Type: application/json");

// Fallback for getallheaders (in case PHP-FPM on Nginx/Windows)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

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
require_once "includes/number_generator.php";
require_once "config/timezone.php";

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB Connection failed"]);
    exit;
}

// Prepare once, reuse in loop
$enquirie_stmt = $conn->prepare("
    INSERT INTO enquiries 
    (lead_number, received_datetime, attended_by, department_id, source_id, ad_campaign_id, referral_code, customer_name, mobile_number, social_media_link, email, status_id, enquiry_type) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt = $conn->prepare("
    INSERT INTO customers 
    (name, phone, destination, time, channel, social_media_link, email, converted, enquiry_id) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$successCount = 0;
$failCount = 0;

foreach ($data['customers'] as $cust) {
    // Validate required fields
    if (!isset($cust['name'], $cust['phone'], $cust['destination'], $cust['time'])) {
        $failCount++;
        continue;
    }

    // Customer data
    $name = $cust['name'];
    $phone = $cust['phone'];
    $destination = $cust['destination'];
    $time = $cust['time'];
    $channel = $cust['channel'] ?? '';
    $social_media_link = $cust['social_media_link'] ?? '';
    $email = $cust['email'] ?? '';

    // Default enquiry values
    $attended_by = 1;
    $department_id = 6;
    $ad_campaign_id = 9;
    $source_id = 0;
    $enquiry_type = "Chatbot";
    $Status_id = 4;
    $converted = 0;
    $referral_code = "CHATBOT";
    $enquiry_id = null;

    

    // Map channel to source_id
    if ($channel === 'WhatsApp') {
        $source_id = 14;
    } elseif ($channel === 'Instagram') {
        $source_id = 4;
    } elseif ($channel === 'WebChat') {
        $source_id = 13;
    }

    // Generate enquiry
    $enquiry_number = generateNumber('enquiry', $conn);
    $received_datetime = date('Y-m-d H:i:s');

    $enquirie_stmt->bind_param(
        "ssiiiissssiss",
        $enquiry_number,
        $received_datetime,
        $attended_by,
        $department_id,
        $source_id,
        $ad_campaign_id,
        $referral_code,
        $name,
        $phone,
        $social_media_link,
        $email,
        $Status_id,
        $enquiry_type
    );
    if ($enquirie_stmt->execute()) {
        $enquiry_id = $enquirie_stmt->insert_id;
        $converted = 1;
    }


    // Insert into customers
    $stmt->bind_param(
        "sssssssii",
        $name,
        $phone,
        $destination,
        $time,
        $channel,
        $social_media_link,
        $email,
        $converted,
        $enquiry_id
    );

    if ($stmt->execute()) {
        $successCount++;
    } else {
        $failCount++;
    }
}

$enquirie_stmt->close();
$stmt->close();
$conn->close();

echo json_encode([
    "status" => "success",
    "message" => "Processed customers",
    "inserted" => $successCount,
    "failed" => $failCount
]);
?>
