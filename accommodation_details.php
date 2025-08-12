<?php
require_once "includes/header.php";

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $delete_sql = "DELETE FROM accommodation_details WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $delete_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            echo "<script>alert('Accommodation detail deleted successfully'); window.location.href='accommodation_details.php';</script>";
        }
        mysqli_stmt_close($stmt);
    }
}

$sql = "SELECT * FROM accommodation_details ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="row">
            <div class="col-md-6">
                <h4 class="text-blue h4">Accommodation Details</h4>
            </div>
            <div class="col-md-6 text-right">
                <a href="add_accommodation_detail.php" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add New Accommodation
                </a>
            </div>
        </div>
    </div>
    <div class="pb-20" style="overflow-x: auto;">
        <div class="table-responsive">
            <table class="data-table table stripe hover" style="width: 100%; min-width: 1800px;">
                <thead>
                    <tr>
                        <th style="min-width: 60px;">SL NO</th>
                        <th style="min-width: 100px;">Destination</th>
                        <th style="min-width: 120px;">Hotel Name</th>
                        <th style="min-width: 100px;">Room Category</th>
                        <th style="min-width: 80px;">CP</th>
                        <th style="min-width: 80px;">MAP</th>
                        <th style="min-width: 100px;">EB Adult CP</th>
                        <th style="min-width: 100px;">EB Adult MAP</th>
                        <th style="min-width: 120px;">Child With Bed CP</th>
                        <th style="min-width: 120px;">Child With Bed MAP</th>
                        <th style="min-width: 130px;">Child Without Bed CP</th>
                        <th style="min-width: 130px;">Child Without Bed MAP</th>
                        <th style="min-width: 140px;">Xmas/NewYear Charges</th>
                        <th style="min-width: 100px;">Meal Type</th>
                        <th style="min-width: 100px;">Adult Meal Charges</th>
                        <th style="min-width: 100px;">Child Meal Price</th>
                        <th style="min-width: 100px;">Validity From</th>
                        <th style="min-width: 100px;">Validity To</th>
                        <th style="min-width: 120px;">Remark</th>
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
                    <td><?php echo htmlspecialchars($row['hotel_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['room_category']); ?></td>
                    <td>₹<?php echo number_format($row['cp'], 2); ?></td>
                    <td>₹<?php echo number_format($row['map_rate'], 2); ?></td>
                    <td>₹<?php echo number_format($row['eb_adult_cp'], 2); ?></td>
                    <td>₹<?php echo number_format($row['eb_adult_map'], 2); ?></td>
                    <td>₹<?php echo number_format($row['child_with_bed_cp'], 2); ?></td>
                    <td>₹<?php echo number_format($row['child_with_bed_map'], 2); ?></td>
                    <td>₹<?php echo number_format($row['child_without_bed_cp'], 2); ?></td>
                    <td>₹<?php echo number_format($row['child_without_bed_map'], 2); ?></td>
                    <td>₹<?php echo number_format($row['xmas_newyear_charges'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['meal_type']); ?></td>
                    <td>₹<?php echo number_format($row['meal_charges'], 2); ?></td>
                    <td>₹<?php echo number_format($row['child_meal_price'] ?? 0, 2); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['validity_from'])); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['validity_to'])); ?></td>
                    <td><?php echo htmlspecialchars($row['remark']); ?></td>
                    <td>
                        <?php 
                        $today = date('Y-m-d');
                        if ($row['validity_to'] >= $today) {
                            echo '<span class="badge badge-success">Active</span>';
                        } else {
                            echo '<span class="badge badge-danger">Expired</span>';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="edit_accommodation_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fa fa-edit"></i>
                        </a>
                        <a href="accommodation_details.php?delete=<?php echo $row['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this accommodation detail?')">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php 
                    endwhile;
                else:
                ?>
                <tr>
                    <td colspan="21" class="text-center">No accommodation details found</td>
                </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<script src="assets/deskapp/vendors/scripts/core.js"></script>
<script src="assets/deskapp/vendors/scripts/script.min.js"></script>
<script src="assets/js/data-module-fix.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="assets/deskapp/src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
<script>
    $('.data-table').DataTable({
        scrollCollapse: true,
        autoWidth: false,
        responsive: false,
        scrollX: true,
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