<?php
// Set page title
$page_title = "Fix Missing Components";

// Include header
require_once "includes/header.php";
?>

<div class="pd-20 card-box mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h4 class="text-blue h4">Fix Missing Components</h4>
            <p>This script will fix missing components identified in the debug report.</p>
        </div>
    </div>

    <div class="pb-20">
        <h5>1. Payment Receipts Table</h5>
        <div class="alert alert-info">
            <?php
            // Check if the table already exists
            $table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'payment_receipts'");
            if(mysqli_num_rows($table_exists) == 0) {
                // Create the payment_receipts table
                $sql = "CREATE TABLE payment_receipts (
                    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    payment_id INT(11) NOT NULL,
                    file_path VARCHAR(255) NOT NULL,
                    file_name VARCHAR(100) NOT NULL,
                    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
                )";
                
                if(mysqli_query($conn, $sql)) {
                    echo "Payment receipts table created successfully";
                } else {
                    echo "Error creating payment receipts table: " . mysqli_error($conn);
                }
            } else {
                echo "Payment receipts table already exists";
            }
            ?>
        </div>

        <h5>2. Functions.php File</h5>
        <div class="alert alert-info">
            <?php
            // Path to functions.php
            $functions_file = "includes/functions.php";

            // Check if functions.php exists
            if(file_exists($functions_file)) {
                echo "functions.php exists. Checking content...<br>";
                
                // Read the file content
                $content = file_get_contents($functions_file);
                
                // Check if the file has the required functions
                $has_is_admin = strpos($content, "function isAdmin()") !== false;
                $has_has_privilege = strpos($content, "function hasPrivilege(") !== false;
                
                if($has_is_admin && $has_has_privilege) {
                    echo "functions.php contains the required functions.";
                } else {
                    echo "functions.php is missing some required functions. Recreating the file...<br>";
                    
                    // Create the functions.php file with required functions
                    $functions_content = '<?php
// Function to check if user is admin
function isAdmin() {
    return $_SESSION["role_id"] == 1;
}

// Function to check user privileges
function hasPrivilege($menu, $action = \'view\') {
    global $conn;
    $role_id = $_SESSION["role_id"];
    
    // Admin has all privileges
    if($role_id == 1) {
        return true;
    }
    
    // Check if the table exists
    $table_exists = mysqli_query($conn, "SHOW TABLES LIKE \'user_privileges\'");
    if(mysqli_num_rows($table_exists) == 0) {
        return false;
    }
    
    $column = "can_" . $action;
    $sql = "SELECT $column FROM user_privileges WHERE role_id = ? AND menu_name = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $role_id, $menu);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                return $row[$column] == 1;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    return false;
}
?>';
                    
                    // Write the content to the file
                    if(file_put_contents($functions_file, $functions_content)) {
                        echo "functions.php has been recreated successfully.";
                    } else {
                        echo "Error: Could not write to functions.php.";
                    }
                }
            } else {
                echo "functions.php does not exist. Creating the file...<br>";
                
                // Create the directory if it doesn't exist
                if(!is_dir("includes")) {
                    mkdir("includes", 0755, true);
                }
                
                // Create the functions.php file with required functions
                $functions_content = '<?php
// Function to check if user is admin
function isAdmin() {
    return $_SESSION["role_id"] == 1;
}

// Function to check user privileges
function hasPrivilege($menu, $action = \'view\') {
    global $conn;
    $role_id = $_SESSION["role_id"];
    
    // Admin has all privileges
    if($role_id == 1) {
        return true;
    }
    
    // Check if the table exists
    $table_exists = mysqli_query($conn, "SHOW TABLES LIKE \'user_privileges\'");
    if(mysqli_num_rows($table_exists) == 0) {
        return false;
    }
    
    $column = "can_" . $action;
    $sql = "SELECT $column FROM user_privileges WHERE role_id = ? AND menu_name = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $role_id, $menu);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                return $row[$column] == 1;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    return false;
}
?>';
                
                // Write the content to the file
                if(file_put_contents($functions_file, $functions_content)) {
                    echo "functions.php has been created successfully.";
                } else {
                    echo "Error: Could not create functions.php.";
                }
            }
            ?>
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