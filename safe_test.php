<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "Step 1: Starting...<br>";
    
    $conn = mysqli_connect("localhost", "gleesire_leads", "Gleesire@123", "gleesire_leads");
    if (!$conn) {
        throw new Exception("Connection failed");
    }
    echo "Step 2: Connected<br>";
    
    $enquiry_id = 0;
    if (isset($_GET['id'])) {
        $enquiry_id = intval($_GET['id']);
    }
    echo "Step 3: ID = " . $enquiry_id . "<br>";
    
    if ($enquiry_id > 0) {
        $sql = "SELECT customer_name FROM enquiries WHERE id = " . $enquiry_id;
        echo "Step 4: Query = " . $sql . "<br>";
        
        $result = mysqli_query($conn, $sql);
        if ($result) {
            echo "Step 5: Query executed<br>";
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                echo "Step 6: Customer = " . $row['customer_name'] . "<br>";
            } else {
                echo "Step 6: No customer found<br>";
            }
        } else {
            echo "Step 5: Query failed<br>";
        }
    }
    
    echo "Step 7: All done<br>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}
?>
<html>
<body>
<h1>Safe Test Page</h1>
<p>This is the HTML part</p>
<p><a href="view_leads.php">Back</a></p>
</body>
</html>