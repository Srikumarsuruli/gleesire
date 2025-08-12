<?php
require_once "includes/header.php";

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM visa_ticket_booking WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Visa & ticket booking deleted successfully'); window.location.href='visa_ticket_booking.php';</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch visa & ticket bookings
$sql = "SELECT * FROM visa_ticket_booking ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="row">
            <div class="col-md-6">
                <h4 class="text-blue h4">Visa & Air Ticket Booking Details</h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="add_visa_ticket_booking.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Visa & Ticket
                </a>
            </div>
        </div>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="data-table table stripe hover" style="width: 100%; min-width: 1500px;">
                <thead>
                    <tr>
                        <th style="min-width: 60px;">SL NO</th>
                        <th style="min-width: 120px;">Cost Sheet Number</th>
                        <th style="min-width: 100px;">Booking Date</th>
                        <th style="min-width: 100px;">Checkin Date</th>
                        <th style="min-width: 100px;">Check Out Date</th>
                        <th style="min-width: 120px;">Destination</th>
                        <th style="min-width: 100px;">Agent Type</th>
                        <th style="min-width: 150px;">Supplier</th>
                        <th style="min-width: 150px;">Name of Supplier</th>
                        <th style="min-width: 120px;">Contact Number</th>
                        <th style="min-width: 120px;">Availability Status</th>
                        <th style="min-width: 120px;">Booking Status</th>
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
                    <td><?php echo htmlspecialchars($row['cost_sheet_number'] ?? ''); ?></td>
                    <td><?php echo $row['booking_date'] ? date('d-m-Y', strtotime($row['booking_date'])) : ''; ?></td>
                    <td><?php echo $row['checkin_date'] ? date('d-m-Y', strtotime($row['checkin_date'])) : ''; ?></td>
                    <td><?php echo $row['checkout_date'] ? date('d-m-Y', strtotime($row['checkout_date'])) : ''; ?></td>
                    <td><?php echo htmlspecialchars($row['destination']); ?></td>
                    <td>
                        <span class="badge <?php echo ($row['agent_type'] ?? 'Domestic') == 'Domestic' ? 'badge-info' : 'badge-primary'; ?>">
                            <?php echo $row['agent_type'] ?? 'Domestic'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                    <td>
                        <span class="badge <?php echo ($row['availability_status'] ?? 'Available') == 'Available' ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $row['availability_status'] ?? 'Available'; ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php 
                            $status = $row['booking_status'] ?? 'Booking Confirmed';
                            echo $status == 'Booking Confirmed' ? 'badge-success' : ($status == 'Amendment' ? 'badge-warning' : 'badge-danger'); 
                        ?>">
                            <?php echo $status; ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_visa_ticket_booking.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="visa_ticket_booking.php?delete=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this visa & ticket booking?')">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="13" class="text-center">No visa & ticket bookings found</td>
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
    
    // Hamburger menu fix
    $(document).off('click', '.menu-icon').on('click', '.menu-icon', function(e) {
        e.preventDefault();
        var sidebar = $('.left-side-bar');
        var overlay = $('.mobile-menu-overlay');
        if (sidebar.hasClass('open')) {
            sidebar.removeClass('open');
            overlay.removeClass('show');
        } else {
            sidebar.addClass('open');
            overlay.addClass('show');
        }
    });
</script>

<?php require_once "includes/footer.php"; ?>