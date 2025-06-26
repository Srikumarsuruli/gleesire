<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('upload_marketing_data') && $_SESSION["role_id"] != 1) {
    header("location: index.php");
    exit;
}

// Initialize variables
$upload_error = $upload_success = "";
$duplicate_error = false;

// Create tables if they don't exist
$create_files_table = "CREATE TABLE IF NOT EXISTS marketing_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    upload_date DATETIME NOT NULL
)";
mysqli_query($conn, $create_files_table);

$create_data_table = "CREATE TABLE IF NOT EXISTS marketing_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT NOT NULL,
    campaign_date DATE NULL,
    campaign_name VARCHAR(255) NOT NULL,
    amount_spent DECIMAL(10,2) NOT NULL,
    impressions INT NOT NULL,
    cpm DECIMAL(10,2) NOT NULL,
    reach INT NOT NULL,
    link_clicks INT NOT NULL,
    cpc DECIMAL(10,2) NOT NULL,
    results INT NOT NULL,
    cost_per_result DECIMAL(10,2) NOT NULL
)";
mysqli_query($conn, $create_data_table);

// Process form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["marketing_csv"])) {
    if($_FILES["marketing_csv"]["error"] == 0) {
        $file_name = $_FILES["marketing_csv"]["name"];
        $file_tmp = $_FILES["marketing_csv"]["tmp_name"];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if($file_ext == "csv") {
            // Insert file record
            $user_id = $_SESSION['id'];
            $file_sql = "INSERT INTO marketing_files (file_name, uploaded_by, upload_date) 
                         VALUES ('$file_name', $user_id, NOW())";
            if(mysqli_query($conn, $file_sql)) {
                $file_id = mysqli_insert_id($conn);
                
                // Process CSV file
                if(($handle = fopen($file_tmp, "r")) !== FALSE) {
                    // Skip header row
                    $header = fgetcsv($handle, 1000, ",");
                    
                    $row_count = 0;
                    while(($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        // Convert date format from DD/MM/YYYY to YYYY-MM-DD
                        $date_parts = explode('/', $data[0]);
                        if(count($date_parts) == 3) {
                            $campaign_date = "'" . $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0] . "'";
                        } else {
                            $campaign_date = "NULL";
                        }
                        
                        // Clean amount values (remove "Rs: " prefix)
                        $amount_spent = str_replace('Rs: ', '', $data[2]);
                        $cpc = str_replace('Rs: ', '', $data[7]);
                        $cost_per_result = str_replace('Rs: ', '', $data[9]);
                        
                        $data_sql = "INSERT INTO marketing_data 
                                    (file_id, campaign_date, campaign_name, amount_spent, impressions, cpm, reach, link_clicks, cpc, results, cost_per_result) 
                                    VALUES 
                                    ($file_id, $campaign_date, '" . mysqli_real_escape_string($conn, $data[1]) . "', 
                                    $amount_spent, " . (int)$data[3] . ", " . (float)$data[4] . ", 
                                    " . (int)$data[5] . ", " . (int)$data[6] . ", $cpc, 
                                    " . (int)$data[8] . ", $cost_per_result)";
                        
                        mysqli_query($conn, $data_sql);
                        $row_count++;
                    }
                    
                    fclose($handle);
                    $upload_success = "File uploaded successfully. Imported $row_count records.";
                } else {
                    $upload_error = "Could not open the CSV file.";
                }
            } else {
                $upload_error = "Error saving file information.";
            }
        } else {
            $upload_error = "Only CSV files are allowed.";
        }
    } else {
        $upload_error = "Error uploading file. Please try again.";
    }
}

// Get all uploaded files
$files_sql = "SELECT mf.*, u.username as uploaded_by_name, 
            DATE_FORMAT(mf.upload_date, '%d/%m/%Y %H:%i') as formatted_date
            FROM marketing_files mf
            JOIN users u ON mf.uploaded_by = u.id
            ORDER BY mf.upload_date DESC";
$files_result = mysqli_query($conn, $files_sql);
?>



<!-- Upload Section -->
<div class="pd-20 card-box mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h4 class="text-blue h4">Upload Marketing CSV</h4>
            <p class="mb-30">Upload your marketing campaign data in CSV format</p>
        </div>
        <div class="pull-right">
            <a href="downloads/marketing_data_sample.csv" class="btn btn-primary btn-sm scroll-click">
                <i class="fa fa-download"></i> Download Sample CSV
            </a>
        </div>
    </div>
    
    <?php if(!empty($upload_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error!</strong> <?php echo $upload_error; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    
    <?php if(!empty($upload_success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> <?php echo $upload_success; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
        <div class="form-group row">
            <label class="col-sm-12 col-md-2 col-form-label">Select CSV File</label>
            <div class="col-sm-12 col-md-10">
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="marketing_csv" name="marketing_csv" required>
                    <label class="custom-file-label" for="marketing_csv">Choose file</label>
                </div>
                <small class="form-text text-muted">
                    File should contain columns: Date, Campaign name, Amount spent, Impressions, CPM, Reach, Link clicks, CPC, Results, Cost per results
                </small>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-12 col-md-10 offset-md-2">
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </div>
    </form>
</div>

<!-- Uploaded Files Section -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Uploaded Marketing Data</h4>
    </div>
    <div class="pb-20">
        <table class="data-table table stripe hover nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>File Name</th>
                    <th>Uploaded By</th>
                    <th>Upload Date</th>
                    <th class="datatable-nosort">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if($files_result && mysqli_num_rows($files_result) > 0):
                $counter = 1;
                while($file = mysqli_fetch_assoc($files_result)): 
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                    <td><?php echo htmlspecialchars($file['uploaded_by_name']); ?></td>
                    <td><?php echo $file['formatted_date']; ?></td>
                    <td>
                        <div class="dropdown">
                            <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                <i class="dw dw-more"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                <a class="dropdown-item" href="view_marketing_data.php?file_id=<?php echo $file['id']; ?>"><i class="dw dw-eye"></i> View</a>
                                <a class="dropdown-item" href="download_marketing_data.php?file_id=<?php echo $file['id']; ?>"><i class="dw dw-download"></i> Download</a>
                                <a class="dropdown-item delete-file" href="#" data-id="<?php echo $file['id']; ?>" data-toggle="modal" data-target="#confirmation-modal"><i class="dw dw-delete-3"></i> Delete</a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="confirmation-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center font-18">
                <h4 class="padding-top-30 mb-30 weight-500">Are you sure you want to delete this file?</h4>
                <div class="padding-bottom-30 row" style="max-width: 170px; margin: 0 auto;">
                    <div class="col-6">
                        <button type="button" class="btn btn-secondary border-radius-100 btn-block confirmation-btn" data-dismiss="modal"><i class="fa fa-times"></i></button>
                        Cancel
                    </div>
                    <div class="col-6">
                        <form id="delete-form" action="delete_marketing_file.php" method="post">
                            <input type="hidden" name="file_id" id="delete_file_id" value="">
                            <button type="submit" class="btn btn-danger border-radius-100 btn-block confirmation-btn"><i class="fa fa-check"></i></button>
                            Delete
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add JavaScript for file input and delete confirmation -->
<script>
$(document).ready(function() {
    // Show filename in custom file input
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
    
    // Set file ID for delete confirmation
    $(".delete-file").click(function() {
        var fileId = $(this).data('id');
        $("#delete_file_id").val(fileId);
    });
});
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>