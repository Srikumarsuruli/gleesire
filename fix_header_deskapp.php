<?php
// Include database connection
require_once "config/database.php";

// Check if session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Path to header_deskapp.php
$headerDeskappPath = __DIR__ . '/includes/header_deskapp.php';
$headerDeskappBackupPath = __DIR__ . '/includes/header_deskapp.php.bak';

echo "<h1>Fixing header_deskapp.php</h1>";
echo "<pre>";

// Check if the file exists
if (!file_exists($headerDeskappPath)) {
    echo "Error: header_deskapp.php not found at {$headerDeskappPath}";
    exit(1);
}

// Create a backup of the original file
if (!copy($headerDeskappPath, $headerDeskappBackupPath)) {
    echo "Warning: Could not create backup of header_deskapp.php";
} else {
    echo "Backup created at {$headerDeskappBackupPath}\n";
}

// Read the file content
$content = file_get_contents($headerDeskappPath);
if ($content === false) {
    echo "Error: Could not read header_deskapp.php";
    exit(1);
}

// Check if functions.php is included
if (strpos($content, "require_once \"includes/functions.php\"") === false) {
    echo "Adding include for functions.php...\n";
    
    // Add the include after the database connection include
    $content = str_replace(
        "require_once \"config/database.php\";",
        "require_once \"config/database.php\";\n\n// Include common functions\nrequire_once \"includes/functions.php\";",
        $content
    );
}

// Check if the file contains the hasPrivilege function
if (strpos($content, "function hasPrivilege(") !== false) {
    echo "Found hasPrivilege() function in header_deskapp.php. Removing...\n";
    
    // Remove the function definition
    $pattern = '/\/\/ Function to check user privileges\s*function\s+hasPrivilege\s*\([^)]*\)\s*\{[^}]*\}/s';
    $newContent = preg_replace($pattern, '// hasPrivilege() function moved to functions.php', $content);
    
    // Write the updated content back to the file
    if (file_put_contents($headerDeskappPath, $newContent)) {
        echo "Successfully removed hasPrivilege() function from header_deskapp.php";
    } else {
        echo "Error: Could not write to header_deskapp.php";
        exit(1);
    }
} else {
    echo "hasPrivilege() function not found in header_deskapp.php\n";
    
    // Write the updated content back to the file if we added the include
    if ($content !== file_get_contents($headerDeskappPath)) {
        if (file_put_contents($headerDeskappPath, $content)) {
            echo "Successfully updated header_deskapp.php";
        } else {
            echo "Error: Could not write to header_deskapp.php";
            exit(1);
        }
    } else {
        echo "No changes needed for header_deskapp.php";
    }
}

echo "</pre>";
echo "<a href='debug.php'>Return to Debug Page</a>";
?>