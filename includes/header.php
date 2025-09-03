<?php
// Initialize the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Session timeout - 15 minutes (900 seconds)
$timeout_duration = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("location: login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

// Include database connection
require_once "config/database.php";

// Include common functions
require_once "includes/functions.php";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Basic Page Info -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Lead Management System</title>
    
    <!-- Site favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="assets/deskapp/src/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/deskapp/src/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/deskapp/src/images/favicon-16x16.png">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Critical CSS -->
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/src/plugins/datatables/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/src/plugins/datatables/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/style.css">
    <link rel="stylesheet" type="text/css" href="assets/css/custom.css">
    <link rel="stylesheet" type="text/css" href="assets/css/dropdown-fix.css">
    
</head>
<body>
    <!-- <div class="pre-loader">
        <div class="pre-loader-box">
            <div class="loader-logo">
                <div class="flight-animation">
                    <div class="flight-icon">✈️</div>
                    <div class="flight-path"></div>
                </div>
            </div>
            <div class="loading-text">loading data</div>
        </div>
    </div> -->
    
    <style>
    .flight-animation {
        position: relative;
        width: 200px;
        height: 60px;
        margin: 0 auto;
    }
    
    .flight-icon {
        font-size: 30px;
        position: absolute;
        animation: fly 2s ease-in-out infinite;
    }
    
    .flight-path {
        position: absolute;
        top: 50%;
        left: 0;
        width: 100%;
        height: 2px;
        background: linear-gradient(90deg, transparent 0%, #007bff 50%, transparent 100%);
        animation: pathGlow 2s ease-in-out infinite;
    }
    
    @keyframes fly {
        0% { left: -30px; top: 30px; transform: rotate(-10deg); }
        50% { left: 85px; top: 15px; transform: rotate(5deg); }
        100% { left: 200px; top: 0px; transform: rotate(15deg); }
    }
    
    @keyframes pathGlow {
        0%, 100% { opacity: 0.3; }
        50% { opacity: 1; }
    }
    </style>

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
                    <?php if(hasPrivilege('dashboard') || $_SESSION["role_id"] == 1): ?>
                    <li>
                        <a href="index.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-house"></span><span class="mtext">Dashboard</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('upload_enquiries') || $_SESSION["role_id"] == 1): ?>
                    <li>
                        <a href="upload_enquiries.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-upload"></span><span class="mtext">Upload Enquiries</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('view_enquiries') || hasPrivilege('job_enquiries') || hasPrivilege('ticket_enquiries') || hasPrivilege('influencer_enquiries') || hasPrivilege('dmc_agent_enquiries') || hasPrivilege('cruise_enquiries') || hasPrivilege('no_response_enquiries') || hasPrivilege('follow_up_enquiries') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-list-ul"></span><span class="mtext">Enquiries</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('view_enquiries') || $_SESSION["role_id"] == 1): ?><li><a href="view_enquiries.php">All Enquiries</a></li><?php endif; ?>
                            <?php if(hasPrivilege('job_enquiries') || $_SESSION["role_id"] == 1): ?><li><a href="view_job_enquiries.php">Job Enquiries</a></li><?php endif; ?>
                                <li><a href="under_construction.php?page=Accommodation Partnership Enquiries">Accommodation Partnership Enquiries</a></li>
                            <?php if(hasPrivilege('ticket_enquiries') || $_SESSION["role_id"] == 1): ?><li><a href="view_ticket_enquiry.php">Ticket Enquiries</a></li><?php endif; ?>
                            <?php if(hasPrivilege('influencer_enquiries') || $_SESSION["role_id"] == 1): ?><li><a href="view_influencer_enquiries.php">Influencer Enquiries</a></li><?php endif; ?>
                            <?php if(hasPrivilege('dmc_agent_enquiries') || $_SESSION["role_id"] == 1): ?><li><a href="view_dmc.php">DMC/Agent Enquiries</a></li><?php endif; ?>
                            <?php if(hasPrivilege('cruise_enquiries') || $_SESSION["role_id"] == 1): ?><li><a href="view_cruise.php">Cruise Enquiries</a></li><?php endif; ?>
                            <?php if(hasPrivilege('no_response_enquiries') || $_SESSION["role_id"] == 1): ?><li><a href="view_noresponserejectedenquiries.php">No Response Enquiries</a></li><?php endif; ?>
                            <?php if(hasPrivilege('follow_up_enquiries') || $_SESSION["role_id"] == 1): ?><li><a href="view_flowup.php">Follow up Enquiries</a></li><?php endif; ?>
                            
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('view_leads') || hasPrivilege('fixed_package_lead') || hasPrivilege('custom_package_leads') || hasPrivilege('medical_tourism_leads') || hasPrivilege('lost_to_competitors') || hasPrivilege('no_response_leads') || hasPrivilege('follow_up_leads') || hasPrivilege('junk_duplicate_leads') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-funnel"></span><span class="mtext">Leads</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('view_leads') || $_SESSION["role_id"] == 1): ?><li><a href="view_leads.php">All Leads</a></li><?php endif; ?>
                            <?php if(hasPrivilege('view_leads') || $_SESSION["role_id"] == 11 || $_SESSION["role_id"] == 12): ?><li><a href="my_leads.php">My Leads</a></li><?php endif; ?>
                            <?php if(hasPrivilege('fixed_package_lead') || $_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=Fixed Package Lead">Fixed Package Lead</a></li><?php endif; ?>
                            <?php if(hasPrivilege('custom_package_leads') || $_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=Custom Package Leads">Custom Package Leads</a></li><?php endif; ?>
                            <?php if(hasPrivilege('pipeline') || $_SESSION["role_id"] == 1): ?><li><a href="pipeline.php">Pipe Line Leads</a></li><?php endif; ?>

                                <?php if(hasPrivilege('medical_tourism_leads') || $_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=Medical Tourism Leads">Medical Tourism Leads</a></li><?php endif; ?>
                            <?php if(hasPrivilege('lost_to_competitors') || $_SESSION["role_id"] == 1): ?><li><a href="view_lost_competitors.php">Lost to Competitors</a></li><?php endif; ?>
                            <?php if(hasPrivilege('no_response_leads') || $_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=No Response/Rejected Leads">No Response/Rejected Leads</a></li><?php endif; ?>
                            <?php if(hasPrivilege('follow_up_leads') || $_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=No Follow up Leads">Follow up Leads</a></li><?php endif; ?>
                            <?php if(hasPrivilege('junk_duplicate_leads') || $_SESSION["role_id"] == 1): ?><li><a href="junk_duplicate_leads.php">Junk and Duplicate Leads</a></li><?php endif; ?>
                            <li><a href="under_construction.php?page=Group Tour Package Leads">Group Tour Package Leads</a></li>
                            <li><a href="under_construction.php?page=Honeymoon Package Leads">Honeymoon Package Leads</a></li>
                            <li><a href="under_construction.php?page=Corporate Package Leads">Corporate Package Leads</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('booking_confirmed') || hasPrivilege('booking_cancelled') || hasPrivilege('travel_completed') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-calendar-check"></span><span class="mtext">Booking</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('booking_confirmed') || $_SESSION["role_id"] == 1): ?><li><a href="booking_confirmed.php">Booking Confirmed</a></li><?php endif; ?>
                            <?php if(hasPrivilege('booking_cancelled') || $_SESSION["role_id"] == 1): ?><li><a href="booking_cancelled.php">Booking Cancelled</a></li><?php endif; ?>
                            <?php if(hasPrivilege('travel_completed') || $_SESSION["role_id"] == 1): ?><li><a href="travel_completed.php">Travel Completed</a></li><?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if($_SESSION["role_id"] == 1): ?>
                    <li>
                        <a href="view_cost_sheets.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-file-earmark-text"></span><span class="mtext">View Cost Sheets</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if(hasPrivilege('feedbacks') || $_SESSION["role_id"] == 1): ?>
                    <li>
                        <a href="under_construction.php?page=Feedbacks" class="dropdown-toggle no-arrow">
                            <span class="micon dw dw-message"></span><span class="mtext">Our Customers</span>
                        </a> 
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('hotel_resorts') || hasPrivilege('cruise_reservation') || hasPrivilege('visa_air_ticket') || hasPrivilege('transportation_reservation') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-calendar-check"></span><span class="mtext">Reservation</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('hotel_resorts') || $_SESSION["role_id"] == 1): ?><li><a href="hotel_resorts.php">Hotel/Resorts</a></li><?php endif; ?>
                            <li><a href="hotel_cruise_details.php">Hotel/Resort Cruise Details</a></li>
                            <?php if(hasPrivilege('cruise_reservation') || $_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=Cruise">Cruise</a></li><?php endif; ?>
                            <?php if(hasPrivilege('visa_air_ticket') || $_SESSION["role_id"] == 1): ?><li><a href="visa_ticket_booking.php">Visa & Air Ticket</a></li><?php endif; ?>
                            <?php if(hasPrivilege('train_ticketing') || $_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=Train Ticketing">Train Ticketing</a></li><?php endif; ?>
                                <?php if(hasPrivilege('transportation_reservation') || $_SESSION["role_id"] == 1): ?><li><a href="transportation_booking.php">Transportation</a></li><?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
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
                        </ul>
                    </li>
                    <?php endif; ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-calculator"></span><span class="mtext">Payments</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('view_payment_receipts') || $_SESSION["role_id"] == 1): ?><li><a href="view_payment_receipts.php">Payment Received</a></li><?php endif; ?>
                            <li><a href="under_construction.php?page=Accommodation Payment Receipts">Accommodation Payment Receipts</a></li>
                            <li><a href="under_construction.php?page=Transportation Payment Receipts">Transportation Payment Receipts</a></li>
                            <li><a href="under_construction.php?page=Cruise Payment Receipts">Cruise Payment Receipts</a></li>
                            <li><a href="under_construction.php?page=Hospital TAC Receipt">Hospital TAC(Travel Agent Commission) Receipt</a></li>
                            <li><a href="under_construction.php?page=Travel Insurance Receipts">Travel Insurance Receipts</a></li>
                            <li><a href="under_construction.php?page=Air Ticket Payment Receipts">Air Ticket Payment Receipts</a></li>
                            <li><a href="under_construction.php?page=VISA Payment Receipts">VISA Payment Receipts</a></li>
                            <li><a href="under_construction.php?page=Train Ticket Receipts">Train Ticket Receipts</a></li>
                            <li><a href="under_construction.php?page=Agent Payment Receipt">Agent Payment Receipt</a></li>
                            <li><a href="under_construction.php?page=Freelance Travel Agent Commission Payment Receipts">Freelance Travel Agent Commission Payment Receipts</a></li>
                        </ul>
                    </li>
                    <?php if(hasPrivilege('summary_report') || hasPrivilege('daily_movement_register') || hasPrivilege('user_activity_report') || hasPrivilege('department_report') || hasPrivilege('source_report') || hasPrivilege('user_performance_report') || hasPrivilege('package_performance_report') || hasPrivilege('marketing_performance_report') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon dw dw-bar-chart-1"></span><span class="mtext">Reports</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('summary_report') || $_SESSION["role_id"] == 1): ?><li><a href="summary_report.php">Summary</a></li><?php endif; ?>
                            <?php if(hasPrivilege('daily_movement_register') || $_SESSION["role_id"] == 1): ?><li><a href="daily_movement_register.php">Daily Movement Register</a></li><?php endif; ?>
                            <?php if(hasPrivilege('user_activity_report') || $_SESSION["role_id"] == 1): ?><li><a href="user_activity_report.php">User Activity Report</a></li><?php endif; ?>
                            <?php if(hasPrivilege('department_report') || $_SESSION["role_id"] == 1): ?><li><a href="department_report.php">Department Wise Report</a></li><?php endif; ?>
                            <?php if(hasPrivilege('source_report') || $_SESSION["role_id"] == 1): ?><li><a href="source_report.php">Source Wise Report</a></li><?php endif; ?>
                            <?php if(hasPrivilege('user_performance_report') || $_SESSION["role_id"] == 1): ?><li><a href="user_performance_report.php">User Performance Report</a></li><?php endif; ?>
                            <li><a href="under_construction.php?page=Source/Channel Wise Reports">Source/Channel Wise Reports</a></li>
                            <li><a href="under_construction.php?page=Campaign wise Reports">Campaign wise Reports</a></li>
                            <li><a href="under_construction.php?page=Package wise Reports">Package wise Reports</a></li>
                            <li><a href="under_construction.php?page=Destination Wise Reports">Destination Wise Reports</a></li>
                            <li><a href="under_construction.php?page=File Manager wise Reports">File Manager wise Reports</a></li>
                            <li><a href="under_construction.php?page=Enquiry Type wise Reports">Enquiry Type wise Reports</a></li>
                            <li><a href="under_construction.php?page=Enquiry Status wise Reports">Enquiry Status wise Reports</a></li>
                            <li><a href="under_construction.php?page=Lead Status Wise Reports">Lead Status Wise Reports</a></li>
                            <li><a href="under_construction.php?page=Lead Priority Wise Reports">Lead Priority Wise Reports</a></li>
                            <li><a href="under_construction.php?page=Lead Attended User Wise Report">Lead Attended User Wise Report</a></li>
                            <li><a href="under_construction.php?page=Tele Sales User wise Reports">Tele Sales User wise Reports</a></li>
                            <li><a href="under_construction.php?page=Reservation Team Reports">Reservation Team Reports</a></li>
                            <?php if(hasPrivilege('user_logs') || $_SESSION["role_id"] == 1): ?><li><a href="user_logs.php">User Logs (User Session Report)</a></li><?php endif; ?>
                            <li><a href="chatbot_full_data.php">Chatbot Full Data</a></li>
                            <li><a href="under_construction.php?page=All Users Activity Reports">All Users Activity Reports</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('accommodation_details') || hasPrivilege('transportation_details') || hasPrivilege('cruise_details') || hasPrivilege('travel_agents') || hasPrivilege('hospital_details') || hasPrivilege('extras_miscellaneous_details') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-building"></span><span class="mtext">Data Module (BC)</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('accommodation_details') || $_SESSION["role_id"] == 1): ?><li><a href="accommodation_details.php">Accommodation Details</a></li><?php endif; ?>
                            <?php if(hasPrivilege('transportation_details') || $_SESSION["role_id"] == 1): ?><li><a href="transport_details.php">Transportation Details</a></li><?php endif; ?>
                            <?php if(hasPrivilege('cruise_details') || $_SESSION["role_id"] == 1): ?><li><a href="cruise_details.php">Cruise Details</a></li><?php endif; ?>
                            <?php if(hasPrivilege('travel_agents') || $_SESSION["role_id"] == 1): ?><li><a href="TravelAgents.php">Travel Agent Details</a></li><?php endif; ?>
                            <?php if(hasPrivilege('hospital_details') || $_SESSION["role_id"] == 1): ?><li><a href="HospitalDetails.php">Hospital Details</a></li><?php endif; ?>
                            <?php if(hasPrivilege('extras_miscellaneous_details') || $_SESSION["role_id"] == 1): ?><li><a href="extras_details.php">Extras/Miscellaneous Details</a></li><?php endif; ?>
                            <li><a href="under_construction.php?page=Freelance Travel Consultant">Freelance Travel Consultant</a></li>
                            
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('destinations') || hasPrivilege('departments') || hasPrivilege('source_channel') || hasPrivilege('referral_code') || hasPrivilege('enquiry_type') || hasPrivilege('enquiry_status') || hasPrivilege('lead_status') || hasPrivilege('night_day') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-server"></span><span class="mtext">Data Module</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('destinations') || $_SESSION["role_id"] == 1): ?><li><a href="Destinations.php">Destinations</a></li><?php endif; ?>
                            <li><a href="under_construction.php?page=Departments">Departments</a></li>
                            <?php if(hasPrivilege('source_channel') || $_SESSION["role_id"] == 1): ?><li><a href="source.php">Source (Channels)</a></li><?php endif; ?>
                            <?php if(hasPrivilege('referral_code') || $_SESSION["role_id"] == 1): ?><li><a href="ReferralCode.php">Referral Codes</a></li><?php endif; ?>
                            <?php if(hasPrivilege('enquiry_type') || $_SESSION["role_id"] == 1): ?><li><a href="EnquiryType.php">Enquiry Types</a></li><?php endif; ?>
                            <?php if(hasPrivilege('enquiry_status') || $_SESSION["role_id"] == 1): ?><li><a href="EnquiryStatus.php">Enquiry Statuses</a></li><?php endif; ?>
                            <?php if(hasPrivilege('lead_status') || $_SESSION["role_id"] == 1): ?><li><a href="LeadStatus.php">Leads Statuses</a></li><?php endif; ?>
                            <?php if(hasPrivilege('night_day') || $_SESSION["role_id"] == 1): ?><li><a href="NightDay.php">Night/Days</a></li><?php endif; ?>
                                <?php if(hasPrivilege('night_day') || $_SESSION["role_id"] == 1): ?><li><a href="Packages.php">Packages Types</a></li><?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('add_user') || hasPrivilege('user_privileges') || $_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-gear"></span><span class="mtext">Admin</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('add_user') || $_SESSION["role_id"] == 1): ?><li><a href="add_user.php">Manage Users</a></li><?php endif; ?>
                            <?php if(hasPrivilege('user_privileges') || $_SESSION["role_id"] == 1): ?><li><a href="user_privileges.php">User Privileges</a></li><?php endif; ?>
                            <?php if($_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=API Keys">API Keys</a></li><?php endif; ?>
                            <?php if($_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=SMTP Main Setup">SMTP Main Setup</a></li><?php endif; ?>
                            <?php if($_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=SMS Integration">SMS Integration</a></li><?php endif; ?>
                            <?php if($_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=Whatsapp API">Whatsapp API</a></li><?php endif; ?>
                            <?php if($_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=Instagram Message API">Instagram Message API</a></li><?php endif; ?>
                            <?php if($_SESSION["role_id"] == 1): ?><li><a href="under_construction.php?page=Messenger API">Messenger API</a></li><?php endif; ?>
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
        
    <!-- js -->
    <script src="assets/deskapp/vendors/scripts/core.js"></script>
    <script src="assets/deskapp/vendors/scripts/script.min.js"></script>
    <script src="assets/deskapp/vendors/scripts/process.js"></script>
    <script src="assets/deskapp/vendors/scripts/layout-settings.js"></script>
    
    <!-- Menu functionality fix -->
    <script src="assets/js/menu-fix.js"></script>
    
    <!-- Dropdown functionality fix -->
    <script src="assets/js/dropdown-fix.js"></script>
    
    <!-- Search functionality -->
    <script src="assets/js/search.js"></script>
    
    <!-- Row highlighting -->
    <script src="assets/js/highlight-row.js"></script>
   <script src="assets/js/menu-fix.js"></script>
    
    <!-- Comments functionality -->
    <script src="assets/js/comments.js"></script>
    
    <script>
    $(document).ready(function() {
        // Table action dropdown fix
        $(document).on('click', '.dropdown-toggle', function(e) {
            if ($(this).find('.dw-more').length > 0) {
                e.preventDefault();
                e.stopPropagation();
                $('.dropdown-menu').removeClass('show');
                $(this).next('.dropdown-menu').addClass('show');
            }
        });
        
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.dropdown').length) {
                $('.dropdown-menu').removeClass('show');
            }
        });
        
        $(document).on('click', '.dropdown-menu', function(e) {
            e.stopPropagation();
        });
        
        // Auto-logout after 15 minutes of inactivity
        let timeoutWarning;
        let timeoutLogout;
        
        function resetTimeout() {
            clearTimeout(timeoutWarning);
            clearTimeout(timeoutLogout);
            
            // Show warning at 14 minutes (840 seconds)
            timeoutWarning = setTimeout(function() {
                if(confirm('Your session will expire in 1 minute due to inactivity. Click OK to stay logged in.')) {
                    // User clicked OK, make an AJAX call to refresh session
                    $.post('refresh_session.php');
                }
            }, 840000);
            
            // Auto logout at 15 minutes (900 seconds)
            timeoutLogout = setTimeout(function() {
                alert('Session expired due to inactivity. You will be redirected to login page.');
                window.location.href = 'login.php?timeout=1';
            }, 900000);
        }
        
        // Reset timeout on any user activity
        $(document).on('click keypress scroll mousemove', resetTimeout);
        
        // Initialize timeout
        resetTimeout();
    });
    </script>