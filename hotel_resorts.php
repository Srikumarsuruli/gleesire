<?php
require_once "includes/header.php";

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM hotel_resorts WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Hotel/Resort deleted successfully'); window.location.href='hotel_resorts.php';</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch hotel/resort details
$sql = "SELECT * FROM hotel_resorts ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="row">
            <div class="col-md-6">
                <h4 class="text-blue h4">Hotel/Resorts</h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="add_hotel_resort.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Hotel/Resort
                </a>
            </div>
        </div>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="data-table table stripe hover" style="width: 100%; min-width: 1500px;">
                <thead>
                    <tr>
                        <th>SL NO</th>
                        <th>Cost Sheet Number</th>
                        <th>Booking Date</th>
                        <th>Check-in Date</th>
                        <th>Check-out Date</th>
                        <th>Destination</th>
                        <th>Hotel Name</th>
                        <th>Room Category</th>
                        <th>CP</th>
                        <th>MAP</th>
                        <th>Availability</th>
                        <th>Booking Status</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                        <td><?php echo htmlspecialchars($row['cost_sheet_number']); ?></td>
                        <td><?php echo $row['booking_date'] ? date('d-m-Y', strtotime($row['booking_date'])) : 'N/A'; ?></td>
                        <td><?php echo $row['checkin_date'] ? date('d-m-Y', strtotime($row['checkin_date'])) : 'N/A'; ?></td>
                        <td><?php echo $row['checkout_date'] ? date('d-m-Y', strtotime($row['checkout_date'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($row['destination']); ?></td>
                        <td><?php echo htmlspecialchars($row['hotel_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['room_category']); ?></td>
                        <td>₹<?php echo number_format($row['cp'], 2); ?></td>
                        <td>₹<?php echo number_format($row['map_price'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $row['availability_status'] == 'Available' ? 'badge-success' : 'badge-warning'; ?>">
                                <?php echo $row['availability_status']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $row['booking_status'] == 'Booking Confirmed' ? 'badge-success' : ($row['booking_status'] == 'Amendment' ? 'badge-info' : 'badge-danger'); ?>">
                                <?php echo $row['booking_status']; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo $row['status'] == 'Active' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $row['status']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_hotel_resort.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="hotel_resorts.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this hotel/resort?')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="14" class="text-center">No hotel/resort details found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- <script src="assets/deskapp/vendors/scripts/core.js"></script> -->
<!-- <script src="assets/deskapp/vendors/scripts/script.min.js"></script> -->
<script src="assets/js/data-module-fix.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
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