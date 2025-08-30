<?php
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('manage_packages')) {
    header("location: index.php");
    exit;
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM packages WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Package deleted successfully'); window.location.href='Packages.php';</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $toggle_id = $_GET['toggle'];
    $toggle_sql = "UPDATE packages SET status = CASE WHEN status = 'Active' THEN 'Inactive' ELSE 'Active' END WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $toggle_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $toggle_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Package status updated successfully'); window.location.href='Packages.php';</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

$sql = "SELECT p.*, d.name as department_name FROM packages p 
        LEFT JOIN departments d ON p.department_id = d.id 
        ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="row">
            <div class="col-md-6">
                <h4 class="text-blue h4">Packages</h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="add_package.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Package
                </a>
            </div>
        </div>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="data-table table stripe hover" style="width: 100%; min-width: 800px;">
                <thead>
                    <tr>
                        <th style="min-width: 60px;">SL NO</th>
                        <th style="min-width: 200px;">Package Name</th>
                        <th style="min-width: 120px;">Package Price</th>
                        <th style="min-width: 150px;">Department</th>
                        <th style="min-width: 80px;">Status</th>
                        <th style="min-width: 100px;">Actions</th>
                    </tr>
                </thead>
            <tbody>
                <?php 
                $sl_no = 1;
                if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)): 
                ?>
                <tr>
                    <td><?php echo $sl_no++; ?></td>
                    <td><?php echo htmlspecialchars($row['package_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['package_price']); ?></td>
                    <td><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></td>
                    <td>
                        <span class="badge <?php echo $row['status'] == 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_package.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="Packages.php?toggle=<?php echo $row['id']; ?>" 
                           class="btn btn-sm <?php echo $row['status'] == 'Active' ? 'btn-warning' : 'btn-success'; ?>" 
                           onclick="return confirm('Are you sure you want to <?php echo $row['status'] == 'Active' ? 'deactivate' : 'activate'; ?> this package?')">
                            <i class="fa fa-<?php echo $row['status'] == 'Active' ? 'ban' : 'check'; ?>"></i>
                        </a>
                        <a href="Packages.php?delete=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this package?')">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="6" class="text-center">No packages found</td>
                </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/deskapp/src/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
<script>
    $('.data-table').DataTable({
        scrollCollapse: true,
        autoWidth: false,
        responsive: true,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "language": {
            "info": "_START_-_END_ of _TOTAL_ entries",
            searchPlaceholder: "Search",
            paginate: {
                next: '<i class="ion-chevron-right"></i>',
                previous: '<i class="ion-chevron-left"></i>'
            }
        },
    });
</script>

<?php require_once "includes/footer.php"; ?>