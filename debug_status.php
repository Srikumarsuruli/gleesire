<?php
// Include database connection
require_once "includes/db_connect.php";

// Get all lead status mappings
$sql = "SELECT e.id, e.lead_number, e.customer_name, lsm.status_name 
        FROM enquiries e 
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        WHERE e.status_id = 3
        ORDER BY e.id";

$result = mysqli_query($conn, $sql);

echo "<h2>Lead Status Debug</h2>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Lead Number</th><th>Customer</th><th>Status</th></tr>";

while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['lead_number'] . "</td>";
    echo "<td>" . $row['customer_name'] . "</td>";
    echo "<td>" . ($row['status_name'] ? htmlspecialchars($row['status_name']) : 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Show the SQL query used in view_leads.php
echo "<h2>SQL Query in view_leads.php</h2>";
echo "<pre>";
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name, ls.name as status_name,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.booking_confirmed,
        lsm.status_name as custom_status
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        JOIN lead_status ls ON e.status_id = ls.id 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        WHERE e.status_id = 3 AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)";
echo htmlspecialchars($sql);
echo "</pre>";
?>