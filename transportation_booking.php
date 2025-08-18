<?php
require_once "includes/header.php";

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM transportation_booking WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Transportation booking deleted successfully'); window.location.href='transportation_booking.php';</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch transportation bookings
$sql = "SELECT 
    t.id,
    t.enquiry_id,
    t.cost_sheet_number,
    t.guest_name,
    t.guest_address,
    t.whatsapp_number,
    t.tour_package,
    t.currency,
    t.nationality,
    t.booking_status,
    t.status,
    t.created_at,
    
    acc.idx,
    acc.supplier,
    acc.car_type,
    acc.daily_rent,
    acc.days,
    acc.km,
    acc.extra_km,
    acc.price_per_km,
    acc.toll,
    acc.availability,
    acc.total
FROM tour_costings t
JOIN JSON_TABLE(
    t.transportation_data,
    '$[*]' COLUMNS (
        idx VARCHAR(255) PATH '$.idx',
        supplier VARCHAR(255) PATH '$.supplier',
        car_type VARCHAR(255) PATH '$.car_type',
        daily_rent DECIMAL(10,2) PATH '$.daily_rent',
        days DECIMAL(10,2) PATH '$.days',
        km DECIMAL(10,2) PATH '$.km',
        extra_km DECIMAL(10,2) PATH '$.extra_km',
        price_per_km DECIMAL(10,2) PATH '$.price_per_km',
        toll DECIMAL(10,2) PATH '$.toll',
        availability VARCHAR(255) PATH '$.availability',
        total DECIMAL(10,2) PATH '$.total'
    )
) AS acc
WHERE JSON_CONTAINS(t.selected_services, '[\"transportation\"]')
ORDER BY t.created_at DESC;";
$result = mysqli_query($conn, $sql);
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="row">
            <div class="col-md-6">
                <h4 class="text-blue h4">Transportation Booking Details</h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="add_transportation_booking.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Transportation
                </a>
            </div>
        </div>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="data-table table stripe hover" style="width: 100%; min-width: 1600px;">
                <thead>
                    <tr>
                        <th>SL NO</th>
                        <th>Cost Sheet Number</th>
                        <th>Booking Date</th>
                        <th>Supplier</th>
                        <th>Car Type</th>
                        <th>Daily Rent</th>
                        <th>Days</th>
                        <th>Km</th>
                        <th>Extra Km</th>
                        <th>Price/Km</th>
                        <th>Toll/Parking</th>
                        <th>Total</th>
                        <th>Availability Status</th>
                        <th>Booking Status</th>
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
                    <td><?php echo htmlspecialchars($row['cost_sheet_number'] ?? ''); ?></td>
                    <td><?php echo $row['created_at'] ? date('d-m-Y', strtotime($row['created_at'])) : ''; ?></td>
                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                    <td><?php echo htmlspecialchars($row['car_type']); ?></td>
                    <td><?php echo htmlspecialchars($row['daily_rent']); ?></td>
                    <td><?php echo number_format($row['days'], 2); ?></td>
                    <td><?php echo number_format($row['extra_km'], 2); ?></td>
                    <td><?php echo number_format($row['km'], 2); ?></td>
                    <td>₹<?php echo number_format($row['price_per_km'], 2); ?></td>
                    <td>₹<?php echo number_format($row['toll'], 2); ?></td>
                    <td>₹<?php echo number_format($row['total'], 2); ?></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <select class="custom-select status-select" data-id="<?php echo $row['id']; ?>" data-original="<?php echo $row['availability']; ?>" style="min-width: 120px;">
                                <option hidden value="" <?php echo ($row['availability'] == "") ? 'selected' : ''; ?>>
                                    Choose
                                </option>
                                <option value="Available" <?php echo ($row['availability'] == "Available") ? 'selected' : ''; ?>>
                                    Available
                                </option>
                                <option value="Not Available" <?php echo ($row['availability'] == "Not Available") ? 'selected' : ''; ?>>
                                    Not Available
                                </option>
                            </select>
                            <button type="button" onclick="updateAvailability(<?php echo $row['id']; ?>, <?php echo $row['idx']; ?>, this)" style="background: none; border: none; color: green; font-size: 18px; cursor: pointer;">✓</button>
                        </div>
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
                        <a href="edit_transportation_booking.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="transportation_booking.php?delete=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this transportation booking?')">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="15" class="text-center">No transportation bookings found</td>
                </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<!-- <script src="assets/deskapp/vendors/scripts/core.js"></script> -->
<!-- <script src="assets/deskapp/vendors/scripts/script.min.js"></script> -->
<!-- <script src="assets/deskapp/vendors/scripts/process.js"></script> -->
<!-- <script src="assets/deskapp/vendors/scripts/layout-settings.js"></script> -->
<script src="assets/deskapp/src/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/dataTables.responsive.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
<script>
    function updateAvailability(id, idx, button) {
    console.log('updateAvailability called with ID:', id);
    
    // Find the select element in the same row as the button
    var row = button.closest('tr');
    var statusSelect = row.querySelector('.status-select');
    
    if (!statusSelect) {
        console.error('Status select not found for ID:', id);
        return;
    }
    
    var selectedStatus = statusSelect.value;
    var originalStatus = statusSelect.getAttribute('data-original');
    
    console.log('Selected status:', selectedStatus);
    console.log('Original status:', originalStatus);
    
    if(selectedStatus && selectedStatus !== originalStatus) {
        // Create and submit form immediately
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_service_availability.php';
        
        var idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        
        var availabilityInput = document.createElement('input');
        availabilityInput.type = 'hidden';
        availabilityInput.name = 'availability';
        availabilityInput.value = selectedStatus;
        
        var hotelIdInput = document.createElement('input');
        hotelIdInput.type = 'hidden';
        hotelIdInput.name = 'idx';
        hotelIdInput.value = idx;
        
        var serviceInput = document.createElement('input');
        serviceInput.type = 'hidden';
        serviceInput.name = 'service';
        serviceInput.value = "transportation_data";
        
        var callbackInput = document.createElement('input');
        callbackInput.type = 'hidden';
        callbackInput.name = 'callback';
        callbackInput.value = "transportation_booking";
        
        form.appendChild(idInput);
        form.appendChild(availabilityInput);
        form.appendChild(hotelIdInput);
        form.appendChild(serviceInput);
        form.appendChild(callbackInput);
        document.body.appendChild(form);
        
        form.submit();
    } else if(selectedStatus === originalStatus) {
        console.log('Availability unchanged, no update needed');
    } else {
        console.error('No status selected');
    }
}
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