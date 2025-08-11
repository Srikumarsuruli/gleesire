<?php
// Custom error handler for debugging
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    // Log the error
    error_log("Error [$errno] $errstr in $errfile on line $errline");
    
    // Only show errors if display_errors is on
    if (ini_get('display_errors')) {
        echo "<div style='background-color: #ffdddd; border: 1px solid #ff0000; padding: 10px; margin: 10px 0;'>";
        echo "<h3>PHP Error</h3>";
        echo "<p><strong>Type:</strong> $errno</p>";
        echo "<p><strong>Message:</strong> $errstr</p>";
        echo "<p><strong>File:</strong> $errfile</p>";
        echo "<p><strong>Line:</strong> $errline</p>";
        echo "</div>";
    }
    
    // Don't execute PHP's internal error handler
    return true;
}

// Set the custom error handler
set_error_handler("custom_error_handler");

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>