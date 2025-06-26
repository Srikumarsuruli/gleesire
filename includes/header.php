<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Include database connection
require_once "config/database.php";

// Get user profile image
$profile_image = null;
$sql = "SELECT profile_image FROM users WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $profile_image = $row["profile_image"];
        }
    }
    mysqli_stmt_close($stmt);
}

// Function to check if user is admin
function isAdmin() {
    return $_SESSION["role_id"] == 1;
}

// Function to check user privileges
function hasPrivilege($menu, $action = 'view') {
    global $conn;
    $role_id = $_SESSION["role_id"];
    
    // Admin has all privileges
    if($role_id == 1) {
        return true;
    }
    
    // Check if the table exists
    $table_exists = mysqli_query($conn, "SHOW TABLES LIKE 'user_privileges'");
    if(mysqli_num_rows($table_exists) == 0) {
        return false;
    }
    
    $column = "can_" . $action;
    $sql = "SELECT $column FROM user_privileges WHERE role_id = ? AND menu_name = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $role_id, $menu);
        
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1) {
                $row = mysqli_fetch_assoc($result);
                return $row[$column] == 1;
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Basic Page Info -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Lead Management System</title>
    
    <!-- Site favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/deskapp/vendors/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/deskapp/vendors/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/deskapp/vendors/images/favicon-16x16.png">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Critical CSS -->
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/style.css">
    <link rel="stylesheet" type="text/css" href="assets/css/custom.css">
    <!-- Non-critical CSS will be loaded by fast-load.js -->
        <!-- Inline critical CSS for faster rendering -->
    <style>
        body {display: block !important;}
        .header, .left-side-bar {position: fixed;}
        .main-container {padding-top: 60px;}
    </style>
    <!-- Search functionality -->
    <script src="assets/js/search.js"></script>
    <!-- Row highlighting -->
    <script src="assets/js/highlight-row.js"></script>
    <!-- Comments functionality -->
    <script src="assets/js/comments.js"></script>
</head>
<body>
    <!-- Preloader removed for faster page loading -->

    <div class="header">
        <div class="header-left">
            <div class="menu-icon bi bi-list"></div>
            <div class="search-toggle-icon bi bi-search" data-toggle="header_search"></div>
            <div class="header-search">
                <form>
                    <div class="form-group mb-0">
                        <i class="dw dw-search2 search-icon"></i>
                        <input type="text" class="form-control search-input" placeholder="Search lead/enquiry/file number">
                    </div>
                </form>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info-dropdown">
                <div class="dropdown">
                    <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                        <span class="user-icon">
                            <?php if(!empty($profile_image)): ?>
                                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                            <?php else: ?>
                                <img src="assets/deskapp/vendors/images/photo1.jpg" alt="Profile">
                            <?php endif; ?>
                        </span>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION["username"]); ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                        <a class="dropdown-item" href="profile.php"><i class="dw dw-user1"></i> Profile</a>
                        <a class="dropdown-item" href="logout.php"><i class="dw dw-logout"></i> Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="left-side-bar">
        <div class="brand-logo">
            <a href="index.php">
                <img src="assets/deskapp/vendors/images/custom-logo.svg" alt="" class="dark-logo">
                <img src="assets/deskapp/vendors/images/custom-logo.svg" alt="" class="light-logo">
            </a>
            <div class="close-sidebar" data-toggle="left-sidebar-close">
                <i class="ion-close-round"></i>
            </div>
        </div>
        <div class="menu-block customscroll">
            <div class="sidebar-menu">
                <ul id="accordion-menu">
                    <li>
                        <a href="index.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-house"></span><span class="mtext">Dashboard</span>
                        </a>
                    </li>
                    <?php if(hasPrivilege('upload_enquiries')): ?>
                    <li>
                        <a href="upload_enquiries.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-upload"></span><span class="mtext">Upload Enquiries</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('view_enquiries')): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-list-ul"></span><span class="mtext">Enquiries</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="view_enquiries.php">All Enquiries</a></li>
                            <li><a href="under_construction.php?page=Job Enquiries">Job Enquiries</a></li>
                            <li><a href="under_construction.php?page=Ticket Enquiries">Ticket Enquiries</a></li>
                            <li><a href="under_construction.php?page=Influencer Enquiries">Influencer Enquiries</a></li>
                            <li><a href="under_construction.php?page=DMC/Agent Enquiries">DMC/Agent Enquiries</a></li>
                            <li><a href="under_construction.php?page=Cruise Enquiries">Cruise Enquiries</a></li>
                            <li><a href="under_construction.php?page=Lost to Competitors">Lost to Competitors</a></li>
                            <li><a href="under_construction.php?page=No Response/Rejected Enquiries">No Response/Rejected Enquiries</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('view_leads')): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-funnel"></span><span class="mtext">Leads</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="view_leads.php">All Leads</a></li>
                            <li><a href="under_construction.php?page=Fixed Package Lead">Fixed Package Lead</a></li>
                            <li><a href="under_construction.php?page=Custom Package Leads">Custom Package Leads</a></li>
                            <li><a href="under_construction.php?page=Medical Tourism Leads">Medical Tourism Leads</a></li>
                            <li><a href="under_construction.php?page=No Response/Rejected Leads">No Response/Rejected Leads</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('pipeline')): ?>
                    <li>
                        <a href="pipeline.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-diagram-3"></span><span class="mtext">Pipeline</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('booking_confirmed')): ?>
                    <li>
                        <a href="booking_confirmed.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-check-circle"></span><span class="mtext">Booking Confirmed</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li>
                        <a href="under_construction.php?page=Travel Completed" class="dropdown-toggle no-arrow">
                            <span class="micon dw dw-checked"></span><span class="mtext">Travel Completed</span>
                        </a>
                    </li>
                    <?php if(hasPrivilege('view_cost_sheets')): ?>
                    <li>
                        <a href="view_cost_sheets.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-file-earmark-text"></span><span class="mtext">View Cost Sheets</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-calendar-check"></span><span class="mtext">Reservation/Booking</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="under_construction.php?page=Hotel/Resorts">Hotel/Resorts</a></li>
                            <li><a href="under_construction.php?page=Cruise">Cruise</a></li>
                            <li><a href="under_construction.php?page=Visa & Air Ticket">Visa & Air Ticket</a></li>
                            <li><a href="under_construction.php?page=Transportation">Transportation</a></li>
                            <li><a href="under_construction.php?page=Feedbacks"><i class="dw dw-chat3"></i> Feedbacks</a></li>
                        </ul>
                    </li>
                    <?php if(hasPrivilege('upload_marketing_data') || hasPrivilege('ad_campaign') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-analytics-21"></span><span class="mtext">Marketing</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('upload_marketing_data') || $_SESSION["role_id"] == 1): ?>
                            <li><a href="upload_marketing_data.php">Upload Daily Campaign Data</a></li>
                            <?php endif; ?>
                            <?php if(hasPrivilege('ad_campaign')): ?>
                            <li><a href="ad_campaign.php">Ad Campaigns</a></li>
                            <?php endif; ?>
                            <!-- <li><a href="under_construction.php?page=Upload Daily Campaign Data">Upload Daily Campaign Data</a></li> -->
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('reports') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-bar-chart-1"></span><span class="mtext">Reports</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="under_construction.php?page=Summary">Summary</a></li>
                            <li><a href="under_construction.php?page=Daily Movement Register">Daily Movement Register</a></li>
                            <li><a href="under_construction.php?page=User Activity Report">User Activity Report</a></li>
                            <li><a href="department_report.php">Department Wise Report</a></li>
                            <li><a href="source_report.php">Source Wise Report</a></li>
                            <li><a href="under_construction.php?page=User Performance Report">User Performance Report</a></li>
                            <li><a href="under_construction.php?page=Package Performance Report">Package Performance Report</a></li>
                            <li><a href="under_construction.php?page=Marketing Performance Report">Marketing Performance Report</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if($_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-gear"></span><span class="mtext">Admin</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="add_user.php">Manage Users</a></li>
                            <li><a href="user_privileges.php">User Privileges</a></li>
                            <li><a href="under_construction.php?page=Transportation Details">Transportation Details</a></li>
                            <li><a href="under_construction.php?page=Accommodation Details">Accommodation Details</a></li>
                            <li><a href="under_construction.php?page=CRUISE Details">CRUISE Details</a></li>
                            <li><a href="under_construction.php?page=Extras/Miscellaneous Details">Extras/Miscellaneous Details</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
        <div class="pd-ltr-20">