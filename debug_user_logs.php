<?php
require_once "config/database.php";

echo "<h2>Debug User Logs Query</h2>";

$filter = $_GET['filter'] ?? 'today';
$user_filter = $_GET['user'] ?? '';

echo "Filter: $filter<br>";
echo "User filter: $user_filter<br>";

// Get date range based on filter
$where_date = "";
switch($filter) {
    case 'today':
        $where_date = "DATE(login_time) = CURDATE()";
        break;
    case 'week':
        $where_date = "login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $where_date = "login_time >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        break;
    case 'year':
        $where_date = "login_time >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        break;
    default:
        $where_date = "1=1";
}

echo "Where clause: $where_date<br>";

// Test the query
$sql = "SELECT l.*, u.username 
        FROM user_login_logs l 
        JOIN users u ON l.user_id = u.id 
        WHERE $where_date";

if($user_filter) {
    $sql .= " AND l.user_id = " . intval($user_filter);
}

$sql .= " ORDER BY l.login_time DESC";

echo "<h3>SQL Query:</h3>";
echo "<pre>$sql</pre>";

$result = mysqli_query($conn, $sql);

if(!$result) {
    echo "❌ Query error: " . mysqli_error($conn) . "<br>";
} else {
    echo "✅ Query executed successfully<br>";
    echo "Rows found: " . mysqli_num_rows($result) . "<br>";
    
    if(mysqli_num_rows($result) > 0) {
        echo "<h3>Results:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Login Time</th><th>Logout Time</th><th>Duration</th></tr>";
        while($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . $row['login_time'] . "</td>";
            echo "<td>" . ($row['logout_time'] ?? 'Active') . "</td>";
            echo "<td>" . $row['session_duration'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

// Check raw data
echo "<h3>Raw table data:</h3>";
$raw_sql = "SELECT l.*, u.username FROM user_login_logs l JOIN users u ON l.user_id = u.id ORDER BY l.login_time DESC LIMIT 10";
$raw_result = mysqli_query($conn, $raw_sql);

if($raw_result && mysqli_num_rows($raw_result) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Username</th><th>Login Time</th><th>Date</th><th>Logout Time</th></tr>";
    while($row = mysqli_fetch_assoc($raw_result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['login_time'] . "</td>";
        echo "<td>" . $row['date'] . "</td>";
        echo "<td>" . ($row['logout_time'] ?? 'Active') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No raw data found<br>";
}

echo "<br>Current date: " . date('Y-m-d') . "<br>";
echo "Current datetime: " . date('Y-m-d H:i:s') . "<br>";
?>