<?php
// Include database connection and number generator
require_once "config/database.php";
require_once "includes/number_generator.php";

echo "<h3>Updating Numbering System</h3>";

// First, run the table update
echo "<p>Adding cost_sheet_number column...</p>";
$sql = "ALTER TABLE cost_sheets ADD COLUMN cost_sheet_number VARCHAR(50) AFTER enquiry_number";
if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>Column cost_sheet_number added successfully!</p>";
} else {
    if (strpos(mysqli_error($conn), 'Duplicate column name') !== false) {
        echo "<p style='color: orange;'>Column cost_sheet_number already exists.</p>";
    } else {
        echo "<p style='color: red;'>Error adding column: " . mysqli_error($conn) . "</p>";
    }
}

// Add lead_number column to enquiries table if it doesn't exist
echo "<p>Adding lead_number column to enquiries...</p>";
$sql = "ALTER TABLE enquiries ADD COLUMN lead_number VARCHAR(50) AFTER lead_number";
if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>Column lead_number added successfully!</p>";
} else {
    if (strpos(mysqli_error($conn), 'Duplicate column name') !== false) {
        echo "<p style='color: orange;'>Column lead_number already exists.</p>";
    } else {
        echo "<p style='color: red;'>Error adding column: " . mysqli_error($conn) . "</p>";
    }
}

// Add file_number column to converted_leads table if it doesn't exist
echo "<p>Adding file_number column to converted_leads...</p>";
$sql = "ALTER TABLE converted_leads ADD COLUMN file_number VARCHAR(50) AFTER enquiry_number";
if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>Column file_number added successfully!</p>";
} else {
    if (strpos(mysqli_error($conn), 'Duplicate column name') !== false) {
        echo "<p style='color: orange;'>Column file_number already exists.</p>";
    } else {
        echo "<p style='color: red;'>Error adding column: " . mysqli_error($conn) . "</p>";
    }
}

echo "<h4>Numbering Format:</h4>";
echo "<ul>";
echo "<li>ENQUIRY NUMBER: GHE/YYYY/MM/0001</li>";
echo "<li>LEAD NUMBER: GHL/YYYY/MM/0001</li>";
echo "<li>COST SHEET NUMBER: GHL/YYYY/MM/0001-S1</li>";
echo "<li>FILE NUMBER: GHF/YYYY/MM/0001</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> New records will automatically use the new numbering format. Numbers restart at 0001 each month.</p>";

echo "<p><a href='view_cost_sheets.php'>Back to Cost Sheets</a></p>";
?>