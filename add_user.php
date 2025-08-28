<?php
// Include header
require_once "includes/header.php";

// Check if user is admin
if($_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

// Define variables and initialize with empty values
$username = $password = $confirm_password = $full_name = $email = $role_id = "";
$username_err = $password_err = $confirm_password_err = $full_name_err = $email_err = $role_id_err = "";
$success = "";

// Add HR Manager role if it doesn't exist
$check_hr_role = "SELECT id FROM roles WHERE role_name = 'HR Manager'";
$hr_role_result = mysqli_query($conn, $check_hr_role);
if(mysqli_num_rows($hr_role_result) == 0) {
    $insert_hr_role = "INSERT INTO roles (role_name) VALUES ('HR Manager')";
    mysqli_query($conn, $insert_hr_role);
}

// Get roles for dropdown
$sql = "SELECT * FROM roles ORDER BY id";
$roles = mysqli_query($conn, $sql);

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE username = ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif(strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Validate full name
    if(empty(trim($_POST["full_name"]))) {
        $full_name_err = "Please enter full name.";     
    } else {
        $full_name = trim($_POST["full_name"]);
    }
    
    // Validate email
    if(empty(trim($_POST["email"]))) {
        $email_err = "Please enter email.";     
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        $email = trim($_POST["email"]);
    }
    
    // Validate role
    if(empty(trim($_POST["role_id"]))) {
        $role_id_err = "Please select a role.";     
    } else {
        $role_id = trim($_POST["role_id"]);
    }
    
    // Check input errors before inserting in database
    if(empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($full_name_err) && empty($email_err) && empty($role_id_err)) {
        
        // Get status
        $status = isset($_POST["status"]) ? $_POST["status"] : 'active';
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, full_name, email, role_id, status) VALUES (?, ?, ?, ?, ?, ?)";
         
        if($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "ssssiss", $param_username, $param_password, $param_full_name, $param_email, $param_role_id, $param_status);
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_full_name = $full_name;
            $param_email = $email;
            $param_role_id = $role_id;
            $param_status = $status;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)) {
                $success = "User created successfully.";
                
                // Clear form fields
                $username = $password = $confirm_password = $full_name = $email = $role_id = "";
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
}

// Add status column if it doesn't exist
$check_column_sql = "SHOW COLUMNS FROM users LIKE 'status'";
$column_result = mysqli_query($conn, $check_column_sql);
if (mysqli_num_rows($column_result) == 0) {
    $alter_table_sql = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
    mysqli_query($conn, $alter_table_sql);
}

// Get all users
$sql = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id";
$users = mysqli_query($conn, $sql);
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Add Users</h2>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add New User</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label required">Username</label>
                                <input type="text" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo $username; ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full-name" class="form-label required">Full Name</label>
                                <input type="text" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" id="full-name" name="full_name" value="<?php echo $full_name; ?>">
                                <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label required">Password</label>
                                <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password" name="password" value="<?php echo $password; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="confirm-password" class="form-label required">Confirm Password</label>
                                <input type="password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" id="confirm-password" name="confirm_password" value="<?php echo $confirm_password; ?>">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label required">Email</label>
                                <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo $email; ?>">
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-sm-12 col-form-label required">User Role</label>
                                <div class="col-sm-12">
                                    <select class="custom-select col-12 <?php echo (!empty($role_id_err)) ? 'is-invalid' : ''; ?>" id="role-id" name="role_id">
                                        <option value="">Select Role</option>
                                        <?php mysqli_data_seek($roles, 0); ?>
                                        <?php while($role = mysqli_fetch_assoc($roles)): ?>
                                            <option value="<?php echo $role['id']; ?>" <?php echo ($role_id == $role['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['role_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <span class="invalid-feedback"><?php echo $role_id_err; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group row">
                                <label class="col-sm-12 col-form-label required">Status</label>
                                <div class="col-sm-12">
                                    <select class="custom-select col-12" id="status" name="status">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Save User</button>
                            <button type="reset" class="btn btn-secondary">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">User List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($users) > 0): ?>
                                <?php while($user = mysqli_fetch_assoc($users)): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                        <td><span class="badge badge-<?php echo ($user['status'] == 'active') ? 'success' : 'danger'; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                        <td><?php echo date('d-m-Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>