<?php
// Fix for master search issues: Missing Night/Day field and invalid travel month date
require_once "config/database.php";

echo "<h2>Fixing Master Search Issues</h2>";

// 1. Check and add night_day column if it doesn't exist
echo "<h3>1. Checking night_day column in converted_leads table...</h3>";

$check_column = mysqli_query($conn, "SHOW COLUMNS FROM converted_leads LIKE 'night_day'");
if(mysqli_num_rows($check_column) == 0) {
    echo "Adding night_day column...<br>";
    $add_column = "ALTER TABLE converted_leads ADD COLUMN night_day VARCHAR(20) NULL";
    if(mysqli_query($conn, $add_column)) {
        echo "✓ night_day column added successfully<br>";
    } else {
        echo "✗ Error adding night_day column: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✓ night_day column already exists<br>";
}

// 2. Fix travel_month column to be VARCHAR instead of DATE
echo "<h3>2. Fixing travel_month column format...</h3>";

$check_travel_month = mysqli_query($conn, "SHOW COLUMNS FROM converted_leads LIKE 'travel_month'");
if($check_travel_month && $row = mysqli_fetch_assoc($check_travel_month)) {
    echo "Current travel_month column type: " . $row['Type'] . "<br>";
    
    if(strpos(strtolower($row['Type']), 'date') !== false) {
        echo "Converting travel_month from DATE to VARCHAR...<br>";
        $alter_column = "ALTER TABLE converted_leads MODIFY COLUMN travel_month VARCHAR(20)";
        if(mysqli_query($conn, $alter_column)) {
            echo "✓ travel_month column converted to VARCHAR successfully<br>";
        } else {
            echo "✗ Error converting travel_month column: " . mysqli_error($conn) . "<br>";
        }
    } else {
        echo "✓ travel_month column is already VARCHAR<br>";
    }
}

// 3. Check if night_day table exists for reference data
echo "<h3>3. Checking night_day reference table...</h3>";

$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'night_day'");
if(mysqli_num_rows($check_table) == 0) {
    echo "Creating night_day reference table...<br>";
    $create_table = "CREATE TABLE night_day (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if(mysqli_query($conn, $create_table)) {
        echo "✓ night_day table created successfully<br>";
        
        // Insert some default values
        $default_values = [
            "1 Night 2 Days",
            "2 Nights 3 Days", 
            "3 Nights 4 Days",
            "4 Nights 5 Days",
            "5 Nights 6 Days",
            "6 Nights 7 Days",
            "7 Nights 8 Days",
            "8 Nights 9 Days",
            "9 Nights 10 Days",
            "10 Nights 11 Days"
        ];
        
        foreach($default_values as $value) {
            $insert = "INSERT INTO night_day (name) VALUES ('$value')";
            mysqli_query($conn, $insert);
        }
        echo "✓ Default night_day values inserted<br>";
    } else {
        echo "✗ Error creating night_day table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✓ night_day table already exists<br>";
}

// 4. Test the search functionality
echo "<h3>4. Testing search functionality...</h3>";

$test_query = "SELECT cl.*, e.lead_number, e.customer_name 
               FROM converted_leads cl 
               JOIN enquiries e ON cl.enquiry_id = e.id 
               LIMIT 1";
$test_result = mysqli_query($conn, $test_query);

if($test_result && mysqli_num_rows($test_result) > 0) {
    $test_row = mysqli_fetch_assoc($test_result);
    echo "✓ Found test record: Lead " . $test_row['lead_number'] . " - " . $test_row['customer_name'] . "<br>";
    echo "Night/Day value: " . ($test_row['night_day'] ?: 'NULL') . "<br>";
    echo "Travel Month value: " . ($test_row['travel_month'] ?: 'NULL') . "<br>";
} else {
    echo "⚠ No converted leads found for testing<br>";
}

// 5. Show current table structure
echo "<h3>5. Current converted_leads table structure:</h3>";
$structure = mysqli_query($conn, "DESCRIBE converted_leads");
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while($row = mysqli_fetch_assoc($structure)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>✅ Fix completed!</h3>";
echo "<p>The master search should now properly display:</p>";
echo "<ul>";
echo "<li>✓ Night/Day field from converted_leads table</li>";
echo "<li>✓ Travel month as text (not invalid date)</li>";
echo "</ul>";

mysqli_close($conn);
?>