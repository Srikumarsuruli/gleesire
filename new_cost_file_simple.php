<?php
// Start session and output buffering first
session_start();
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database config first
require_once "config/database.php";

// Check privileges without header first
function hasPrivilege($privilege) {
    return isset($_SESSION['user_id']) && isset($_SESSION['privileges']) && in_array($privilege, $_SESSION['privileges']);
}

if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Now include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Get enquiry ID
$enquiry_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($enquiry_id == 0) {
    echo "<div class='alert alert-danger'>Invalid enquiry ID.</div>";
    require_once "includes/footer.php";
    exit;
}

// Get enquiry data
$sql = "SELECT * FROM enquiries WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $enquiry_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 0) {
    echo "<div class='alert alert-danger'>Enquiry not found.</div>";
    require_once "includes/footer.php";
    exit;
}

$enquiry = mysqli_fetch_assoc($result);

// Create tour_costings table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS tour_costings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    cost_sheet_number VARCHAR(50),
    guest_name VARCHAR(255),
    guest_address TEXT,
    whatsapp_number VARCHAR(20),
    tour_package VARCHAR(100),
    currency VARCHAR(10) DEFAULT 'USD',
    nationality VARCHAR(10),
    selected_services TEXT,
    visa_data TEXT,
    accommodation_data TEXT,
    transportation_data TEXT,
    cruise_data TEXT,
    extras_data TEXT,
    payment_data TEXT,
    total_expense DECIMAL(10,2) DEFAULT 0,
    markup_percentage DECIMAL(5,2) DEFAULT 0,
    markup_amount DECIMAL(10,2) DEFAULT 0,
    tax_percentage DECIMAL(5,2) DEFAULT 18,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    package_cost DECIMAL(10,2) DEFAULT 0,
    currency_rate DECIMAL(10,4) DEFAULT 1,
    converted_amount DECIMAL(10,2) DEFAULT 0,
    adults_count INT DEFAULT 0,
    children_count INT DEFAULT 0,
    infants_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_table_sql);

// Get destinations for dropdown
$destinations_sql = "SELECT * FROM destinations ORDER BY name";
$destinations = mysqli_query($conn, $destinations_sql);
?>

<style>
.cost-file-container {
    padding: 20px;
    background-color: #f8f9fa;
    min-height: 100vh;
}

.cost-file-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin: 0 auto;
    max-width: 1200px;
}

.cost-file-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
    border-radius: 10px 10px 0 0;
}

.cost-file-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.cost-file-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin-top: 10px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    padding: 30px;
}

.info-card {
    background: #f8f9ff;
    border-radius: 10px;
    padding: 20px;
    border-left: 4px solid #667eea;
}

.info-card h5 {
    color: #333;
    font-weight: 600;
    margin-bottom: 15px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
}

.info-value {
    color: #333;
    font-weight: 500;
}

.form-control {
    max-width: 200px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 0.9rem;
}

.btn-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 12px 30px;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin: 20px;
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    color: white;
    text-decoration: none;
}

.action-buttons {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
}

.alert {
    padding: 15px;
    margin: 20px;
    border-radius: 5px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>

<div class="cost-file-container">
    <div class="cost-file-card">
        <div class="cost-file-header">
            <h1 class="cost-file-title">New Cost File</h1>
            <p class="cost-file-subtitle">Create cost file for: <?php echo htmlspecialchars($enquiry['customer_name']); ?></p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $enquiry_id); ?>">
            <div class="info-grid">
                <!-- Customer Information -->
                <div class="info-card">
                    <h5><i class="fa fa-user"></i> Customer Information</h5>
                    <div class="info-row">
                        <span class="info-label">Customer Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['customer_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mobile:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['mobile_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($enquiry['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Guest Name:</span>
                        <input type="text" class="form-control" name="guest_name" value="<?php echo htmlspecialchars($enquiry['customer_name']); ?>">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Guest Address:</span>
                        <input type="text" class="form-control" name="guest_address" placeholder="Enter address">
                    </div>
                    <div class="info-row">
                        <span class="info-label">WhatsApp Number:</span>
                        <input type="text" class="form-control" name="whatsapp_number" value="<?php echo htmlspecialchars($enquiry['mobile_number']); ?>">
                    </div>
                </div>

                <!-- Travel Information -->
                <div class="info-card">
                    <h5><i class="fa fa-plane"></i> Travel Information</h5>
                    <div class="info-row">
                        <span class="info-label">Package Type:</span>
                        <select class="form-control" name="tour_package">
                            <option value="">Select Package</option>
                            <option value="Honeymoon Package">Honeymoon Package</option>
                            <option value="Family Package">Family Package</option>
                            <option value="Adventure Package">Adventure Package</option>
                            <option value="Business Package">Business Package</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Currency:</span>
                        <select class="form-control" name="currency">
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                            <option value="INR">INR</option>
                            <option value="AED">AED</option>
                        </select>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nationality:</span>
                        <select class="form-control" name="nationality">
                            <option value="">Select Country</option>
                            <option value="IN">India</option>
                            <option value="US">United States</option>
                            <option value="GB">United Kingdom</option>
                            <option value="AE">UAE</option>
                        </select>
                    </div>
                </div>

                <!-- PAX Information -->
                <div class="info-card">
                    <h5><i class="fa fa-users"></i> Number of PAX</h5>
                    <div class="info-row">
                        <span class="info-label">Adults:</span>
                        <input type="number" class="form-control" name="adults_count" value="2" min="0">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Children:</span>
                        <input type="number" class="form-control" name="children_count" value="0" min="0">
                    </div>
                    <div class="info-row">
                        <span class="info-label">Infants:</span>
                        <input type="number" class="form-control" name="infants_count" value="0" min="0">
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <button type="submit" class="btn-modern">
                    <i class="fa fa-save"></i> Save Cost File
                </button>
                <a href="view_leads.php" class="btn-modern" style="background: #6c757d;">
                    <i class="fa fa-arrow-left"></i> Back to Leads
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Simple form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const guestName = document.querySelector('input[name="guest_name"]').value;
    if (!guestName.trim()) {
        alert('Please enter guest name');
        e.preventDefault();
        return false;
    }
    
    alert('Cost file will be saved!');
});
</script>

<?php
require_once "includes/footer.php";
?>