<?php
require_once "includes/header.php";

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM transport_details WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Transport detail deleted successfully'); window.location.href='transport_details.php';</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch transport details
$sql = "SELECT * FROM transport_details ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="row">
            <div class="col-md-6">
                <h4 class="text-blue h4">Transport Details</h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="add_transport_detail.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Transport
                </a>
            </div>
        </div>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="data-table table stripe hover" style="width: 100%; min-width: 1000px;">
                <thead>
                    <tr>
                        <th style="min-width: 60px;">SL NO</th>
                        <th style="min-width: 120px;">Destination</th>
                        <th style="min-width: 150px;">Company Name</th>
                        <th style="min-width: 120px;">Contact Person</th>
                        <th style="min-width: 100px;">Mobile</th>
                        <th style="min-width: 150px;">Email</th>
                        <th style="min-width: 100px;">Vehicle</th>
                        <th style="min-width: 100px;">Daily Rent</th>
                        <th style="min-width: 100px;">Rate/KM</th>
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
                    <td><?php echo htmlspecialchars($row['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['vehicle']); ?></td>
                    <td>₹<?php echo number_format($row['daily_rent'], 2); ?></td>
                    <td>₹<?php echo number_format($row['rate_per_km'], 2); ?></td>
                    <td>
                        <span class="badge <?php echo $row['status'] == 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_transport_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="transport_details.php?delete=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this transport detail?')">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="11" class="text-center">No transport details found</td>
                </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/deskapp/vendors/scripts/core.js"></script>
<script src="assets/deskapp/vendors/scripts/script.min.js"></script>
<script src="assets/deskapp/vendors/scripts/process.js"></script>
<script src="assets/deskapp/vendors/scripts/layout-settings.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/dataTables.responsive.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
<script>
    $('.data-table').DataTable({
        scrollCollapse: true,
        autoWidth: false,
        responsive: true,
        columnDefs: [{
            targets: "datatable-nosort",
            orderable: false,
        }],
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