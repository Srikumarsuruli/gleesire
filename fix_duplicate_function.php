<?php
// Include database connection
require_once "config/database.php";

// Check if session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if a file contains a specific function
function fileContainsFunction($filePath, $functionName) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    return strpos($content, "function $functionName(") !== false;
}

// Files to check
$files = [
    "includes/header.php",
    "includes/header_deskapp.php",
    "includes/functions.php"
];

echo "<h1>Fixing Duplicate Function Declaration</h1>";
echo "<pre>";

// Check which files contain the isAdmin function
$containingFiles = [];
foreach ($files as $file) {
    if (fileContainsFunction($file, "isAdmin")) {
        $containingFiles[] = $file;
        echo "Function isAdmin() found in: $file\n";
    }
}

// If the function is in multiple files, fix it
if (count($containingFiles) > 1) {
    echo "\nDuplicate function declaration detected. Fixing...\n";
    
    // Keep the function in functions.php and remove it from other files
    foreach ($containingFiles as $file) {
        if ($file !== "includes/functions.php") {
            $content = file_get_contents($file);
            
            // Pattern to match the isAdmin function declaration
            $pattern = '/function\s+isAdmin\s*\(\s*\)\s*\{[^}]*\}/';
            
            // Remove the function
            $newContent = preg_replace($pattern, '// Function isAdmin() moved to functions.php', $content);
            
            // Save the file
            if (file_put_contents($file, $newContent)) {
                echo "Removed isAdmin() function from $file\n";
            } else {
                echo "Failed to update $file\n";
            }
        }
    }
    
    echo "\nFix completed. Please refresh your application.\n";
} else {
    echo "\nNo duplicate function declarations found. The issue might be elsewhere.\n";
    
    // Check if the function is included multiple times
    echo "\nChecking for multiple includes of the same file...\n";
    
    $headerContent = file_get_contents("includes/header.php");
    $matches = [];
    preg_match_all('/require_once\s+[\'"]includes\/functions\.php[\'"]\s*;/', $headerContent, $matches);
    
    if (count($matches[0]) > 1) {
        echo "Multiple includes of functions.php found in header.php. Fixing...\n";
        
        // Keep only the first include
        $newContent = preg_replace('/require_once\s+[\'"]includes\/functions\.php[\'"]\s*;/', '// Duplicate include removed', $headerContent, 1);
        
        // Save the file
        if (file_put_contents("includes/header.php", $newContent)) {
            echo "Removed duplicate include from header.php\n";
        } else {
            echo "Failed to update header.php\n";
        }
    } else {
        echo "No duplicate includes found in header.php\n";
    }
}

echo "</pre>";
echo "<a href='debug.php'>Return to Debug Page</a>";
?>