<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $cruise_details = trim($_POST['cruise_details']);
    $name = trim($_POST['name']);
    $contact_number = trim($_POST['contact_number']);
    $department = trim($_POST['department']);
    $email = trim($_POST['email']);
    $adult_price = floatval($_POST['adult_price']);
    $kids_price = floatval($_POST['kids_price']);
    $status = $_POST['status'];
    
    if (empty($destination) || empty($cruise_details) || empty($name) || empty($contact_number) || empty($department)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "INSERT INTO cruise_details (destination, cruise_details, name, contact_number, department, email, adult_price, kids_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssssssdds", $destination, $cruise_details, $name, $contact_number, $department, $email, $adult_price, $kids_price, $status);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Cruise detail added successfully!";
            } else {
                $error_message = "Error: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Add Cruise Detail</h4>
    </div>
    <div class="pd-20">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Destination <span class="text-danger">*</span></label>
                        <input type="text" name="destination" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Cruise Details <span class="text-danger">*</span></label>
                        <input type="text" name="cruise_details" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email ID</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Adult Price (₹)</label>
                        <input type="number" step="0.01" name="adult_price" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Kids Price (₹)</label>
                        <input type="number" step="0.01" name="kids_price" class="form-control" value="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Add Cruise Detail</button>
                <a href="cruise_details.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>