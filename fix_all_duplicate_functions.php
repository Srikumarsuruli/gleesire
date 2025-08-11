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

// Function to check if a file includes another file
function fileIncludesFile($filePath, $includePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $content = file_get_contents($filePath);
    return strpos($content, "require_once \"$includePath\"") !== false || 
           strpos($content, "require \"$includePath\"") !== false || 
           strpos($content, "include_once \"$includePath\"") !== false || 
           strpos($content, "include \"$includePath\"") !== false;
}

// Function to remove a function from a file
function removeFunctionFromFile($filePath, $functionName) {
    $content = file_get_contents($filePath);
    
    // Create backup
    $backupPath = $filePath . '.bak';
    if (!file_exists($backupPath)) {
        copy($filePath, $backupPath);
    }
    
    // Pattern to match the function declaration
    $pattern = '/\/\/\s*Function to check.*?\s*function\s+' . $functionName . '\s*\([^)]*\)\s*\{[^}]*\}/s';
    
    // Remove the function
    $newContent = preg_replace($pattern, '// ' . $functionName . '() function moved to functions.php', $content);
    
    // If the pattern didn't match, try a simpler pattern
    if ($newContent === $content) {
        $pattern = '/function\s+' . $functionName . '\s*\([^)]*\)\s*\{[^}]*\}/s';
        $newContent = preg_replace($pattern, '// ' . $functionName . '() function moved to functions.php', $content);
    }
    
    // Save the file
    return file_put_contents($filePath, $newContent);
}

// Function to add an include to a file
function addIncludeToFile($filePath, $includePath) {
    $content = file_get_contents($filePath);
    
    // Create backup
    $backupPath = $filePath . '.bak';
    if (!file_exists($backupPath)) {
        copy($filePath, $backupPath);
    }
    
    // Add the include after the database connection include
    $newContent = str_replace(
        "require_once \"config/database.php\";",
        "require_once \"config/database.php\";\n\n// Include common functions\nrequire_once \"$includePath\";",
        $content
    );
    
    // Save the file
    return file_put_contents($filePath, $newContent);
}

// Set page title
$page_title = "Fix Duplicate Functions";

// Include header
require_once "includes/header.php";
?>

<div class="pd-20 card-box mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h4 class="text-blue h4">Fix Duplicate Function Declarations</h4>
            <p>This script will fix duplicate function declarations in your application files.</p>
        </div>
    </div>

    <div class="pb-20">
        <div class="alert alert-info">
            <h5>Checking for duplicate functions...</h5>
            <pre>
<?php
// Files to check
$files = [
    "includes/header.php",
    "includes/header_deskapp.php",
    "includes/functions.php",
    "includes/footer.php",
    "includes/footer_deskapp.php"
];

// Functions to check
$functions = ["isAdmin", "hasPrivilege"];

// Check which files contain the functions
$functionLocations = [];
foreach ($functions as $function) {
    $functionLocations[$function] = [];
    foreach ($files as $file) {
        if (fileContainsFunction($file, $function)) {
            $functionLocations[$function][] = $file;
            echo "Function $function() found in: $file\n";
        }
    }
}

// Fix duplicate functions
$fixesMade = false;
foreach ($functions as $function) {
    if (count($functionLocations[$function]) > 1) {
        echo "\nDuplicate declaration of $function() detected. Fixing...\n";
        
        // Keep the function in functions.php and remove it from other files
        foreach ($functionLocations[$function] as $file) {
            if ($file !== "includes/functions.php") {
                if (removeFunctionFromFile($file, $function)) {
                    echo "Removed $function() function from $file\n";
                    $fixesMade = true;
                } else {
                    echo "Failed to update $file\n";
                }
                
                // Add include for functions.php if not already there
                if (!fileIncludesFile($file, "includes/functions.php")) {
                    if (addIncludeToFile($file, "includes/functions.php")) {
                        echo "Added include for functions.php to $file\n";
                        $fixesMade = true;
                    } else {
                        echo "Failed to add include to $file\n";
                    }
                }
            }
        }
    } else {
        echo "\nNo duplicate declarations found for $function()\n";
    }
}

// If no fixes were made, check for other issues
if (!$fixesMade) {
    echo "\nChecking for other potential issues...\n";
    
    // Check for multiple includes of the same file
    foreach ($files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $matches = [];
            preg_match_all('/require_once\s+[\'"]includes\/functions\.php[\'"]\s*;/', $content, $matches);
            
            if (count($matches[0]) > 1) {
                echo "Multiple includes of functions.php found in $file. Fixing...\n";
                
                // Create backup
                $backupPath = $file . '.bak';
                if (!file_exists($backupPath)) {
                    copy($file, $backupPath);
                }
                
                // Keep only the first include
                $newContent = preg_replace('/require_once\s+[\'"]includes\/functions\.php[\'"]\s*;/', '// Duplicate include removed', $content, 1);
                
                // Save the file
                if (file_put_contents($file, $newContent)) {
                    echo "Removed duplicate include from $file\n";
                    $fixesMade = true;
                } else {
                    echo "Failed to update $file\n";
                }
            }
        }
    }
}

if ($fixesMade) {
    echo "\nFixes have been applied. Please refresh your application.\n";
} else {
    echo "\nNo issues found that could be automatically fixed.\n";
    echo "The error might be in a different file or caused by a different issue.\n";
}
?>
            </pre>
        </div>

        <div class="mt-20">
            <a href="debug.php" class="btn btn-primary">Go back to Debug Page</a>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>