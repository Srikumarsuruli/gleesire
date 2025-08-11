<?php
// Include header
require_once "includes/header.php";
?>

<div class="card-box mb-30">
    <div class="pd-20">
        <h4 class="text-blue h4">Menu Test Page</h4>
        <p>This page is to test if the menu functionality is working properly.</p>
        
        <div class="alert alert-info">
            <strong>Test Instructions:</strong>
            <ul>
                <li>Click the menu icon (hamburger) in the header to toggle the sidebar</li>
                <li>Click on dropdown menu items in the sidebar to expand/collapse them</li>
                <li>Click on the user profile dropdown in the header</li>
                <li>Try the search functionality in the header</li>
            </ul>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h5>Menu Status</h5>
                <p>If you can see this page and the menu is working, the fix was successful!</p>
            </div>
            <div class="col-md-6">
                <h5>JavaScript Status</h5>
                <div id="js-status">JavaScript is loading...</div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#js-status').html('<span class="text-success">JavaScript is working!</span>');
    
    // Test menu functionality
    console.log('Menu test page loaded');
    console.log('jQuery version:', $.fn.jquery);
    
    // Check if menu functions are available
    if (typeof $('.menu-icon').click === 'function') {
        console.log('Menu icon click handler is available');
    }
    
    // Test dropdown functionality
    $('.dropdown-toggle').on('click', function() {
        console.log('Dropdown clicked');
    });
});
</script>

<?php
// Include footer
require_once "includes/footer.php";
?>