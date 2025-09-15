<?php
// Generate sample CSV - must be before any output
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="accommodation_sample.csv"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    echo "star_category,destination,hotel_name,room_category,meal_type,room_price_adult,room_price_child_with_bed,room_price_child_without_bed,meal_charge_adult,meal_charge_child,season_type,validity_from,validity_to\n";
    echo "3 Star,Goa,Sample Hotel,Deluxe,EPAI,5000,3000,2000,500,250,WINTER,2024-01-01,2024-12-31\n";
    exit;
}

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

// Handle CSV upload
if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    if ($file['error'] == 0 && pathinfo($file['name'], PATHINFO_EXTENSION) == 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        fgetcsv($handle); // Skip header row
        $success_count = 0;
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (count($data) >= 13) {
                $sql = "INSERT INTO accommodation_details (star_category, destination, hotel_name, room_category, meal_type, room_price_adult, room_price_child_with_bed, room_price_child_without_bed, meal_charge_adult, meal_charge_child, season_type, validity_from, validity_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sssssdddddsss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11], $data[12]);
                    if (mysqli_stmt_execute($stmt)) $success_count++;
                    mysqli_stmt_close($stmt);
                }
            }
        }
        
        fclose($handle);
        echo "<script>alert('$success_count records uploaded successfully'); window.location.href='accommodation_details.php';</script>";
    } else {
        echo "<script>alert('Please upload a valid CSV file');</script>";
    }
}



// Filter variables
$destination_filter = $hotel_name_filter = $room_category_filter = $price_from = $price_to = $validity_from = $validity_to = "";

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $destination_filter = !empty($_POST["destination_filter"]) ? $_POST["destination_filter"] : "";
    $hotel_name_filter = !empty($_POST["hotel_name_filter"]) ? $_POST["hotel_name_filter"] : "";
    $room_category_filter = !empty($_POST["room_category_filter"]) ? $_POST["room_category_filter"] : "";
    $price_from = !empty($_POST["price_from"]) ? floatval($_POST["price_from"]) : "";
    $price_to = !empty($_POST["price_to"]) ? floatval($_POST["price_to"]) : "";
    $validity_from = !empty($_POST["validity_from"]) ? $_POST["validity_from"] : "";
    $validity_to = !empty($_POST["validity_to"]) ? $_POST["validity_to"] : "";
} else {
    // Get from URL parameters for pagination
    $destination_filter = isset($_GET['destination_filter']) ? $_GET['destination_filter'] : "";
    $hotel_name_filter = isset($_GET['hotel_name_filter']) ? $_GET['hotel_name_filter'] : "";
    $room_category_filter = isset($_GET['room_category_filter']) ? $_GET['room_category_filter'] : "";
    $price_from = isset($_GET['price_from']) ? floatval($_GET['price_from']) : "";
    $price_to = isset($_GET['price_to']) ? floatval($_GET['price_to']) : "";
    $validity_from = isset($_GET['validity_from']) ? $_GET['validity_from'] : "";
    $validity_to = isset($_GET['validity_to']) ? $_GET['validity_to'] : "";
}

// Build SQL query with filters
$sql = "SELECT * FROM accommodation_details WHERE 1=1";
$params = array();
$types = "";

if(!empty($destination_filter)) {
    $sql .= " AND destination LIKE ?";
    $params[] = "%" . $destination_filter . "%";
    $types .= "s";
}

if(!empty($hotel_name_filter)) {
    $sql .= " AND hotel_name LIKE ?";
    $params[] = "%" . $hotel_name_filter . "%";
    $types .= "s";
}

if(!empty($room_category_filter)) {
    $sql .= " AND room_category LIKE ?";
    $params[] = "%" . $room_category_filter . "%";
    $types .= "s";
}

if(!empty($price_from) && is_numeric($price_from)) {
    $sql .= " AND cp >= ?";
    $params[] = floatval($price_from);
    $types .= "d";
}

if(!empty($price_to) && is_numeric($price_to)) {
    $sql .= " AND cp <= ?";
    $params[] = floatval($price_to);
    $types .= "d";
}

if(!empty($validity_from)) {
    $sql .= " AND validity_to >= ?";
    $params[] = $validity_from;
    $types .= "s";
}

if(!empty($validity_to)) {
    $sql .= " AND validity_from <= ?";
    $params[] = $validity_to;
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC";

// Execute query
if(!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $sql);
}

// Get unique destinations for filter dropdown
$destinations_sql = "SELECT DISTINCT destination FROM accommodation_details ORDER BY destination";
$destinations_result = mysqli_query($conn, $destinations_sql);

// Get unique hotel names for filter dropdown
$hotel_names_sql = "SELECT DISTINCT hotel_name FROM accommodation_details ORDER BY hotel_name";
$hotel_names_result = mysqli_query($conn, $hotel_names_sql);

// Get unique room categories for filter dropdown
$room_categories_sql = "SELECT DISTINCT room_category FROM accommodation_details ORDER BY room_category";
$room_categories_result = mysqli_query($conn, $room_categories_sql);
?>

<!-- Filter Section -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Filters</h4>
    </div>
    <div class="pb-20 pd-20">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filter-form">
            <div class="filter-row">
                <div class="form-group">
                    <label>Destination</label>
                    <select class="custom-select" name="destination_filter">
                        <option value="">All Destinations</option>
                        <?php while($dest = mysqli_fetch_assoc($destinations_result)): ?>
                            <option value="<?php echo htmlspecialchars($dest['destination']); ?>" <?php echo ($destination_filter == $dest['destination']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dest['destination']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Hotel Name</label>
                    <select class="custom-select" name="hotel_name_filter">
                        <option value="">All Hotels</option>
                        <?php while($hotel = mysqli_fetch_assoc($hotel_names_result)): ?>
                            <option value="<?php echo htmlspecialchars($hotel['hotel_name']); ?>" <?php echo ($hotel_name_filter == $hotel['hotel_name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($hotel['hotel_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Room Category</label>
                    <select class="custom-select" name="room_category_filter">
                        <option value="">All Categories</option>
                        <?php while($cat = mysqli_fetch_assoc($room_categories_result)): ?>
                            <option value="<?php echo htmlspecialchars($cat['room_category']); ?>" <?php echo ($room_category_filter == $cat['room_category']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['room_category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price From (₹)</label>
                    <input type="number" class="form-control" name="price_from" value="<?php echo htmlspecialchars($price_from); ?>" placeholder="Min Price">
                </div>
                <div class="form-group">
                    <label>Price To (₹)</label>
                    <input type="number" class="form-control" name="price_to" value="<?php echo htmlspecialchars($price_to); ?>" placeholder="Max Price">
                </div>
                <div class="form-group">
                    <label>Valid From</label>
                    <input type="date" class="form-control" name="validity_from" value="<?php echo htmlspecialchars($validity_from); ?>">
                </div>
                <div class="form-group">
                    <label>Valid To</label>
                    <input type="date" class="form-control" name="validity_to" value="<?php echo htmlspecialchars($validity_to); ?>">
                </div>
                <div class="filter-buttons">
                    <button type="submit" name="filter" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card-box mb-30">
    <div class="pd-20">
        <div class="row">
            <div class="col-md-6">
                <h4 class="text-blue h4">Accommodation Details</h4>
            </div>
            <div class="col-md-6 text-right">
                <button type="button" class="btn btn-success mr-2" data-toggle="modal" data-target="#bulkUploadModal">
                    <i class="fa fa-upload"></i> Bulk Upload
                </button>
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
                        <th style="min-width: 80px;">Star Category</th>
                        <th style="min-width: 100px;">Destination</th>
                        <th style="min-width: 120px;">Hotel Name</th>
                        <th style="min-width: 100px;">Room Category</th>
                        <th style="min-width: 80px;">Meal Type</th>
                        <th style="min-width: 100px;">Room Price Adult</th>
                        <th style="min-width: 120px;">Child With Bed</th>
                        <th style="min-width: 130px;">Child Without Bed</th>
                        <th style="min-width: 100px;">Meal Adult</th>
                        <th style="min-width: 100px;">Meal Child</th>
                        <th style="min-width: 100px;">Season Type</th>
                        <th style="min-width: 100px;">Valid From</th>
                        <th style="min-width: 100px;">Valid To</th>
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
                    <td><?php echo htmlspecialchars($row['star_category'] ?? '3 Star'); ?></td>
                    <td><?php echo htmlspecialchars($row['destination']); ?></td>
                    <td><?php echo htmlspecialchars($row['hotel_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['room_category']); ?></td>
                    <td><?php echo htmlspecialchars($row['meal_type'] ?? 'EPAI'); ?></td>
                    <td>₹<?php echo number_format($row['room_price_adult'] ?? 0, 2); ?></td>
                    <td>₹<?php echo number_format($row['room_price_child_with_bed'] ?? 0, 2); ?></td>
                    <td>₹<?php echo number_format($row['room_price_child_without_bed'] ?? 0, 2); ?></td>
                    <td>₹<?php echo number_format($row['meal_charge_adult'] ?? 0, 2); ?></td>
                    <td>₹<?php echo number_format($row['meal_charge_child'] ?? 0, 2); ?></td>
                    <td><?php echo htmlspecialchars($row['season_type'] ?? 'WINTER'); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['validity_from'])); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['validity_to'])); ?></td>
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
                    <td colspan="16" class="text-center">No accommodation details found</td>
                </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal fade" id="bulkUploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Bulk Upload Accommodation Details</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Download Sample CSV:</label>
                        <a href="?download_sample=1" class="btn btn-info btn-sm">
                            <i class="fa fa-download"></i> Download Sample
                        </a>
                    </div>
                    <div class="form-group">
                        <label>Select CSV File:</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <small class="text-muted">Please upload CSV file with proper format</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_csv" class="btn btn-primary">Upload</button>
                </div>
            </form>
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

<!-- Include filter styles -->
<link rel="stylesheet" href="assets/css/filter-styles.css">

<style>
.filter-row {
    display: flex;
    gap: 15px;
    align-items: end;
    flex-wrap: nowrap;
}

.form-group {
    min-width: 120px;
    flex: 1;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    align-items: end;
    white-space: nowrap;
}

.filter-buttons .btn {
    height: 48px;
}
</style>

<?php require_once "includes/footer.php"; ?>