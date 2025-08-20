<?php
require_once "includes/header.php";

$page_name = isset($_GET['page']) ? $_GET['page'] : 'This Page';
?>

<div class="card-box mb-30">
    <div class="pd-20 text-center">
        <div style="padding: 50px 0;">
            <i class="fa fa-wrench" style="font-size: 80px; color: #f39c12; margin-bottom: 20px;"></i>
            <h2 class="text-warning">Under Construction</h2>
            <p class="lead"><?php echo htmlspecialchars($page_name); ?> is currently under development.</p>
            <p class="text-muted">We're working hard to bring you this feature. Please check back soon!</p>
            <a href="index.php" class="btn btn-primary mt-3">
                <i class="fa fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>