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
    
    <!-- CSS -->
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/core.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/icon-font.min.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/src/plugins/datatables/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/src/plugins/datatables/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="assets/deskapp/vendors/styles/style.css">
</head>
<body>
    <div class="pre-loader">
        <div class="pre-loader-box">
            <div class="loader-logo">
                <img src="assets/deskapp/vendors/images/custom-logo.svg" alt="Lead Management">
            </div>
            <div class="loader-progress" id="progress_div">
                <div class="bar" id="bar1"></div>
            </div>
            <div class="percent" id="percent1">0%</div>
            <div class="loading-text">Loading...</div>
        </div>
    </div>

    <div class="header">
        <div class="header-left">
            <div class="menu-icon bi bi-list"></div>
            <div class="search-toggle-icon bi bi-search" data-toggle="header_search"></div>
            <div class="header-search">
                <form>
                    <div class="form-group mb-0">
                        <i class="dw dw-search2 search-icon"></i>
                        <input type="text" class="form-control search-input" placeholder="Search lead/enquiry/file number/referral code">
                    </div>
                </form>
            </div>
        </div>
        <div class="header-right">
            <div class="user-notification">
                <div class="dropdown">
                    <a class="dropdown-toggle no-arrow" href="#" role="button" data-toggle="dropdown">
                        <i class="icon-copy dw dw-notification"></i>
                        <span class="badge notification-active"></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" style="width: 350px; max-height: 400px; overflow-y: auto;">
                        <div class="notification-header px-3 py-2 border-bottom">
                            <h6 class="mb-0">Notifications</h6>
                        </div>
                        <div class="notification-list">
                            <ul id="notification-list" class="list-unstyled mb-0">
                                <li class="text-center py-3">
                                    <span class="text-muted">Loading notifications...</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
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
                    <li>
                        <a href="view_enquiries.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-list-ul"></span><span class="mtext">View Enquiries</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('view_leads') || hasPrivilege('junk_duplicate_leads')): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-funnel"></span><span class="mtext">Leads</span>
                        </a>
                        <ul class="submenu">
                            <?php if(hasPrivilege('view_leads')): ?><li><a href="view_leads.php">All Leads</a></li><?php endif; ?>
                            <?php if(hasPrivilege('junk_duplicate_leads')): ?><li><a href="junk_duplicate_leads.php">Junk and Duplicate Leads</a></li><?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('booking_confirmed')): ?>
                    <li>
                        <a href="booking_confirmed.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-check-circle"></span><span class="mtext">Booking Confirmed</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('booking_cancelled')): ?>
                    <li>
                        <a href="booking_cancelled.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-x-circle"></span><span class="mtext">Booking Cancelled</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('ad_campaign')): ?>
                    <li>
                        <a href="ad_campaign.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-badge-ad"></span><span class="mtext">Ad Campaign</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if(hasPrivilege('transport_details')): ?>
                    <li>
                        <a href="transport_details.php" class="dropdown-toggle no-arrow">
                            <span class="micon bi bi-truck"></span><span class="mtext">Transport Details</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if($_SESSION["role_id"] == 1): ?>
                    <li class="dropdown">
                        <a href="javascript:;" class="dropdown-toggle">
                            <span class="micon bi bi-gear"></span><span class="mtext">Admin</span>
                        </a>
                        <ul class="submenu">
                            <li><a href="add_user.php">Add Users</a></li>
                            <li><a href="user_privileges.php">User Privileges</a></li>
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
    
    <!-- Search functionality -->
    <script src="assets/js/search.js"></script>
    
    <!-- Menu functionality fix -->
    <script src="assets/js/menu-fix.js"></script>
    
    <!-- Notification functionality -->
    <script>
    $(document).ready(function() {
        var lastNotificationCount = 0;
        
        loadNotifications();
        
        // Auto-refresh notifications every 30 seconds
        setInterval(loadNotifications, 30000);
        
        $('.user-notification .dropdown-toggle').on('click', function() {
            loadNotifications();
        });
        
        function loadNotifications() {
            $.ajax({
                url: 'get_notifications.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        updateNotificationBadge(response.unread_count);
                        
                        // Play sound if new notifications arrived
                        if(response.unread_count > lastNotificationCount && lastNotificationCount > 0) {
                            playNotificationSound();
                        }
                        lastNotificationCount = response.unread_count;
                        
                        var notificationHtml = '';
                        if(response.notifications.length > 0) {
                            response.notifications.forEach(function(notification) {
                                var dotClass = notification.is_read == 0 ? 'style="color: #e74c3c; font-weight: bold;"' : 'style="color: #7f8c8d;"';
                                var bgClass = notification.is_read == 0 ? 'style="background-color: #f8f9fa; border-left: 3px solid #e74c3c;"' : 'style="border-left: 3px solid transparent;"';
                                var unreadIndicator = notification.is_read == 0 ? '<span class="badge badge-danger badge-pill ml-2">New</span>' : '';
                                
                                notificationHtml += '<li class="border-bottom" ' + bgClass + '>' +
                                    '<a href="#" onclick="markReadAndRedirect(' + notification.id + ', ' + notification.enquiry_id + ')" style="text-decoration: none; color: inherit; display: block;">' +
                                    '<div class="d-flex align-items-start p-3">' +
                                    '<div class="notification-icon mr-3 mt-1">' +
                                    '<i class="icon-copy dw dw-user1" style="font-size: 18px; color: #3498db;"></i>' +
                                    '</div>' +
                                    '<div class="notification-content flex-grow-1">' +
                                    '<div class="d-flex justify-content-between align-items-start">' +
                                    '<h6 class="mb-1" ' + dotClass + '>' + (notification.customer_name || 'Lead Assignment') + unreadIndicator + '</h6>' +
                                    '</div>' +
                                    '<p class="mb-1 text-muted" style="font-size: 13px; line-height: 1.4;">' + notification.message + '</p>' +
                                    '<small class="text-muted" style="font-size: 11px;">' +
                                    '<i class="icon-copy dw dw-calendar1 mr-1"></i>' + notification.created_at +
                                    '</small>' +
                                    '</div>' +
                                    '</div>' +
                                    '</a>' +
                                    '</li>';
                            });
                        } else {
                            notificationHtml = '<li class="text-center py-3"><span class="text-muted">No notifications</span></li>';
                        }
                        $('#notification-list').html(notificationHtml);
                    }
                },
                error: function() {
                    $('#notification-list').html('<li class="text-center py-3"><span class="text-muted">Error loading notifications</span></li>');
                }
            });
        }
        
        function updateNotificationBadge(count) {
            if(count > 0) {
                $('.notification-active').show().text(count).css({
                    'background-color': '#e74c3c',
                    'color': 'white',
                    'border-radius': '50%',
                    'padding': '2px 6px',
                    'font-size': '10px',
                    'position': 'absolute',
                    'top': '-5px',
                    'right': '-5px'
                });
            } else {
                $('.notification-active').hide();
            }
        }
        
        function playNotificationSound() {
            // Create a more pleasant notification sound
            var audioContext = new (window.AudioContext || window.webkitAudioContext)();
            var oscillator = audioContext.createOscillator();
            var gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.2);
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.3);
        }
        
        window.markReadAndRedirect = function(notificationId, enquiryId) {
            $.post('mark_notification_read.php', {notification_id: notificationId}, function() {
                window.location.href = 'view_leads.php?highlight=' + enquiryId;
            });
        };
    });
    </script>