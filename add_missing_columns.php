<?php
require_once "config/database.php";

// Add social_media_link column if it doesn't exist
$add_social_media_sql = "ALTER TABLE enquiries ADD COLUMN social_media_link VARCHAR(255) NULL";
@mysqli_query($conn, $add_social_media_sql);

// Add email column if it doesn't exist  
$add_email_sql = "ALTER TABLE enquiries ADD COLUMN email VARCHAR(255) NULL";
@mysqli_query($conn, $add_email_sql);

echo "Columns added successfully (if they didn't exist already)";
?>