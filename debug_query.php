<?php
require_once "includes/header.php";

// Test the exact query from view_leads.php
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name, ls.name as status_name,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.booking_confirmed,
        lsm.status_name as lead_status
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        LEFT JOIN lead_status ls ON e.status_id = ls.id
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        LEFT JOIN comments c ON e.id = c.enquiry_id
        WHERE e.status_id = 3 AND cl.enquiry_id IS NOT NULL AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)
        GROUP BY e.id ORDER BY e.received_datetime DESC";

echo "<h3>Debug: Full Query Result</h3>";
echo "<p>Query: " . htmlspecialchars($sql) . "</p>";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo "<p style='color:red'>Query Error: " . mysqli_error($conn) . "</p>";
} else {
    echo "<p>Rows found: " . mysqli_num_rows($result) . "</p>";
    
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Lead Number</th><th>Customer</th><th>Enquiry Number</th><th>Attended By</th></tr>";
        
        while($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['lead_number'] . "</td>";
            echo "<td>" . $row['customer_name'] . "</td>";
            echo "<td>" . $row['enquiry_number'] . "</td>";
            echo "<td>" . $row['attended_by_name'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No results found</p>";
    }
}
?>