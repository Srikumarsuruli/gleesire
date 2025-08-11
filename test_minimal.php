<?php
// Minimal test - no includes
echo "TEST 1: Basic PHP works<br>";

// Test database connection
$servername = "localhost";
$username = "gleesire_leads";
$password = "Gleesire@123";
$dbname = "gleesire_leads";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if ($conn) {
    echo "TEST 2: Database connection works<br>";
} else {
    echo "TEST 2: Database connection failed<br>";
    die();
}

// Test enquiry ID
$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo "TEST 3: Enquiry ID = " . $enquiry_id . "<br>";

if ($enquiry_id > 0) {
    $sql = "SELECT customer_name FROM enquiries WHERE id = $enquiry_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "TEST 4: Customer found = " . $row['customer_name'] . "<br>";
    } else {
        echo "TEST 4: No customer found<br>";
    }
}

echo "TEST 5: All tests completed<br>";
?>
<html>
<head><title>Minimal Test</title></head>
<body>
<h1>Minimal Cost File Test</h1>
<p>If you see this, the page is working!</p>
<form method="post">
    <input type="text" name="test" placeholder="Test input">
    <button type="submit">Test Submit</button>
</form>
<?php if ($_POST): ?>
    <p>Form submitted: <?php echo htmlspecialchars($_POST['test'] ?? 'empty'); ?></p>
<?php endif; ?>
</body>
</html>