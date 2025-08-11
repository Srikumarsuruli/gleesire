<?php
// Include header
require_once "includes/header.php";

// Check if user is admin
if($_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

// Check if ID parameter exists
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))) {
    header("location: add_user.php");
    exit;
}

$id = trim($_GET["id"]);
$username = $full_name = $email = $role_id = $password = "";
$username_err = $full_name_err = $email_err = $role_id_err = $password_err = "";
$success = "";

// Get roles for dropdown
$sql = "SELECT * FROM roles ORDER BY id";
$roles = mysqli_query($conn, $sql);

// Fetch user data
$sql = "SELECT * FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $username = $user['username'];
            $full_name = $user['full_name'];
            $email = $user['email'];
            $role_id = $user['role_id'];
        } else {
            header("location: add_user.php");
            exit;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Check if username is taken by another user
        $sql = "SELECT id FROM users WHERE username = ? AND id != ?";
        
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $param_username, $id);
            $param_username = trim($_POST["username"]);
            
            if(mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if(mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            }
            mysqli_stmt_close($stmt);
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
    
    // Validate password (optional for update)
    if(!empty(trim($_POST["password"]))) {
        if(strlen(trim($_POST["password"])) < 6) {
            $password_err = "Password must have at least 6 characters.";
        } else {
            $password = trim($_POST["password"]);
        }
    }
    
    // Check input errors before updating
    if(empty($username_err) && empty($full_name_err) && empty($email_err) && empty($role_id_err) && empty($password_err)) {
        
        if(!empty($password)) {
            // Update with password
            $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role_id = ?, password = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                mysqli_stmt_bind_param($stmt, "sssisi", $username, $full_name, $email, $role_id, $param_password, $id);
                
                if(mysqli_stmt_execute($stmt)) {
                    $success = "User updated successfully.";
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            // Update without password
            $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, role_id = ? WHERE id = ?";
            
            if($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssii", $username, $full_name, $email, $role_id, $id);
                
                if(mysqli_stmt_execute($stmt)) {
                    $success = "User updated successfully.";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4">Edit User</h2>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit User Details</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $id; ?>" method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label required">Username</label>
                                <input type="text" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="full-name" class="form-label required">Full Name</label>
                                <input type="text" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" id="full-name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>">
                                <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label required">Email</label>
                                <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
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
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" id="password" name="password">
                                <small class="text-muted">Leave blank to keep current password</small>
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Update User</button>
                            <a href="add_user.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>