<?php
require_once "config/database.php";

echo "<h2>Running Master Search Fix</h2>";

$queries = [
    "ALTER TABLE converted_leads ADD COLUMN night_day VARCHAR(20) NULL",
    "ALTER TABLE converted_leads MODIFY COLUMN travel_month VARCHAR(20)",
    "CREATE TABLE IF NOT EXISTS night_day (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "INSERT IGNORE INTO night_day (name) VALUES 
    ('1 Night 2 Days'),
    ('2 Nights 3 Days'),
    ('3 Nights 4 Days'),
    ('4 Nights 5 Days'),
    ('5 Nights 6 Days'),
    ('6 Nights 7 Days'),
    ('7 Nights 8 Days'),
    ('8 Nights 9 Days'),
    ('9 Nights 10 Days'),
    ('10 Nights 11 Days')"
];

foreach($queries as $i => $query) {
    echo "Step " . ($i + 1) . ": ";
    if(mysqli_query($conn, $query)) {
        echo "✓ Success<br>";
    } else {
        $error = mysqli_error($conn);
        if(strpos($error, 'Duplicate column') !== false) {
            echo "✓ Column already exists<br>";
        } else {
            echo "✗ Error: " . $error . "<br>";
        }
    }
}

echo "<h3>✅ Fix Complete!</h3>";
echo "<p>Master search should now show Night/Day field and proper travel month.</p>";

mysqli_close($conn);
?>