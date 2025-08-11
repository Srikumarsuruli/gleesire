// Action button dropdown fix - only for 3-dot buttons in data tables
$(document).ready(function() {
    // Only target action dropdowns in data tables, not sidebar menu
    $(document).on('mousedown', '.data-table .dw-more, .table .dw-more', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $toggle = $(this).closest('.dropdown-toggle');
        var $dropdown = $toggle.closest('.dropdown');
        var $menu = $dropdown.find('.dropdown-menu');
        
        // Close all other action dropdowns (only in tables)
        $('.data-table .dropdown-menu, .table .dropdown-menu').removeClass('show');
        
        // Show current dropdown
        $menu.addClass('show');
    });
    
    // Close table dropdowns when clicking elsewhere (but not sidebar menu)
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length || $(e.target).closest('.left-side-bar').length) {
            $('.data-table .dropdown-menu, .table .dropdown-menu').removeClass('show');
        }
    });
    
    // Prevent table dropdowns from closing when clicking inside
    $(document).on('click', '.data-table .dropdown-menu, .table .dropdown-menu', function(e) {
        e.stopPropagation();
    });
});