<?php
function generateNumber($type, $conn, $preview = false) {
    $year = date('Y');
    $month = date('m');
    
    // Define prefixes
    $prefixes = [
        'enquiry' => 'GHE',
        'lead' => 'GHL', 
        'file' => 'GHF',
        'cost_sheet' => 'GHL'
    ];
    
    $prefix = $prefixes[$type];
    
    // Create table to track numbers if it doesn't exist
    $table_sql = "CREATE TABLE IF NOT EXISTS number_sequences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(20) NOT NULL,
        year INT NOT NULL,
        month INT NOT NULL,
        last_number INT NOT NULL DEFAULT 0,
        UNIQUE KEY unique_sequence (type, year, month)
    )";
    mysqli_query($conn, $table_sql);
    
    // Get current sequence for this type/year/month
    $check_sql = "SELECT last_number FROM number_sequences WHERE type = ? AND year = ? AND month = ?";
    $stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt, "sii", $type, $year, $month);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $next_number = $row['last_number'] + 1;
        
        if (!$preview) {
            // Update existing sequence only if not preview
            $update_sql = "UPDATE number_sequences SET last_number = ? WHERE type = ? AND year = ? AND month = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "isii", $next_number, $type, $year, $month);
            mysqli_stmt_execute($update_stmt);
        }
    } else {
        $next_number = 1;
        
        if (!$preview) {
            // Create new sequence only if not preview
            $insert_sql = "INSERT INTO number_sequences (type, year, month, last_number) VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_sql);
            mysqli_stmt_bind_param($insert_stmt, "siii", $type, $year, $month, $next_number);
            mysqli_stmt_execute($insert_stmt);
        }
    }
    
    // Format the number
    $formatted_number = sprintf("%04d", $next_number);
    
    if ($type == 'cost_sheet') {
        return $prefix . '/' . $year . '/' . $month . '/' . $formatted_number . '-S1';
    } else {
        return $prefix . '/' . $year . '/' . $month . '/' . $formatted_number;
    }
}
?>