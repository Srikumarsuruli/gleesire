<?php
require_once "includes/header.php";

// Debug query to check converted enquiries
$debug_sql = "SELECT e.id, e.lead_number, e.customer_name, e.status_id, ls.name as status_name 
              FROM enquiries e 
              LEFT JOIN lead_status ls ON (e.status_id = ls.id OR e.status_id = ls.name)
              WHERE e.status_id = 'Converted' OR e.status_id = 3 OR ls.name = 'Converted'";

$result = mysqli_query($conn, $debug_sql);

echo "<h3>Debug: Converted Enquiries</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Lead Number</th><th>Customer</th><th>Status ID</th><th>Status Name</th></tr>";

while($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['lead_number'] . "</td>";
    echo "<td>" . $row['customer_name'] . "</td>";
    echo "<td>" . $row['status_id'] . "</td>";
    echo "<td>" . $row['status_name'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check converted_leads table
$cl_sql = "SELECT * FROM converted_leads";
$cl_result = mysqli_query($conn, $cl_sql);

echo "<h3>Debug: Converted Leads Table</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Enquiry ID</th><th>Enquiry Number</th></tr>";

while($row = mysqli_fetch_assoc($cl_result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['enquiry_id'] . "</td>";
    echo "<td>" . $row['enquiry_number'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>