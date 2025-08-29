<?php
require_once "includes/header.php";

if(!hasPrivilege('daily_movement_register')) {
    header("location: index.php");
    exit;
}

$selected_date = date('Y-m-d');

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $selected_date = !empty($_POST["selected_date"]) ? $_POST["selected_date"] : date('Y-m-d');
}

// Get movement data for the selected date
$sql = "SELECT 
    tc.cost_sheet_number,
    e.customer_name as guest_name,
    tc.accommodation_data,
    e.mobile_number as contact_details,
    fm.full_name as file_manager_name,
    cl.travel_end_date as checkout_date
FROM enquiries e 
JOIN tour_costings tc ON e.id = tc.enquiry_id
JOIN converted_leads cl ON e.id = cl.enquiry_id
LEFT JOIN users fm ON cl.file_manager_id = fm.id
WHERE tc.confirmed = '1' 
AND (DATE(cl.travel_start_date) = ? OR DATE(cl.travel_end_date) = ?)
ORDER BY tc.cost_sheet_number";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $selected_date, $selected_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!-- Filter Section -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Daily Movement Register</h4>
    </div>
    <div class="pb-20 pd-20">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Date</label>
                        <input type="date" class="form-control" name="selected_date" value="<?php echo $selected_date; ?>">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-group mb-0">
                        <button type="submit" name="filter" class="btn btn-primary">Apply Filter</button>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary ml-2">Reset</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Movement Register Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">MOVEMENT REGISTER - <?php echo date('d/m/Y', strtotime($selected_date)); ?></h4>
    </div>
    <div class="pb-20">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Cost Sheet Number</th>
                        <th>Guest Name</th>
                        <th>Hotel Name</th>
                        <th>Contact Details</th>
                        <th>File Manager Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php $sno = 1; ?>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <?php
                            // Extract hotel name from accommodation data
                            $hotel_name = 'Nill';
                            $accommodation_data = json_decode($row['accommodation_data'], true);
                            if(is_array($accommodation_data) && !empty($accommodation_data)) {
                                foreach($accommodation_data as $accommodation) {
                                    if(isset($accommodation['hotel'])) {
                                        $hotel_name = $accommodation['hotel'];
                                        break;
                                    }
                                }
                            }
                            
                            // Determine status based on checkout date
                            $status = 'Nill';
                            if($row['checkout_date'] && date('Y-m-d', strtotime($row['checkout_date'])) == $selected_date) {
                                $status = 'Departure';
                            }
                            ?>
                            <tr>
                                <td><?php echo $sno++; ?></td>
                                <td><?php echo htmlspecialchars($row['cost_sheet_number']); ?></td>
                                <td><?php echo htmlspecialchars($row['guest_name']); ?></td>
                                <td><?php echo htmlspecialchars($hotel_name); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_details'] ?? 'Nill'); ?></td>
                                <td><?php echo htmlspecialchars($row['file_manager_name'] ?? 'Nill'); ?></td>
                                <td><?php echo $status; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No movement records found for <?php echo date('d/m/Y', strtotime($selected_date)); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>