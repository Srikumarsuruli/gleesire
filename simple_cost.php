<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: PHP is working<br>";

// Database connection
$conn = mysqli_connect("localhost", "gleesire_leads", "Gleesire@123", "gleesire_leads");

if (!$conn) {
    die("Step 2: Database connection failed: " . mysqli_connect_error());
}
echo "Step 2: Database connected<br>";

$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo "Step 3: Enquiry ID = " . $enquiry_id . "<br>";

$enquiry = null;
if ($enquiry_id > 0) {
    $sql = "SELECT customer_name, mobile_number, email FROM enquiries WHERE id = " . $enquiry_id;
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        die("Step 4: Query failed: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) > 0) {
        $enquiry = mysqli_fetch_assoc($result);
        echo "Step 4: Customer found: " . $enquiry['customer_name'] . "<br>";
    } else {
        echo "Step 4: No customer found<br>";
    }
}

echo "Step 5: All checks passed<br>";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Cost File</title>
</head>
<body>
    <h1>Cost File Creation</h1>
    
    <?php if ($enquiry): ?>
        <h2>Customer: <?php echo htmlspecialchars($enquiry['customer_name']); ?></h2>
        <p>Mobile: <?php echo htmlspecialchars($enquiry['mobile_number']); ?></p>
        <p>Email: <?php echo htmlspecialchars($enquiry['email']); ?></p>
        
        <form method="post">
            <p>
                <label>Guest Name:</label><br>
                <input type="text" name="guest_name" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>" style="width: 300px; padding: 5px;">
            </p>
            <p>
                <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none;">Save Cost File</button>
            </p>
        </form>
        
        <?php if ($_POST): ?>
            <div style="background: #d4edda; padding: 10px; margin: 10px 0;">
                Form submitted! Guest name: <?php echo htmlspecialchars($_POST['guest_name']); ?>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <p style="color: red;">Customer not found for ID: <?php echo $enquiry_id; ?></p>
    <?php endif; ?>
    
    <p><a href="view_leads.php">← Back to Leads</a></p>
</body>
</html>