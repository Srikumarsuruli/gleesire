<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Check if cost_sheets table exists
$table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'cost_sheets'");
if(mysqli_num_rows($table_exists) == 0) {
    // Table doesn't exist, redirect to the table creation script
    echo "<div class='alert alert-warning'>Cost sheets table doesn't exist. <a href='create_cost_sheets_table.php' class='alert-link'>Click here</a> to create it.</div>";
}

// Handle delete action
if(isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && isAdmin()) {
    $cost_sheet_id = intval($_GET['id']);
    
    // Delete cost sheet
    $delete_sql = "DELETE FROM cost_sheets WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $cost_sheet_id);
    
    if(mysqli_stmt_execute($delete_stmt)) {
        $success_message = "Cost sheet deleted successfully!";
    } else {
        $error_message = "Error deleting cost sheet: " . mysqli_error($conn);
    }
}

// Get all cost sheets
$sql = "SELECT cs.*, e.customer_name, u.full_name as created_by_name 
        FROM cost_sheets cs 
        LEFT JOIN enquiries e ON cs.enquiry_id = e.id 
        LEFT JOIN users u ON cs.created_by = u.id 
        ORDER BY cs.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="pd-20 card-box mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h4 class="text-blue h4">Cost Sheets</h4>
        </div>
    </div>
    
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cost Sheet No</th>
                    <th>Customer Name</th>
                    <th>Enquiry Number</th>
                    <th>Package Cost</th>
                    <th>Currency</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['cost_sheet_number'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['enquiry_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['currency']) . ' ' . number_format($row['package_cost'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['currency']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_by_name']); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="dropdown">
                                    <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                        <i class="dw dw-more"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                        <a style="font-size: 12px; class="dropdown-item" href="view_cost_sheet_details.php?id=<?php echo $row['id']; ?>"><i class="dw dw-eye"></i> View</a>
                                        <a style="font-size: 12px; class="dropdown-item" href="cost_sheet.php?id=<?php echo $row['enquiry_id']; ?>"><i class="dw dw-edit2"></i> Edit</a>
                                        <!-- <a class="dropdown-item" href="generate_pdf.php?id=<?php echo $row['id']; ?>"><i class="dw dw-download"></i> Download PDF</a> -->
                                        <?php if(isAdmin()): ?>
                                            <a style="font-size: 12px;" class="dropdown-item" href="view_cost_sheets.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this cost sheet?');"><i class="dw dw-delete-3"></i> Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No cost sheets found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>