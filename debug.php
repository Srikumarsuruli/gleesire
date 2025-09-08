<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Check required extensions
$required_extensions = ['mysqli', 'session', 'json'];
foreach($required_extensions as $ext) {
    echo "Extension $ext: " . (extension_loaded($ext) ? 'OK' : 'MISSING') . "<br>";
}

// Test database connection
echo "<br>Testing database connection...<br>";
try {
    $conn = mysqli_connect('localhost', 'gleesire_leads_user', '{*7(hNG}aV&C8{lc}', 'gleesire_leads');
    if($conn) {
        echo "Database connection: OK<br>";
        mysqli_close($conn);
    } else {
        echo "Database connection: FAILED - " . mysqli_connect_error() . "<br>";
    }
} catch(Exception $e) {
    echo "Database connection: ERROR - " . $e->getMessage() . "<br>";
}

// Check file permissions
echo "<br>File permissions:<br>";
echo "config/database.php: " . (is_readable(__DIR__ . '/config/database.php') ? 'Readable' : 'Not readable') . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
?>