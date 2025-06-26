<?php
// Include header
require_once "includes/header.php";

// Check if user has privilege to access this page
if(!hasPrivilege('view_leads')) {
    header("location: index.php");
    exit;
}

// Define variables for filtering
$attended_by = $file_manager = $lead_type = $lead_status = $date_filter = "";
$start_date = $end_date = "";

// Process filter form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["filter"])) {
    $attended_by = !empty($_POST["attended_by"]) ? $_POST["attended_by"] : "";
    $file_manager = !empty($_POST["file_manager"]) ? $_POST["file_manager"] : "";
    $lead_type = !empty($_POST["lead_type"]) ? $_POST["lead_type"] : "";
    $lead_status = !empty($_POST["lead_status"]) ? $_POST["lead_status"] : "";
    $date_filter = !empty($_POST["date_filter"]) ? $_POST["date_filter"] : "";
    
    if($date_filter == "custom" && !empty($_POST["start_date"]) && !empty($_POST["end_date"])) {
        $start_date = $_POST["start_date"];
        $end_date = $_POST["end_date"];
    }
}

// Create lead_status_map table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS lead_status_map (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enquiry_id INT(11) NOT NULL,
    status_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (enquiry_id)
)";
mysqli_query($conn, $create_table_sql);

// Check for converted enquiries that are not in leads and add them
$check_sql = "SELECT e.* FROM enquiries e 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id 
        WHERE e.status_id = 3 AND cl.enquiry_id IS NULL";

$check_result = mysqli_query($conn, $check_sql);

if(mysqli_num_rows($check_result) > 0) {
    while($enquiry = mysqli_fetch_assoc($check_result)) {
        // Generate enquiry number
        $enquiry_number = 'GH ' . sprintf('%04d', rand(1, 9999));
        
        // Insert into converted_leads table
        $insert_sql = "INSERT INTO converted_leads (enquiry_id, enquiry_number, travel_start_date, travel_end_date, booking_confirmed) 
                      VALUES (?, ?, NULL, NULL, 0)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "is", $enquiry['id'], $enquiry_number);
        mysqli_stmt_execute($insert_stmt);
    }
}



// Build the SQL query with filters
$sql = "SELECT e.*, u.full_name as attended_by_name, d.name as department_name, 
        s.name as source_name, ac.name as campaign_name, ls.name as status_name,
        cl.enquiry_number, cl.travel_start_date, cl.travel_end_date, cl.booking_confirmed,
        lsm.status_name as lead_status
        FROM enquiries e 
        JOIN users u ON e.attended_by = u.id 
        JOIN departments d ON e.department_id = d.id 
        JOIN sources s ON e.source_id = s.id 
        LEFT JOIN ad_campaigns ac ON e.ad_campaign_id = ac.id 
        JOIN lead_status ls ON e.status_id = ls.id 
        LEFT JOIN converted_leads cl ON e.id = cl.enquiry_id
        LEFT JOIN lead_status_map lsm ON e.id = lsm.enquiry_id
        WHERE e.status_id = 3 AND (cl.booking_confirmed = 0 OR cl.booking_confirmed IS NULL)"; // Only converted leads that are not yet confirmed

$params = array();
$types = "";

if(!empty($attended_by)) {
    $sql .= " AND e.attended_by = ?";
    $params[] = $attended_by;
    $types .= "i";
}

if(!empty($file_manager)) {
    $sql .= " AND cl.file_manager_id = ?";
    $params[] = $file_manager;
    $types .= "i";
}

if(!empty($lead_type)) {
    $sql .= " AND cl.lead_type = ?";
    $params[] = $lead_type;
    $types .= "s";
}

if(!empty($lead_status)) {
    $sql .= " AND lsm.status_name = ?";
    $params[] = $lead_status;
    $types .= "s";
}

if(!empty($date_filter)) {
    switch($date_filter) {
        case "today":
            $sql .= " AND DATE(e.received_datetime) = CURDATE()";
            break;
        case "yesterday":
            $sql .= " AND DATE(e.received_datetime) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case "this_week":
            $sql .= " AND YEARWEEK(e.received_datetime) = YEARWEEK(NOW())";
            break;
        case "this_month":
            $sql .= " AND MONTH(e.received_datetime) = MONTH(NOW()) AND YEAR(e.received_datetime) = YEAR(NOW())";
            break;
        case "this_year":
            $sql .= " AND YEAR(e.received_datetime) = YEAR(NOW())";
            break;
        case "custom":
            if(!empty($start_date) && !empty($end_date)) {
                $sql .= " AND DATE(e.received_datetime) BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
                $types .= "ss";
            }
            break;
    }
}

// Add order by clause
$sql .= " ORDER BY e.received_datetime DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);

if(!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get users for filter dropdown
$users_sql = "SELECT * FROM users ORDER BY full_name";
$users = mysqli_query($conn, $users_sql);

// Get file managers for filter dropdown (same as users)
$file_managers = $users;

// Get lead statuses for filter dropdown
$statuses_sql = "SELECT * FROM lead_status ORDER BY id";
$statuses = mysqli_query($conn, $statuses_sql);

// Get lead status options for filter dropdown
$lead_status_options = [
    "Hot Prospect - Quote given",
    "Prospect - Attended",
    "Prospect - Awaiting Rate from Agent",
    "Neutral Prospect - In Discussion",
    "Future Hot Prospect - Quote Given (with delay)",
    "Future Prospect - Postponed",
    "Call Back - Call Back Scheduled",
    "Re-Opened - Re-Engaged Lead",
    "Re-Assigned - Transferred Lead",
    "Not Connected - No Response",
    "Not Interested - Cancelled",
    "Junk - Junk",
    "Duplicate - Duplicate",
    "Closed – Booked",
    "Change Request – Active Amendment",
    "Booking Value - Sale Amount"
];

// Check for confirmation message
$confirmation_message = "";
if(isset($_GET["confirmed"]) && $_GET["confirmed"] == 1) {
    $confirmation_message = "<div class='alert alert-success'>Lead successfully moved to Booking Confirmed.</div>";
} else if(isset($_GET["status_updated"]) && $_GET["status_updated"] == 1) {
    $confirmation_message = "<div class='alert alert-success'>Lead status updated successfully.</div>";
} else if(isset($_GET["error"]) && $_GET["error"] == 1) {
    $confirmation_message = "<div class='alert alert-danger'>Error updating lead status.</div>";
} else if(isset($_GET["error"]) && $_GET["error"] == 2) {
    $confirmation_message = "<div class='alert alert-danger'>Invalid request.</div>";
}
?>



<?php if(!empty($confirmation_message)): ?>
<div class="row">
    <div class="col-md-12">
        <?php echo $confirmation_message; ?>
    </div>
</div>
<?php endif; ?>

<!-- Include filter styles -->
<link rel="stylesheet" href="assets/css/filter-styles.css">

<!-- Filter Section -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Filters</h4>
    </div>
    <div class="pb-20 pd-20">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filter-form">
            <div class="filter-row">
                <div class="form-group">
                    <label>Attended By</label>
                    <select class="custom-select" id="attended-by-filter" name="attended_by">
                        <option value="">All</option>
                        <?php mysqli_data_seek($users, 0); while($user = mysqli_fetch_assoc($users)): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo ($attended_by == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>File Manager</label>
                    <select class="custom-select" id="file-manager-filter" name="file_manager">
                        <option value="">All</option>
                        <?php mysqli_data_seek($file_managers, 0); while($manager = mysqli_fetch_assoc($file_managers)): ?>
                            <option value="<?php echo $manager['id']; ?>" <?php echo ($file_manager == $manager['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($manager['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lead Type</label>
                    <select class="custom-select" id="lead-type-filter" name="lead_type">
                        <option value="">All</option>
                        <option value="Hot" <?php echo ($lead_type == "Hot") ? 'selected' : ''; ?>>Hot</option>
                        <option value="Warm" <?php echo ($lead_type == "Warm") ? 'selected' : ''; ?>>Warm</option>
                        <option value="Cold" <?php echo ($lead_type == "Cold") ? 'selected' : ''; ?>>Cold</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lead Status</label>
                    <select class="custom-select" id="lead-status-filter" name="lead_status">
                        <option value="">All</option>
                        <?php foreach($lead_status_options as $option): ?>
                            <option value="<?php echo $option; ?>" <?php echo ($lead_status == $option) ? 'selected' : ''; ?>>
                                <?php echo $option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date Filter</label>
                    <select class="custom-select" id="date-filter" name="date_filter">
                        <option value="">All Time</option>
                        <option value="today" <?php echo ($date_filter == "today") ? 'selected' : ''; ?>>Today</option>
                        <option value="yesterday" <?php echo ($date_filter == "yesterday") ? 'selected' : ''; ?>>Yesterday</option>
                        <option value="this_week" <?php echo ($date_filter == "this_week") ? 'selected' : ''; ?>>This Week</option>
                        <option value="this_month" <?php echo ($date_filter == "this_month") ? 'selected' : ''; ?>>This Month</option>
                        <option value="this_year" <?php echo ($date_filter == "this_year") ? 'selected' : ''; ?>>This Year</option>
                        <option value="custom" <?php echo ($date_filter == "custom") ? 'selected' : ''; ?>>Custom Range</option>
                    </select>
                </div>
                <div id="custom-date-range" class="custom-date-range" <?php echo ($date_filter != "custom") ? 'style="display: none;"' : ''; ?>>
                    <div class="form-group">
                        <label for="start-date">Start Date</label>
                        <input type="date" class="form-control" id="start-date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end-date">End Date</label>
                        <input type="date" class="form-control" id="end-date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                </div>
                <div class="filter-buttons">
                    <button type="submit" name="filter" class="btn btn-primary">Apply Filters</button>
                    <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Leads Table -->
<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Leads</h4>
    </div>
    <div class="pb-20">
        <table class="data-table table stripe hover nowrap">
            <thead>
                <tr>
                    <th>Enquiry #</th>
                    <th>Lead #</th>
                    <th>Customer Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Source</th>
                    <th>Campaign</th>
                    <th>Lead Status</th>
                    <th>Received Date</th>
                    <th>Attended By</th>
                    <th>Department</th>
                    <th>Enquiries Status</th>
                    <th class="datatable-nosort">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr data-id="<?php echo $row['id']; ?>" class="<?php echo (isset($_GET['highlight']) && $_GET['highlight'] == $row['id']) ? 'highlight-row' : ''; ?>">
                    <td><?php echo htmlspecialchars($row['lead_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['enquiry_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['source_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['campaign_name'] ?? 'N/A'); ?></td>
                    <td>
                        <div class="d-flex align-items-center">
                            <form method="post" action="save_status.php" style="display: flex; align-items: center;">
                                <?php 
                                // Get the current status directly from the database for this row
                                $status_check_sql = "SELECT status_name FROM lead_status_map WHERE enquiry_id = " . $row['id'];
                                $status_check_result = mysqli_query($conn, $status_check_sql);
                                $db_status = '';
                                if ($status_check_result && mysqli_num_rows($status_check_result) > 0) {
                                    $status_row = mysqli_fetch_assoc($status_check_result);
                                    $db_status = $status_row['status_name'];
                                }
                                ?>
                                <input type="hidden" name="enquiry_id" value="<?php echo $row['id']; ?>">
                                <select class="custom-select" name="status" style="min-width: 200px;">
                                    <option value="">Select Status</option>
                                    <option value="Hot Prospect - Quote given" <?php echo ($db_status == "Hot Prospect - Quote given") ? 'selected' : ''; ?>>Hot Prospect - Quote given</option>
                                    <option value="Prospect - Attended" <?php echo ($db_status == "Prospect - Attended") ? 'selected' : ''; ?>>Prospect - Attended</option>
                                    <option value="Prospect - Awaiting Rate from Agent" <?php echo ($db_status == "Prospect - Awaiting Rate from Agent") ? 'selected' : ''; ?>>Prospect - Awaiting Rate from Agent</option>
                                    <option value="Neutral Prospect - In Discussion" <?php echo ($db_status == "Neutral Prospect - In Discussion") ? 'selected' : ''; ?>>Neutral Prospect - In Discussion</option>
                                    <option value="Future Hot Prospect - Quote Given (with delay)" <?php echo ($db_status == "Future Hot Prospect - Quote Given (with delay)") ? 'selected' : ''; ?>>Future Hot Prospect - Quote Given (with delay)</option>
                                    <option value="Future Prospect - Postponed" <?php echo ($db_status == "Future Prospect - Postponed") ? 'selected' : ''; ?>>Future Prospect - Postponed</option>
                                    <option value="Call Back - Call Back Scheduled" <?php echo ($db_status == "Call Back - Call Back Scheduled") ? 'selected' : ''; ?>>Call Back - Call Back Scheduled</option>
                                    <option value="Re-Opened - Re-Engaged Lead" <?php echo ($db_status == "Re-Opened - Re-Engaged Lead") ? 'selected' : ''; ?>>Re-Opened - Re-Engaged Lead</option>
                                    <option value="Re-Assigned - Transferred Lead" <?php echo ($db_status == "Re-Assigned - Transferred Lead") ? 'selected' : ''; ?>>Re-Assigned - Transferred Lead</option>
                                    <option value="Not Connected - No Response" <?php echo ($db_status == "Not Connected - No Response") ? 'selected' : ''; ?>>Not Connected - No Response</option>
                                    <option value="Not Interested - Cancelled" <?php echo ($db_status == "Not Interested - Cancelled") ? 'selected' : ''; ?>>Not Interested - Cancelled</option>
                                    <option value="Junk - Junk" <?php echo ($db_status == "Junk - Junk") ? 'selected' : ''; ?>>Junk - Junk</option>
                                    <option value="Duplicate - Duplicate" <?php echo ($db_status == "Duplicate - Duplicate") ? 'selected' : ''; ?>>Duplicate - Duplicate</option>
                                    <option value="Closed – Booked" <?php echo ($db_status == "Closed – Booked") ? 'selected' : ''; ?>>Closed – Booked</option>
                                    <option value="Change Request – Active Amendment" <?php echo ($db_status == "Change Request – Active Amendment") ? 'selected' : ''; ?>>Change Request – Active Amendment</option>
                                    <option value="Booking Value - Sale Amount" <?php echo ($db_status == "Booking Value - Sale Amount") ? 'selected' : ''; ?>>Booking Value - Sale Amount</option>
                                </select>
                                <button type="submit" class="btn btn-link p-0 ml-2">
                                    <i class="icon-copy fa fa-check text-success" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                    <td><?php echo date('d-m-Y H:i', strtotime($row['received_datetime'])); ?></td>
                    <td><?php echo htmlspecialchars($row['attended_by_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['status_name']); ?></td>
                    <td>
                        <div class="dropdown">
                            <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                <i class="dw dw-more"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                <a class="dropdown-item" href="edit_enquiry.php?id=<?php echo $row['id']; ?>"><i class="dw dw-edit2"></i> Edit</a>
                                <a class="dropdown-item" href="#" onclick="moveToConfirmed(<?php echo $row['id']; ?>); return false;"><i class="dw dw-check"></i> Move to Confirmed</a>
                                <a class="dropdown-item" href="comments.php?id=<?php echo $row['id']; ?>&type=lead"><i class="dw dw-chat"></i> Comments</a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Comment Modal -->
<div class="modal fade" id="add-comment-modal" tabindex="-1" role="dialog" aria-labelledby="add-comment-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="add-comment-modal-title">Comments</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="comments-container">
                    <!-- Comments will be loaded here -->
                </div>
                <hr>
                <form id="comment-form">
                    <input type="hidden" name="enquiry_id" id="enquiry-id">
                    <input type="hidden" name="table_id" id="table-id">
                    <div class="form-group">
                        <label for="comment">Add Comment</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Comment</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function saveStatus(link, id) {
    var status = $('.lead-status-select[data-id="' + id + '"]').val();
    if (!status) {
        alert('Please select a status first');
        return false;
    }
    link.href = 'direct_save_status.php?id=' + id + '&status=' + encodeURIComponent(status);
    return true;
}

$(document).ready(function() {
    // Direct click handler for save buttons
    $('.save-status-btn').click(function() {
        var enquiryId = $(this).data('id');
        var status = $('.lead-status-select[data-id="' + enquiryId + '"]').val();
        
        if (!status) {
            alert('Please select a status');
            return;
        }
        
        var $button = $(this);
        var $icon = $button.find('i');
        
        // Show loading
        $icon.removeClass('fa-check').addClass('fa-spinner fa-spin');
        
        // Simple AJAX request
        $.post('save_lead_status.php', {
            enquiry_id: enquiryId,
            status: status
        }, function(response) {
            if (response.indexOf('success') !== -1) {
                // Success
                $icon.removeClass('fa-spinner fa-spin').addClass('fa-check');
            } else {
                // Error
                alert('Error saving status: ' + response);
                $icon.removeClass('fa-spinner fa-spin').addClass('fa-times');
                setTimeout(function() {
                    $icon.removeClass('fa-times').addClass('fa-check');
                }, 2000);
            }
        });
    });
});
</script>
<script>
    // Show/hide custom date range based on date filter selection
    document.getElementById('date-filter').addEventListener('change', function() {
        var customDateRange = document.getElementById('custom-date-range');
        if (this.value === 'custom') {
            customDateRange.style.display = 'flex';
        } else {
            customDateRange.style.display = 'none';
        }
    });
    
    // Function to move lead to confirmed
    function moveToConfirmed(id) {
        if (confirm('Are you sure you want to move this lead to Booking Confirmed?')) {
            // Create form data
            var formData = new FormData();
            formData.append('enquiry_id', id);
            
            // Send AJAX request
            fetch('move_to_confirmed.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                alert('Lead successfully moved to Booking Confirmed');
                // Reload the page to refresh the table
                window.location.href = 'view_leads.php?confirmed=1';
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        }
    }
    
    // Set enquiry ID in comment modal and load comments
    $(document).ready(function() {
        
        $('.comment-link').on('click', function(e) {
            var enquiryId = $(this).data('id');
            var customerName = $(this).data('customer');
            console.log("Comment link clicked for enquiry ID:", enquiryId, "Customer:", customerName);
            
            // Set the modal title with customer name
            $('#add-comment-modal-title').text('Comments for ' + customerName);
            
            // Set form values
            $('#enquiry-id').val(enquiryId);
            $('#table-id').val('enquiries');
            
            // Load existing comments
            loadComments(enquiryId);
        });
        
        $('#add-comment-modal').on('show.bs.modal', function (event) {
            // Modal is being shown - nothing additional needed here
            // The click handler above will have already set everything up
        });
        
        // Function to load comments
        function loadComments(enquiryId) {
            console.log("Loading comments for enquiry ID:", enquiryId);
            $('#comments-container').html('<p>Loading comments...</p>');
            
            $.ajax({
                url: 'get_comments.php',
                type: 'GET',
                data: {
                    enquiry_id: enquiryId
                },
                success: function(response) {
                    console.log("Response received:", response);
                    try {
                        var data = JSON.parse(response);
                        console.log("Parsed data:", data);
                        if (data.success) {
                            var commentsHtml = '';
                            if (data.comments && data.comments.length > 0) {
                                data.comments.forEach(function(comment) {
                                    commentsHtml += '<div class="comment-box">' +
                                        '<div class="comment-header">' +
                                        '<span class="comment-user">' + comment.user_name + '</span>' +
                                        '<span class="comment-date">' + comment.created_at + '</span>' +
                                        '</div>' +
                                        '<div class="comment-body">' + comment.comment.replace(/\n/g, '<br>') + '</div>' +
                                        '</div>';
                                });
                            } else {
                                commentsHtml = '<p class="text-muted">No comments yet.</p>';
                            }
                            $('#comments-container').html(commentsHtml);
                        } else {
                            $('#comments-container').html('<p class="text-danger">Error loading comments: ' + data.message + '</p>');
                        }
                    } catch (e) {
                        console.error("Error parsing JSON:", e, response);
                        $('#comments-container').html('<p class="text-danger">Error processing response</p>');
                    }
                },
                error: function() {
                    $('#comments-container').html('<p class="text-danger">Error loading comments</p>');
                }
            });
        }
        
        // Initialize dropdown functionality
        $('.dropdown-toggle').dropdown();
        
        // Handle form submission via AJAX
        $('#comment-form').on('submit', function(e) {
            e.preventDefault();
            var enquiryId = $('#enquiry-id').val();
            var comment = $('#comment').val();
            
            $.ajax({
                url: 'add_comment.php',
                type: 'POST',
                data: {
                    enquiry_id: enquiryId,
                    comment: comment,
                    table_id: $('#table-id').val()
                },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);
                        if (data.success) {
                            // Clear the textarea
                            $('#comment').val('');
                            
                            // Reload comments
                            loadComments(enquiryId);
                        } else {
                            alert('Error: ' + data.message);
                        }
                    } catch (e) {
                        alert('Error processing response');
                    }
                },
                error: function() {
                    alert('Error submitting comment');
                }
            });
        });
    });
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>