<?php
// Completely standalone cost file - no includes at all
$servername = "localhost";
$username = "gleesire_leads";
$password = "Gleesire@123";
$dbname = "gleesire_leads";

$conn = mysqli_connect($servername, $username, $password, $dbname);
$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$enquiry = null;
if ($enquiry_id > 0 && $conn) {
    $sql = "SELECT * FROM enquiries WHERE id = $enquiry_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $enquiry = mysqli_fetch_assoc($result);
    }
}

if ($_POST && $enquiry) {
    $create_sql = "CREATE TABLE IF NOT EXISTS tour_costings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        enquiry_id INT,
        guest_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_sql);
    
    $insert_sql = "INSERT INTO tour_costings (enquiry_id, guest_name) VALUES ($enquiry_id, '" . mysqli_real_escape_string($conn, $_POST['guest_name']) . "')";
    if (mysqli_query($conn, $insert_sql)) {
        $success = "Cost file saved!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cost File</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .header { background: #007bff; color: white; padding: 20px; margin: -30px -30px 20px -30px; border-radius: 10px 10px 0 0; }
        .form-group { margin: 15px 0; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        .btn { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .back-link { display: inline-block; margin-top: 15px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Cost File</h1>
            <?php if ($enquiry): ?>
                <p>Customer: <?php echo htmlspecialchars($enquiry['customer_name']); ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!$enquiry): ?>
            <p style="color: red;">Invalid enquiry ID or enquiry not found.</p>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label>Customer Name (Read Only)</label>
                    <input type="text" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Mobile (Read Only)</label>
                    <input type="text" value="<?php echo htmlspecialchars($enquiry['mobile_number']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Email (Read Only)</label>
                    <input type="text" value="<?php echo htmlspecialchars($enquiry['email']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Guest Name *</label>
                    <input type="text" name="guest_name" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Guest Address</label>
                    <textarea name="guest_address" rows="3" placeholder="Enter guest address"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Tour Package</label>
                    <select name="tour_package">
                        <option value="">Select Package</option>
                        <option value="Honeymoon Package">Honeymoon Package</option>
                        <option value="Family Package">Family Package</option>
                        <option value="Adventure Package">Adventure Package</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Save Cost File</button>
                </div>
            </form>
        <?php endif; ?>
        
        <a href="view_leads.php" class="back-link">← Back to Leads</a>
    </div>
</body>
</html>