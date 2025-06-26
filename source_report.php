<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('reports')) {
    header("location: index.php");
    exit;
}

// Define variables for filtering
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $start_date = !empty($_POST["start_date"]) ? $_POST["start_date"] : date('Y-m-d', strtotime('-30 days'));
    $end_date = !empty($_POST["end_date"]) ? $_POST["end_date"] : date('Y-m-d');
}

// Get all sources
$sources_sql = "SELECT * FROM sources ORDER BY name";
$sources_result = mysqli_query($conn, $sources_sql);
$sources = [];

while($source = mysqli_fetch_assoc($sources_result)) {
    $sources[] = $source;
}

// Get report data
$report_data = [];
$date_range = [];

// Generate date range
$current_date = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);
$end_date_obj->modify('+1 day'); // Include end date

while($current_date < $end_date_obj) {
    $date_str = $current_date->format('Y-m-d');
    $date_display = $current_date->format('d-M-y');
    $date_range[$date_str] = $date_display;
    $report_data[$date_str] = [];
    
    // Initialize counts for each source
    foreach($sources as $source) {
        $report_data[$date_str][$source['id']] = 0;
    }
    
    $current_date->modify('+1 day');
}

// Get enquiry counts by date and source
$sql = "SELECT DATE(received_datetime) as date, source_id, COUNT(*) as count 
        FROM enquiries 
        WHERE DATE(received_datetime) BETWEEN ? AND ?
        GROUP BY DATE(received_datetime), source_id
        ORDER BY DATE(received_datetime)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while($row = mysqli_fetch_assoc($result)) {
    $date = $row['date'];
    $source_id = $row['source_id'];
    $count = $row['count'];
    
    if(isset($report_data[$date][$source_id])) {
        $report_data[$date][$source_id] = $count;
    }
}
?>


<!-- Filter Section -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Date Range Filter</h4>
    </div>
    <div class="pb-20 pd-20">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filter-form">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="start-date">Start Date</label>
                        <input type="date" class="form-control" id="start-date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="end-date">End Date</label>
                        <input type="date" class="form-control" id="end-date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-group mb-0">
                        <button type="submit" name="filter" class="btn btn-primary">Apply Filter</button>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary ml-2">Reset</a>
                        <button type="button" id="export-excel" class="btn btn-success ml-2">Export to Excel</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Source Wise Report</h4>
    </div>
    <div class="pb-20">
        <div class="table-responsive">
            <table class="table table-bordered" id="report-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <?php foreach($sources as $source): ?>
                        <th><?php echo htmlspecialchars($source['name']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($report_data as $date => $source_counts): ?>
                    <tr>
                        <td><?php echo $date_range[$date]; ?></td>
                        <?php foreach($sources as $source): ?>
                        <td><?php echo $source_counts[$source['id']]; ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- SheetJS library for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    document.getElementById('export-excel').addEventListener('click', function() {
        // Get the table
        var table = document.getElementById('report-table');
        
        // Create workbook and worksheet
        var wb = XLSX.utils.book_new();
        var ws = XLSX.utils.table_to_sheet(table);
        
        // Add worksheet to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Source Report');
        
        // Generate filename with date range
        var startDate = document.getElementById('start-date').value;
        var endDate = document.getElementById('end-date').value;
        var filename = 'Source_Report_' + startDate + '_to_' + endDate + '.xlsx';
        
        // Export to Excel
        XLSX.writeFile(wb, filename);
    });
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>