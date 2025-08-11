<?php
require_once "includes/header.php";

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM cruise_details WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Cruise detail deleted successfully'); window.location.href='cruise_details.php';</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

$sql = "SELECT * FROM cruise_details ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="row">
            <div class="col-md-6">
                <h4 class="text-blue h4">Cruise Details</h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="add_cruise_detail.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Cruise
                </a>
            </div>
        </div>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="data-table table stripe hover" style="width: 100%; min-width: 1200px;">
                <thead>
                    <tr>
                        <th style="min-width: 60px;">SL NO</th>
                        <th style="min-width: 120px;">Destination</th>
                        <th style="min-width: 200px;">Cruise Details</th>
                        <th style="min-width: 120px;">Name</th>
                        <th style="min-width: 120px;">Contact Number</th>
                        <th style="min-width: 100px;">Department</th>
                        <th style="min-width: 150px;">Email ID</th>
                        <th style="min-width: 100px;">Adult Price</th>
                        <th style="min-width: 100px;">Kids Price</th>
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
                    <td><?php echo htmlspecialchars($row['destination']); ?></td>
                    <td><?php echo htmlspecialchars($row['cruise_details']); ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['department']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td>₹<?php echo number_format($row['adult_price'], 2); ?></td>
                    <td>₹<?php echo number_format($row['kids_price'], 2); ?></td>
                    <td>
                        <span class="badge <?php echo $row['status'] == 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_cruise_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="cruise_details.php?delete=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this cruise detail?')">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="11" class="text-center">No cruise details found</td>
                </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/deskapp/vendors/scripts/core.js"></script>
<script src="assets/deskapp/vendors/scripts/script.min.js"></script>
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