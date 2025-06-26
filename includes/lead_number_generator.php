<?php
function generateLeadNumber($conn) {
    $year = date('Y');
    $month = date('m');
    $day = date('d');
    
    // Start transaction to ensure sequence integrity
    mysqli_begin_transaction($conn);
    
    try {
        // Check if we have an entry for this month
        $check_sql = "SELECT * FROM lead_sequence WHERE year = $year AND month = $month FOR UPDATE";
        $result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($result) == 0) {
            // First lead of the month - create new sequence starting at 1
            $insert_sql = "INSERT INTO lead_sequence (year, month, last_sequence) VALUES ($year, $month, 1)";
            mysqli_query($conn, $insert_sql);
            $next_seq = 1;
        } else {
            // Increment the sequence
            $row = mysqli_fetch_assoc($result);
            $next_seq = $row['last_sequence'] + 1;
            
            // Update the sequence
            $update_sql = "UPDATE lead_sequence SET last_sequence = $next_seq WHERE year = $year AND month = $month";
            mysqli_query($conn, $update_sql);
        }
        
        // Commit the transaction
        mysqli_commit($conn);
        
        // Format with leading zeros
        $seq_formatted = sprintf('%04d', $next_seq);
        
        return "LGH-$year/$month/$day/$seq_formatted";
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        
        // Fallback to a timestamp-based number if there's an error
        return "LGH-$year/$month/$day/" . sprintf('%04d', 1);
    }
}
?>