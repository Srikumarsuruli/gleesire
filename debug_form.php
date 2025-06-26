<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Form Data Debug</h2>";

echo "<h3>POST Data:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>Status ID Check:</h3>";
if(isset($_POST["status_id"]) && $_POST["status_id"] == 3) {
    echo "Status is set to Converted (ID: 3)";
    
    echo "<h3>Converted Lead Fields:</h3>";
    $fields = [
        "lead_type", "customer_location", "secondary_contact", 
        "destination_id", "other_details", "travel_month",
        "travel_start_date", "travel_end_date", "adults_count",
        "children_count", "infants_count", "customer_available_timing",
        "file_manager_id"
    ];
    
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Value</th><th>Empty?</th></tr>";
    
    foreach($fields as $field) {
        $value = isset($_POST[$field]) ? $_POST[$field] : "Not set";
        $is_empty = empty($_POST[$field]) ? "Yes" : "No";
        
        echo "<tr>";
        echo "<td>$field</td>";
        echo "<td>$value</td>";
        echo "<td>$is_empty</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "Status is NOT set to Converted";
}
?>