<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>File Upload Directory Check</h2>";

// Check uploads directory
$upload_dir = 'uploads';
if (!file_exists($upload_dir)) {
    echo "<p>Main uploads directory does not exist. Creating...</p>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p>Created directory: $upload_dir</p>";
    } else {
        echo "<p>Failed to create directory: $upload_dir</p>";
    }
} else {
    echo "<p>Main uploads directory exists: $upload_dir</p>";
    echo "<p>Directory is writable: " . (is_writable($upload_dir) ? "Yes" : "No") . "</p>";
}

// Check receipts directory
$receipts_dir = 'uploads/receipts';
if (!file_exists($receipts_dir)) {
    echo "<p>Receipts directory does not exist. Creating...</p>";
    if (mkdir($receipts_dir, 0777, true)) {
        echo "<p>Created directory: $receipts_dir</p>";
    } else {
        echo "<p>Failed to create directory: $receipts_dir</p>";
    }
} else {
    echo "<p>Receipts directory exists: $receipts_dir</p>";
    echo "<p>Directory is writable: " . (is_writable($receipts_dir) ? "Yes" : "No") . "</p>";
}

// Create a test file
$test_file = $receipts_dir . '/test_file.txt';
echo "<p>Attempting to create a test file: $test_file</p>";
if (file_put_contents($test_file, 'This is a test file to check write permissions.')) {
    echo "<p>Test file created successfully.</p>";
    
    // Try to read the file
    echo "<p>Attempting to read the test file...</p>";
    $content = file_get_contents($test_file);
    if ($content !== false) {
        echo "<p>File read successfully. Content: " . htmlspecialchars($content) . "</p>";
    } else {
        echo "<p>Failed to read the test file.</p>";
    }
    
    // Delete the test file
    if (unlink($test_file)) {
        echo "<p>Test file deleted successfully.</p>";
    } else {
        echo "<p>Failed to delete the test file.</p>";
    }
} else {
    echo "<p>Failed to create the test file.</p>";
}

// Check PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>max_file_uploads: " . ini_get('max_file_uploads') . "</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . " seconds</p>";

// Check if GD is installed (for image processing)
echo "<p>GD installed: " . (extension_loaded('gd') ? "Yes" : "No") . "</p>";

echo "<h2>Server Information</h2>";
echo "<p>Server software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";

echo "<p><a href='view_payment_receipts.php'>Return to Payment Receipts</a></p>";
?>