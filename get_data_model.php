<?php
header('Content-Type: application/json');

session_start();

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    $response = array(
        'success' => false,
        'message' => 'User not logged in'
    );
    echo json_encode($response);
    exit;
}

require_once 'config/database.php';

try {
   // Process GET request
    if($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["data_model"])) {
        $data_model = $_GET["data_model"];
        
        if($data_model == "accommodation"){

            // Filters
            $destination = isset($_GET['destination']) ? $_GET['destination'] : '';
            $hotel_name = isset($_GET['hotel_name']) ? $_GET['hotel_name'] : '';
            $room_category = isset($_GET['room_category']) ? $_GET['room_category'] : '';

            $sql = "SELECT * FROM accommodation_details WHERE validity_to < NOW()";
            $values = array();

            if(!empty($destination)) {
                $sql .= " AND destination = ?";
                $values[] = $destination;
            }

            if(!empty($hotel_name)) {
                $sql .= " AND hotel_name LIKE ?";
                $values[] = "%$hotel_name%";
            }

            if(!empty($room_category)) {
                $sql .= " AND room_category = ?";
                $values[] = $room_category;
            }

            if($sql_stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($sql_stmt, str_repeat('s', count($values)), ...$values);
                mysqli_stmt_execute($sql_stmt);
                $result = mysqli_stmt_get_result($sql_stmt);
                
                // Convert result to array
                $data = array();
                while($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }

                $response = array(
                    'success' => true,
                    'data' => $data
                );

                echo json_encode($response);
                mysqli_stmt_close($sql_stmt);
            }

        }
        else {
            $response = array(
                'success' => true,
                'data' => []
            );
            echo json_encode($response);
        }

    } else {
        $response = array(
            'success' => false,
            'message' => 'Invalid request'
        );
        echo json_encode($response);
    }

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

// Close connection
mysqli_close($conn);
?>
