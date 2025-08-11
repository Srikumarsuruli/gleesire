<?php
// Include header
require_once "includes/header.php";

// Allow all logged-in users to access this page

// Check if file_id is provided
if(!isset($_GET['file_id']) || empty($_GET['file_id'])) {
    header("location: upload_marketing_data.php");
    exit;
}

$file_id = $_GET['file_id'];

// Get file details
$file_sql = "SELECT mf.*, u.username as uploaded_by_name, 
             DATE_FORMAT(mf.upload_date, '%d/%m/%Y %H:%i') as formatted_date
             FROM marketing_files mf
             JOIN users u ON mf.uploaded_by = u.id
             WHERE mf.id = ?";
$file_stmt = mysqli_prepare($conn, $file_sql);
mysqli_stmt_bind_param($file_stmt, "i", $file_id);
mysqli_stmt_execute($file_stmt);
$file_result = mysqli_stmt_get_result($file_stmt);

if(mysqli_num_rows($file_result) == 0) {
    header("location: upload_marketing_data.php");
    exit;
}

$file = mysqli_fetch_assoc($file_result);

// Get marketing data
$data_sql = "SELECT * FROM marketing_data 
             WHERE file_id = ? 
             ORDER BY campaign_date DESC";
$data_stmt = mysqli_prepare($conn, $data_sql);
mysqli_stmt_bind_param($data_stmt, "i", $file_id);
mysqli_stmt_execute($data_stmt);
$data_result = mysqli_stmt_get_result($data_stmt);
?>



<!-- File Details Section -->
<div class="pd-20 card-box mb-30">
    <div class="clearfix">
        <div class="pull-left">
            <h4 class="text-blue h4"><?php echo htmlspecialchars($file['file_name']); ?></h4>
        </div>
        <div class="pull-right">
            <a href="upload_marketing_data.php" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
            <a href="download_marketing_data.php?file_id=<?php echo $file_id; ?>" class="btn btn-primary btn-sm"><i class="fa fa-download"></i> Download</a>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4">
            <p><strong>Uploaded By:</strong> <?php echo htmlspecialchars($file['uploaded_by_name']); ?></p>
        </div>
        <div class="col-md-4">
            <p><strong>Upload Date:</strong> <?php echo $file['formatted_date']; ?></p>
        </div>
        <div class="col-md-4">
            <p><strong>Records:</strong> <?php echo mysqli_num_rows($data_result); ?></p>
        </div>
    </div>
</div>

<!-- Marketing Data Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Marketing Campaign Data</h4>
    </div>
    <div class="pb-20">
        <table class="data-table table stripe hover nowrap">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Campaign Name</th>
                    <th>Amount Spent (INR)</th>
                    <th>Impressions</th>
                    <th>CPM (INR)</th>
                    <th>Reach</th>
                    <th>Link Clicks</th>
                    <th>CPC (INR)</th>
                    <th>Results</th>
                    <th>Cost per Result (INR)</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $counter = 1;
                mysqli_data_seek($data_result, 0);
                while($row = mysqli_fetch_assoc($data_result)): 
                    // Format date from YYYY-MM-DD to DD/MM/YYYY
                    $campaign_date = !empty($row['campaign_date']) ? 
                        date('d/m/Y', strtotime($row['campaign_date'])) : '-';
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo $campaign_date; ?></td>
                    <td><?php echo htmlspecialchars($row['campaign_name']); ?></td>
                    <td>Rs: <?php echo number_format($row['amount_spent'], 2); ?></td>
                    <td><?php echo number_format($row['impressions']); ?></td>
                    <td><?php echo number_format($row['cpm'], 2); ?></td>
                    <td><?php echo number_format($row['reach']); ?></td>
                    <td><?php echo number_format($row['link_clicks']); ?></td>
                    <td>Rs: <?php echo number_format($row['cpc'], 2); ?></td>
                    <td><?php echo number_format($row['results']); ?></td>
                    <td>Rs: <?php echo number_format($row['cost_per_result'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>