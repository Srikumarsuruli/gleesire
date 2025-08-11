<?php
echo "Testing specific enquiry...<br>";

$conn = mysqli_connect("localhost", "gleesire_leads", "Gleesire@123", "gleesire_leads");
$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

echo "Enquiry ID: " . $enquiry_id . "<br>";

if ($enquiry_id > 0) {
    $sql = "SELECT customer_name, mobile_number, email FROM enquiries WHERE id = " . $enquiry_id;
    echo "Query: " . $sql . "<br>";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $enquiry = mysqli_fetch_assoc($result);
            echo "SUCCESS: Found customer: " . htmlspecialchars($enquiry['customer_name']) . "<br>";
            echo "Mobile: " . htmlspecialchars($enquiry['mobile_number']) . "<br>";
            echo "Email: " . htmlspecialchars($enquiry['email']) . "<br>";
        } else {
            echo "No customer found with ID " . $enquiry_id . "<br>";
        }
    } else {
        echo "Query failed: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Invalid enquiry ID<br>";
}
?>
<html>
<body>
<h2>Simple Cost File Form</h2>
<form method="post">
    <p>Guest Name: <input type="text" name="guest_name" value="Test Name"></p>
    <p><button type="submit">Save</button></p>
</form>

<?php if ($_POST): ?>
    <p style="color: green;">Form submitted: <?php echo htmlspecialchars($_POST['guest_name']); ?></p>
<?php endif; ?>

<p><a href="view_leads.php">Back to Leads</a></p>
</body>
</html>