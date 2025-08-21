<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect to index page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: index.php");
    exit;
}
 
// Include config file
require_once "config/database.php";
 
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Check for remembered credentials
if(isset($_COOKIE['remember_username']) && isset($_COOKIE['remember_password'])) {
    $username = $_COOKIE['remember_username'];
    $password = base64_decode($_COOKIE['remember_password']);
}
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT id, username, password, role_id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $role_id);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Handle Remember Me functionality
                            if(isset($_POST['remember_me'])) {
                                setcookie('remember_username', $username, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                                setcookie('remember_password', base64_encode($password), time() + (30 * 24 * 60 * 60), '/'); // 30 days
                            } else {
                                // Clear cookies if remember me is not checked
                                setcookie('remember_username', '', time() - 3600, '/');
                                setcookie('remember_password', '', time() - 3600, '/');
                            }
                            
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role_id"] = $role_id;
                            
                            // Log user login with location
                            $login_time = date('Y-m-d H:i:s');
                            $login_date = date('Y-m-d');
                            $ip_address = $_SERVER['REMOTE_ADDR'];
                            $user_agent = $_SERVER['HTTP_USER_AGENT'];
                            
                            // Parse device information from user agent
                            function parseDeviceInfo($user_agent) {
                                $device_name = 'Unknown';
                                $device_type = 'Unknown';
                                
                                // Mobile devices with more details
                                if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $user_agent)) {
                                    $device_type = 'Mobile';
                                    if (preg_match('/iPhone OS ([0-9_]+)/i', $user_agent, $matches)) {
                                        $ios_version = str_replace('_', '.', $matches[1]);
                                        $device_name = 'iPhone (iOS ' . $ios_version . ')';
                                    } elseif (preg_match('/iPhone/i', $user_agent)) {
                                        $device_name = 'iPhone';
                                    } elseif (preg_match('/iPad/i', $user_agent)) {
                                        $device_name = 'iPad';
                                    } elseif (preg_match('/Android ([0-9.]+)/i', $user_agent, $matches)) {
                                        $android_version = $matches[1];
                                        $device_name = 'Android ' . $android_version;
                                    } elseif (preg_match('/Android/i', $user_agent)) {
                                        $device_name = 'Android Device';
                                    }
                                } else {
                                    $device_type = 'Desktop';
                                    // Browser detection
                                    $browser = 'Unknown Browser';
                                    if (preg_match('/Chrome\/([0-9.]+)/i', $user_agent, $matches)) {
                                        $browser = 'Chrome ' . explode('.', $matches[1])[0];
                                    } elseif (preg_match('/Firefox\/([0-9.]+)/i', $user_agent, $matches)) {
                                        $browser = 'Firefox ' . explode('.', $matches[1])[0];
                                    } elseif (preg_match('/Safari\/([0-9.]+)/i', $user_agent) && !preg_match('/Chrome/i', $user_agent)) {
                                        $browser = 'Safari';
                                    } elseif (preg_match('/Edge\/([0-9.]+)/i', $user_agent, $matches)) {
                                        $browser = 'Edge ' . explode('.', $matches[1])[0];
                                    }
                                    
                                    if (preg_match('/Windows NT ([0-9.]+)/i', $user_agent, $matches)) {
                                        $win_version = $matches[1];
                                        $win_name = $win_version == '10.0' ? 'Windows 10' : ($win_version == '6.1' ? 'Windows 7' : 'Windows');
                                        $device_name = $win_name . ' (' . $browser . ')';
                                    } elseif (preg_match('/Mac OS X ([0-9_]+)/i', $user_agent, $matches)) {
                                        $mac_version = str_replace('_', '.', $matches[1]);
                                        $device_name = 'macOS ' . $mac_version . ' (' . $browser . ')';
                                    } elseif (preg_match('/Linux/i', $user_agent)) {
                                        $device_name = 'Linux (' . $browser . ')';
                                    }
                                }
                                
                                return array('name' => $device_name, 'type' => $device_type);
                            }
                            
                            $device_info = parseDeviceInfo($user_agent);
                            $device_name = $device_info['name'];
                            $device_type = $device_info['type'];
                            
                            // Get location from IP with fallback
                            $location_data = @json_decode(file_get_contents("http://ip-api.com/json/{$ip_address}"), true);
                            if($location_data && $location_data['status'] == 'success') {
                                $country = $location_data['country'];
                                $city = $location_data['city'];
                            } else {
                                // Fallback for local/private IPs
                                $country = 'Local Network';
                                $city = 'Office';
                            }
                            
                            // Check if user already has a session today
                            $check_sql = "SELECT id FROM user_login_logs WHERE user_id = ? AND date = ? AND logout_time IS NULL";
                            if($check_stmt = mysqli_prepare($conn, $check_sql)) {
                                mysqli_stmt_bind_param($check_stmt, "is", $id, $login_date);
                                mysqli_stmt_execute($check_stmt);
                                $check_result = mysqli_stmt_get_result($check_stmt);
                                
                                if(mysqli_num_rows($check_result) > 0) {
                                    // Continue existing session
                                    $row = mysqli_fetch_assoc($check_result);
                                    $_SESSION["current_login_id"] = $row['id'];
                                } else {
                                    // Add device columns if they don't exist
                                    @mysqli_query($conn, "ALTER TABLE user_login_logs ADD COLUMN device_name VARCHAR(100) DEFAULT 'Unknown'");
                                    @mysqli_query($conn, "ALTER TABLE user_login_logs ADD COLUMN device_type VARCHAR(50) DEFAULT 'Unknown'");
                                    
                                    // Create new session with location and device info
                                    $log_sql = "INSERT INTO user_login_logs (user_id, login_time, date, ip_address, country, city, user_agent, device_name, device_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                    if($log_stmt = mysqli_prepare($conn, $log_sql)) {
                                        mysqli_stmt_bind_param($log_stmt, "issssssss", $id, $login_time, $login_date, $ip_address, $country, $city, $user_agent, $device_name, $device_type);
                                        mysqli_stmt_execute($log_stmt);
                                        $_SESSION["current_login_id"] = mysqli_insert_id($conn);
                                        mysqli_stmt_close($log_stmt);
                                    }
                                }
                                mysqli_stmt_close($check_stmt);
                            }
                            
                            $_SESSION["login_start_time"] = time();
                            $_SESSION["login_date"] = $login_date;
                            
                            // Redirect user to index page
                            header("location: index.php");
                        } else{
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else{
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Basic Page Info -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Login - Lead Management System</title>
    
    <!-- Site favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/deskapp/src/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/deskapp/src/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/deskapp/src/images/favicon-16x16.png">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/style.css">
    <link rel="stylesheet" type="text/css" href="assets/css/custom.css">
</head>
<body class="login-page">
    <div class="login-header box-shadow">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="brand-logo">
                <a href="login.php">
                    <img src="assets/deskapp/vendors/images/custom-logo.svg" alt="">
                </a>
            </div>
        </div>
    </div>
    <div class="login-wrap d-flex align-items-center flex-wrap justify-content-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 col-lg-7">
                    <img src="assets/deskapp/vendors/images/login-page-img.png" alt="">
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="login-box bg-white box-shadow border-radius-10">
                        <div class="login-title">
                            <h2 class="text-center text-primary">Login To Lead Management</h2>
                        </div>
                        
                        <?php if(!empty($login_err)): ?>
                            <div class="alert alert-danger"><?php echo $login_err; ?></div>
                        <?php endif; ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="form-group">
                                <label class="form-control-label">Username</label>
                                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>
                            <div class="form-group">
                                <label class="form-control-label">Password</label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="row pb-30">
                                <div class="col-6">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="remember-me" name="remember_me" <?php echo (isset($_COOKIE['remember_username']) ? 'checked' : ''); ?>>
                                        <label class="custom-control-label" for="remember-me">Remember Me</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="input-group mb-0">
                                        <input class="btn btn-primary btn-lg btn-block" type="submit" value="Sign In">
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- js -->
    <script src="assets/deskapp/vendors/scripts/core.js"></script>
    <script src="assets/deskapp/vendors/scripts/script.min.js"></script>
    <script src="assets/deskapp/vendors/scripts/process.js"></script>
    <script src="assets/deskapp/vendors/scripts/layout-settings.js"></script>
</body>
</html>