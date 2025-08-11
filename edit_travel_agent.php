<?php
require_once "includes/header.php";

$success_message = '';
$error_message = '';
$travel_agent = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: TravelAgents.php");
    exit;
}

$id = $_GET['id'];

$sql = "SELECT * FROM travel_agents WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $travel_agent = mysqli_fetch_assoc($result);
        } else {
            header("Location: TravelAgents.php");
            exit;
        }
    }
    mysqli_stmt_close($stmt);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $destination = trim($_POST['destination']);
    $agent_type = $_POST['agent_type'];
    $supplier = trim($_POST['supplier']);
    $supplier_name = trim($_POST['supplier_name']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $status = $_POST['status'];
    
    if (empty($destination) || empty($agent_type) || empty($supplier) || empty($supplier_name) || empty($contact_number)) {
        $error_message = "Please fill in all required fields.";
    } else {
        $sql = "UPDATE travel_agents SET destination = ?, agent_type = ?, supplier = ?, supplier_name = ?, contact_number = ?, email = ?, status = ? WHERE id = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssssssi", $destination, $agent_type, $supplier, $supplier_name, $contact_number, $email, $status, $id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Travel agent updated successfully!";
                // Refresh data
                $travel_agent['destination'] = $destination;
                $travel_agent['agent_type'] = $agent_type;
                $travel_agent['supplier'] = $supplier;
                $travel_agent['supplier_name'] = $supplier_name;
                $travel_agent['contact_number'] = $contact_number;
                $travel_agent['email'] = $email;
                $travel_agent['status'] = $status;
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
        <h4 class="text-blue h4">Edit Travel Agent</h4>
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
                        <input type="text" name="destination" class="form-control" value="<?php echo htmlspecialchars($travel_agent['destination']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Agent Type <span class="text-danger">*</span></label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="agent_type" id="domestic" value="Domestic" <?php echo ($travel_agent['agent_type'] == 'Domestic') ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="domestic">Domestic</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="agent_type" id="outbound" value="Outbound" <?php echo ($travel_agent['agent_type'] == 'Outbound') ? 'checked' : ''; ?> required>
                            <label class="form-check-label" for="outbound">Outbound</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="supplier" class="form-control" value="<?php echo htmlspecialchars($travel_agent['supplier']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Name of the Supplier <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_name" class="form-control" value="<?php echo htmlspecialchars($travel_agent['supplier_name']); ?>" required>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Number <span class="text-danger">*</span></label>
                        <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($travel_agent['contact_number']); ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email ID</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($travel_agent['email']); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active" <?php echo ($travel_agent['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo ($travel_agent['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update Travel Agent</button>
                <a href="TravelAgents.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>