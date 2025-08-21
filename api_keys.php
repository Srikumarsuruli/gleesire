<?php
require_once "includes/header.php";

// Check if user is admin
if($_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

$success = $error = "";

// Create API keys table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    usage_count INT DEFAULT 0,
    FOREIGN KEY (created_by) REFERENCES users(id)
)";
mysqli_query($conn, $create_table_sql);

// Generate new API key
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate_key"])) {
    $key_name = trim($_POST["key_name"]);
    
    if(!empty($key_name)) {
        // Generate secure API key
        $api_key = 'gls_' . bin2hex(random_bytes(28)); // 64 character key
        
        $sql = "INSERT INTO api_keys (key_name, api_key, created_by) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $key_name, $api_key, $_SESSION["id"]);
            if(mysqli_stmt_execute($stmt)) {
                $success = "API Key generated successfully!";
            } else {
                $error = "Error generating API key.";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $error = "Please enter a key name.";
    }
}

// Toggle key status
if(isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $key_id = $_GET['toggle'];
    $new_status = $_GET['status'] == 'active' ? 'inactive' : 'active';
    
    $sql = "UPDATE api_keys SET status = ? WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $new_status, $key_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("location: api_keys.php");
    exit;
}

// Get all API keys
$sql = "SELECT ak.*, u.username FROM api_keys ak JOIN users u ON ak.created_by = u.id ORDER BY ak.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">API Key Management</h4>
    </div>
    
    <?php if(!empty($success)): ?>
        <div class="pd-20">
            <div class="alert alert-success"><?php echo $success; ?></div>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($error)): ?>
        <div class="pd-20">
            <div class="alert alert-danger"><?php echo $error; ?></div>
        </div>
    <?php endif; ?>
    
    <!-- Generate New API Key -->
    <div class="pd-20">
        <h5>Generate New API Key</h5>
        <form method="post" class="row">
            <div class="col-md-6">
                <input type="text" name="key_name" class="form-control" placeholder="Enter key name (e.g., Botamation Integration)" required>
            </div>
            <div class="col-md-3">
                <button type="submit" name="generate_key" class="btn btn-primary">Generate Key</button>
            </div>
        </form>
    </div>
    
    <!-- API Keys List -->
    <div class="pb-20">
        <table class="table hover data-table nowrap">
            <thead>
                <tr>
                    <th>Key Name</th>
                    <th>API Key</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th>Last Used</th>
                    <th>Usage Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['key_name']); ?></td>
                    <td>
                        <code class="api-key-display" data-key="<?php echo $row['api_key']; ?>">
                            <?php echo substr($row['api_key'], 0, 20) . '...'; ?>
                        </code>
                        <button class="btn btn-sm btn-outline-primary ml-2" onclick="copyKey('<?php echo $row['api_key']; ?>')">
                            <i class="fa fa-copy"></i> Copy
                        </button>
                        <button class="btn btn-sm btn-outline-secondary ml-1" onclick="toggleKey(this, '<?php echo $row['api_key']; ?>')">
                            <i class="fa fa-eye"></i> Show
                        </button>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                    <td><?php echo $row['last_used'] ? date('d-m-Y H:i', strtotime($row['last_used'])) : 'Never'; ?></td>
                    <td><?php echo $row['usage_count']; ?></td>
                    <td>
                        <a href="?toggle=<?php echo $row['id']; ?>&status=<?php echo $row['status']; ?>" 
                           class="btn btn-sm btn-<?php echo $row['status'] == 'active' ? 'warning' : 'success'; ?>">
                            <?php echo $row['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function copyKey(apiKey) {
    navigator.clipboard.writeText(apiKey).then(function() {
        alert('API Key copied to clipboard!');
    });
}

function toggleKey(button, apiKey) {
    const codeElement = button.parentElement.querySelector('.api-key-display');
    const icon = button.querySelector('i');
    
    if(codeElement.textContent.includes('...')) {
        codeElement.textContent = apiKey;
        icon.className = 'fa fa-eye-slash';
        button.innerHTML = '<i class="fa fa-eye-slash"></i> Hide';
    } else {
        codeElement.textContent = apiKey.substring(0, 20) + '...';
        icon.className = 'fa fa-eye';
        button.innerHTML = '<i class="fa fa-eye"></i> Show';
    }
}
</script>

<?php require_once "includes/footer.php"; ?>