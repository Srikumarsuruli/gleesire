<?php
// Include header
require_once "includes/header.php";

// Get page title from URL parameter
$page_title = isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'Page';
?>

<div class="pd-20 card-box mb-30">
    <div class="clearfix mb-20">
        <div class="pull-left">
            <h4 class="text-blue h4"><?php echo $page_title; ?></h4>
        </div>
    </div>
    
    <div class="text-center" style="padding: 50px 0;">
        <div class="mb-30">
            <i class="icon-copy dw dw-construction" style="font-size: 80px; color: #f39c12;"></i>
        </div>
        <h3 class="text-warning">Under Construction</h3>
        <p class="text-muted">This page is currently under development and will be available soon.</p>
        <div class="mt-30">
            <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php
// Include footer
require_once "includes/footer.php";
?>