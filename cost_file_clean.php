<?php
session_start();
ob_start();

// Include database config
require_once "config/database.php";

// Simple privilege check
if(!isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

// Get enquiry ID
$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($enquiry_id == 0) {
    die("Invalid enquiry ID");
}

// Get enquiry data
$sql = "SELECT * FROM enquiries WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    die("Enquiry not found");
}

$enquiry = mysqli_fetch_assoc($result);

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Create tour_costings table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS tour_costings (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        enquiry_id INT(11) NOT NULL,
        guest_name VARCHAR(255),
        guest_address TEXT,
        whatsapp_number VARCHAR(20),
        tour_package VARCHAR(100),
        currency VARCHAR(10) DEFAULT 'USD',
        nationality VARCHAR(10),
        adults_count INT DEFAULT 0,
        children_count INT DEFAULT 0,
        infants_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table_sql);
    
    // Insert cost file data
    $insert_sql = "INSERT INTO tour_costings (enquiry_id, guest_name, guest_address, whatsapp_number, tour_package, currency, nationality, adults_count, children_count, infants_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "issssssiiii", 
        $enquiry_id,
        $_POST['guest_name'],
        $_POST['guest_address'],
        $_POST['whatsapp_number'],
        $_POST['tour_package'],
        $_POST['currency'],
        $_POST['nationality'],
        $_POST['adults_count'],
        $_POST['children_count'],
        $_POST['infants_count']
    );
    
    if(mysqli_stmt_execute($insert_stmt)) {
        $success = "Cost file saved successfully!";
    } else {
        $error = "Error saving cost file: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>New Cost File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .cost-card { background: white; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .cost-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .cost-body { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-label { font-weight: 600; color: #333; margin-bottom: 8px; }
        .form-control { border: 2px solid #e9ecef; border-radius: 8px; padding: 12px; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn-save { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 12px 30px; border-radius: 25px; font-weight: 600; }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); color: white; }
        .alert { border-radius: 10px; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="cost-card">
        <div class="cost-header">
            <h1><i class="fa fa-file-alt"></i> New Cost File</h1>
            <p>Create cost file for: <?php echo htmlspecialchars($enquiry['customer_name']); ?></p>
        </div>
        
        <div class="cost-body">
            <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fa fa-check-circle"></i> <?php echo $success; ?>
                    <br><a href="view_leads.php" class="btn btn-sm btn-primary mt-2">Back to Leads</a>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($enquiry['mobile_number']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($enquiry['email']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Guest Name *</label>
                            <input type="text" class="form-control" name="guest_name" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Guest Address</label>
                            <textarea class="form-control" name="guest_address" rows="3" placeholder="Enter guest address"></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" class="form-control" name="whatsapp_number" value="<?php echo htmlspecialchars($enquiry['mobile_number']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Tour Package</label>
                            <select class="form-control" name="tour_package">
                                <option value="">Select Package</option>
                                <option value="Honeymoon Package">Honeymoon Package</option>
                                <option value="Family Package">Family Package</option>
                                <option value="Adventure Package">Adventure Package</option>
                                <option value="Business Package">Business Package</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Currency</label>
                            <select class="form-control" name="currency">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                                <option value="INR">INR</option>
                                <option value="AED">AED</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nationality</label>
                            <select class="form-control" name="nationality">
                                <option value="">Select Country</option>
                                <option value="IN">India</option>
                                <option value="US">United States</option>
                                <option value="GB">United Kingdom</option>
                                <option value="AE">UAE</option>
                                <option value="SA">Saudi Arabia</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label">Adults</label>
                                    <input type="number" class="form-control" name="adults_count" value="2" min="0">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label">Children</label>
                                    <input type="number" class="form-control" name="children_count" value="0" min="0">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label">Infants</label>
                                    <input type="number" class="form-control" name="infants_count" value="0" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-save">
                        <i class="fa fa-save"></i> Save Cost File
                    </button>
                    <a href="view_leads.php" class="btn btn-secondary ms-2">
                        <i class="fa fa-arrow-left"></i> Back to Leads
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>