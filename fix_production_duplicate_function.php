<?php
// This script is designed to fix the duplicate function declaration issue on the production server

// Check if we're in a web context
$isWeb = isset($_SERVER['REQUEST_METHOD']);
if ($isWeb) {
    header('Content-Type: text/plain');
}

// Function to output messages
function output($message) {
    global $isWeb;
    echo $message . ($isWeb ? "\n" : PHP_EOL);
}

output("Starting fix for duplicate function declaration...");

// Define the path to header.php
$headerPath = __DIR__ . '/includes/header.php';
$headerBackupPath = __DIR__ . '/includes/header.php.bak';

// Check if the file exists
if (!file_exists($headerPath)) {
    output("Error: header.php not found at {$headerPath}");
    exit(1);
}

// Create a backup of the original file
if (!copy($headerPath, $headerBackupPath)) {
    output("Warning: Could not create backup of header.php");
} else {
    output("Backup created at {$headerBackupPath}");
}

// Read the file content
$content = file_get_contents($headerPath);
if ($content === false) {
    output("Error: Could not read header.php");
    exit(1);
}

// Check if the file contains the isAdmin function
if (strpos($content, "function isAdmin()") !== false) {
    output("Found isAdmin() function in header.php. Removing...");
    
    // Remove the function definition
    $pattern = '/function\s+isAdmin\s*\(\s*\)\s*\{[^}]*\}/';
    $newContent = preg_replace($pattern, '// Function isAdmin() moved to functions.php', $content);
    
    // Write the updated content back to the file
    if (file_put_contents($headerPath, $newContent)) {
        output("Successfully removed isAdmin() function from header.php");
    } else {
        output("Error: Could not write to header.php");
        exit(1);
    }
} else {
    output("isAdmin() function not found in header.php");
    
    // Check for duplicate includes of functions.php
    $matches = [];
    preg_match_all('/require_once\s+[\'"]includes\/functions\.php[\'"]\s*;/', $content, $matches);
    
    if (count($matches[0]) > 1) {
        output("Found multiple includes of functions.php. Fixing...");
        
        // Keep only the first include
        $newContent = preg_replace('/require_once\s+[\'"]includes\/functions\.php[\'"]\s*;/', '// Duplicate include removed', $content, 1);
        
        // Write the updated content back to the file
        if (file_put_contents($headerPath, $newContent)) {
            output("Successfully removed duplicate include from header.php");
        } else {
            output("Error: Could not write to header.php");
            exit(1);
        }
    } else {
        output("No duplicate includes found in header.php");
        output("The issue might be in another file that's being included.");
    }
}

output("Fix completed. Please refresh your application.");

if ($isWeb) {
    echo "\n<a href='debug.php'>Return to Debug Page</a>";
}
?>