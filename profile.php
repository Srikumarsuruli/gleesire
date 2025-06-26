<?php
// Include header
require_once "includes/header.php";

$success = $error = "";

// Process password change
if(isset($_POST["change_password"])) {
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    
    if(empty($new_password)) {
        $error = "Please enter new password";
    } elseif(strlen($new_password) < 6) {
        $error = "Password must have at least 6 characters";
    } elseif($new_password != $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $_SESSION["id"]);
            if(mysqli_stmt_execute($stmt)) {
                $success = "Password changed successfully";
            } else {
                $error = "Something went wrong";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Process image upload
if(isset($_POST["upload_image"]) && isset($_FILES["profile_image"])) {
    $target_dir = "assets/images/profiles/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
    $target_file = $target_dir . $_SESSION["id"] . "." . $file_extension;
    
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if(!in_array(strtolower($file_extension), $allowed_types)) {
        $error = "Only JPG, JPEG, PNG & GIF files are allowed";
    } elseif($_FILES["profile_image"]["size"] > 5000000) {
        $error = "File is too large (max 5MB)";
    } elseif(move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $target_file, $_SESSION["id"]);
            if(mysqli_stmt_execute($stmt)) {
                $success = "Profile image updated successfully";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Error uploading file: " . error_get_last()['message'];
    }
}

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$user = [];
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}
?>

<div class="container">
    <h2 class="my-4">User Profile</h2>
    
    <?php if(!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if(!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Profile Image -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">Profile Image</div>
                <div class="card-body text-center">
                    <?php if(!empty($user["profile_image"])): ?>
                        <img src="<?php echo htmlspecialchars($user["profile_image"]); ?>" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 50px;">
                            <?php echo strtoupper(substr($user["full_name"], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5><?php echo htmlspecialchars($user["full_name"]); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user["email"]); ?></p>
                    
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                        </div>
                        <button type="submit" name="upload_image" class="btn btn-primary">Update Image</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Change Password -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Change Password</div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>