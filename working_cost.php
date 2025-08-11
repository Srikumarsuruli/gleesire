<?php
// Use the same database config as your working files
require_once "config/database.php";

$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($enquiry_id == 0) {
    die("Invalid enquiry ID");
}

// Get enquiry data
$sql = "SELECT * FROM enquiries WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Enquiry not found");
}

$enquiry = mysqli_fetch_assoc($result);

// Handle form submission
if ($_POST) {
    // Create table if needed
    $create_sql = "CREATE TABLE IF NOT EXISTS tour_costings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enquiry_id INT,
        guest_name VARCHAR(255),
        guest_address TEXT,
        tour_package VARCHAR(100),
        currency VARCHAR(10) DEFAULT 'USD',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_sql);
    
    // Insert data
    $insert_sql = "INSERT INTO tour_costings (enquiry_id, guest_name, guest_address, tour_package, currency) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "issss", 
        $enquiry_id,
        $_POST['guest_name'],
        $_POST['guest_address'],
        $_POST['tour_package'],
        $_POST['currency']
    );
    
    if (mysqli_stmt_execute($insert_stmt)) {
        $success = "Cost file saved successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cost File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .card { max-width: 800px; margin: 0 auto; }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-alt"></i> New Cost File</h3>
            <p class="mb-0">Customer: <?php echo htmlspecialchars($enquiry['customer_name']); ?></p>
        </div>
        <div class="card-body">
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <br><a href="view_leads.php" class="btn btn-primary btn-sm mt-2">Back to Leads</a>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($enquiry['mobile_number']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($enquiry['email']); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Guest Name *</label>
                            <input type="text" class="form-control" name="guest_name" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Guest Address</label>
                            <textarea class="form-control" name="guest_address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tour Package</label>
                            <select class="form-control" name="tour_package">
                                <option value="">Select Package</option>
                                <option value="Honeymoon Package">Honeymoon Package</option>
                                <option value="Family Package">Family Package</option>
                                <option value="Adventure Package">Adventure Package</option>
                                <option value="Business Package">Business Package</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Currency</label>
                            <select class="form-control" name="currency">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="INR">INR</option>
                                <option value="AED">AED</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Save Cost File
                    </button>
                    <a href="view_leads.php" class="btn btn-secondary btn-lg ms-2">
                        <i class="fas fa-arrow-left"></i> Back to Leads
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>