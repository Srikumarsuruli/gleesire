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
    acc.destination,
    acc.hotel,
    acc.check_in,
    acc.check_out,
    acc.room_type,
    acc.rooms_no,
    acc.rooms_rate,
    acc.extra_adult_no,
    acc.extra_adult_rate,
    acc.extra_child_no,
    acc.extra_child_rate,
    acc.child_no_bed_no,
    acc.child_no_bed_rate,
    acc.nights,
    acc.meal_plan,
    acc.availability,
    acc.total
FROM tour_costings t
JOIN JSON_TABLE(
    t.accommodation_data,
    '$[*]' COLUMNS (
        idx VARCHAR(255) PATH '$.idx',
        destination VARCHAR(255) PATH '$.destination',
        hotel VARCHAR(255) PATH '$.hotel',
        check_in DATE PATH '$.check_in',
        check_out DATE PATH '$.check_out',
        room_type VARCHAR(255) PATH '$.room_type',
        rooms_no INT PATH '$.rooms_no',
        rooms_rate DECIMAL(10,2) PATH '$.rooms_rate',
        extra_adult_no INT PATH '$.extra_adult_no',
        extra_adult_rate DECIMAL(10,2) PATH '$.extra_adult_rate',
        extra_child_no INT PATH '$.extra_child_no',
        extra_child_rate DECIMAL(10,2) PATH '$.extra_child_rate',
        child_no_bed_no INT PATH '$.child_no_bed_no',
        child_no_bed_rate DECIMAL(10,2) PATH '$.child_no_bed_rate',
        nights INT PATH '$.nights',
        meal_plan VARCHAR(255) PATH '$.meal_plan',
        availability VARCHAR(255) PATH '$.availability',
        total DECIMAL(10,2) PATH '$.total'
    )
) AS acc
WHERE JSON_CONTAINS(t.selected_services, '[\"accommodation\"]')
ORDER BY t.created_at DESC;";

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
                        <th>Room Rate</th>
                        <th>Total</th>
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
                        <td><?php echo $row['created_at'] ? date('d-m-Y', strtotime($row['created_at'])) : 'N/A'; ?></td>
                        <td><?php echo $row['check_in'] ? date('d-m-Y', strtotime($row['check_in'])) : 'N/A'; ?></td>
                        <td><?php echo $row['check_out'] ? date('d-m-Y', strtotime($row['check_out'])) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($row['destination']); ?></td>
                        <td><?php echo htmlspecialchars($row['hotel']); ?></td>
                        <td><?php echo htmlspecialchars($row['room_type']); ?></td>
                        <td>₹<?php echo number_format($row['rooms_rate'], 2); ?></td>
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
        serviceInput.value = "accommodation_data";
        
        var callbackInput = document.createElement('input');
        callbackInput.type = 'hidden';
        callbackInput.name = 'callback';
        callbackInput.value = "hotel_resorts";
        
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
</script>

<?php require_once "includes/footer.php"; ?>