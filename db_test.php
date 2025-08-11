<?php
echo "Step 1: Starting database test<br>";

// Try to connect with error suppression
$conn = @mysqli_connect("localhost", "gleesire_leads", "Gleesire@123", "gleesire_leads");

if (!$conn) {
    echo "Step 2: Database connection FAILED<br>";
    echo "Error: " . mysqli_connect_error() . "<br>";
    
    // Try alternative connection
    echo "Trying alternative host...<br>";
    $conn = @mysqli_connect("127.0.0.1", "gleesire_leads", "Gleesire@123", "gleesire_leads");
    
    if (!$conn) {
        echo "Alternative connection also failed<br>";
        echo "Please check your database credentials in cPanel<br>";
    } else {
        echo "Alternative connection SUCCESS<br>";
    }
} else {
    echo "Step 2: Database connection SUCCESS<br>";
    
    // Test query
    $result = @mysqli_query($conn, "SELECT COUNT(*) as count FROM enquiries");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "Step 3: Query SUCCESS - Found " . $row['count'] . " enquiries<br>";
    } else {
        echo "Step 3: Query FAILED - " . mysqli_error($conn) . "<br>";
    }
}

echo "Test completed<br>";
?>