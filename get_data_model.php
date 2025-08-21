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
        elseif($data_model == "transportation"){

            // Filters
            $destination = isset($_GET['destination']) ? $_GET['destination'] : '';
            $company_name = isset($_GET['company_name']) ? $_GET['company_name'] : '';
            $vehicle = isset($_GET['vehicle']) ? $_GET['vehicle'] : '';

            $sql = "SELECT * FROM transport_details WHERE status = 'Active'";
            $values = array();

            if(!empty($destination)) {
                $sql .= " AND destination = ?";
                $values[] = $destination;
            }

            if(!empty($company_name)) {
                $sql .= " AND company_name LIKE ?";
                $values[] = "%$company_name%";
            }

            if(!empty($vehicle)) {
                $sql .= " AND vehicle = ?";
                $values[] = $vehicle;
            }

            if($sql_stmt = mysqli_prepare($conn, $sql)) {
                if(count($values) > 0) {
                    mysqli_stmt_bind_param($sql_stmt, str_repeat('s', count($values)), ...$values);
                }
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
        elseif($data_model == "agent_package"){

            // Filters
            $destination = isset($_GET['destination']) ? $_GET['destination'] : '';
            $supplier = isset($_GET['supplier']) ? $_GET['supplier'] : '';

            $sql = "SELECT * FROM travel_agents WHERE status = 'Active'";
            $values = array();

            if(!empty($destination)) {
                $sql .= " AND destination = ?";
                $values[] = $destination;
            }

            if(!empty($supplier)) {
                $sql .= " AND supplier LIKE ?";
                $values[] = "%$supplier%";
            }

            if($sql_stmt = mysqli_prepare($conn, $sql)) {
                if(count($values) > 0) {
                    mysqli_stmt_bind_param($sql_stmt, str_repeat('s', count($values)), ...$values);
                }
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
        elseif($data_model == "medical_tourism"){

            // Filters
            $destination = isset($_GET['destination']) ? $_GET['destination'] : '';
            $hospital_name = isset($_GET['hospital_name']) ? $_GET['hospital_name'] : '';

            $sql = "SELECT * FROM hospital_details WHERE status = 'Active'";
            $values = array();

            if(!empty($destination)) {
                $sql .= " AND destination = ?";
                $values[] = $destination;
            }

            if(!empty($hospital_name)) {
                $sql .= " AND hospital_name LIKE ?";
                $values[] = "%$hospital_name%";
            }

            if($sql_stmt = mysqli_prepare($conn, $sql)) {
                if(count($values) > 0) {
                    mysqli_stmt_bind_param($sql_stmt, str_repeat('s', count($values)), ...$values);
                }
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
