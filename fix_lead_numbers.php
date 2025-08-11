<?php
require_once "config/database.php";

echo "<h2>Fixing Lead Number Prefixes</h2>";

// Update converted_leads table
$sql1 = "UPDATE converted_leads SET enquiry_number = REPLACE(enquiry_number, 'LGH-', 'GHL/') WHERE enquiry_number LIKE 'LGH-%'";
$result1 = mysqli_query($conn, $sql1);

if($result1) {
    $affected1 = mysqli_affected_rows($conn);
    echo "✅ Updated $affected1 records in converted_leads table<br>";
} else {
    echo "❌ Error updating converted_leads: " . mysqli_error($conn) . "<br>";
}

// Update enquiries table if it has lead numbers
$sql2 = "UPDATE enquiries SET lead_number = REPLACE(lead_number, 'LGH-', 'GHL/') WHERE lead_number LIKE 'LGH-%'";
$result2 = mysqli_query($conn, $sql2);

if($result2) {
    $affected2 = mysqli_affected_rows($conn);
    echo "✅ Updated $affected2 records in enquiries table<br>";
} else {
    echo "❌ Error updating enquiries: " . mysqli_error($conn) . "<br>";
}

// Check for any other tables that might have lead numbers
$tables_to_check = ['comments', 'cost_sheets', 'payment_receipts'];

foreach($tables_to_check as $table) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if(mysqli_num_rows($check_table) > 0) {
        $columns = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '%lead%' OR SHOW COLUMNS FROM $table LIKE '%enquiry_number%'");
        if(mysqli_num_rows($columns) > 0) {
            while($column = mysqli_fetch_assoc($columns)) {
                $col_name = $column['Field'];
                $update_sql = "UPDATE $table SET $col_name = REPLACE($col_name, 'LGH-', 'GHL/') WHERE $col_name LIKE 'LGH-%'";
                $update_result = mysqli_query($conn, $update_sql);
                if($update_result) {
                    $affected = mysqli_affected_rows($conn);
                    if($affected > 0) {
                        echo "✅ Updated $affected records in $table.$col_name<br>";
                    }
                }
            }
        }
    }
}

echo "<br><strong>✅ Lead number prefix update completed!</strong><br>";
echo "All future leads will now use GHL/ prefix format: GHL/2025/08/01/8155<br>";
echo "<br><a href='view_leads.php'>Check Updated Leads</a>";
?>