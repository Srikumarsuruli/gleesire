<?php
require_once "config/database.php";

$sql = "CREATE TABLE IF NOT EXISTS night_day (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($conn, $sql)) {
    echo "Night/Day table created successfully<br>";
    
    // Insert the predefined night/day values
    $night_day_values = [
        '1N/2D',
        '2N/3D',
        '3N/4D',
        '4N/5D',
        '5N/6D',
        '6N/7D',
        '7N/8D',
        '8N/9D',
        '9N/10D',
        '10N/11D',
        '11N/12D',
        '12N/13D',
        '13N/14D',
        '14N/15D'
    ];
    
    $insert_sql = "INSERT INTO night_day (name) VALUES (?)";
    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
        foreach ($night_day_values as $value) {
            mysqli_stmt_bind_param($stmt, "s", $value);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
        echo "Night/Day data inserted successfully";
    }
} else {
    echo "Error creating table: " . mysqli_error($conn);
}
?>