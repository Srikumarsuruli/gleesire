// Menu functionality fix
$(document).ready(function() {
    // Remove any existing handlers and add new one
    $('.menu-icon').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $sidebar = $('.left-side-bar');
        var $overlay = $('.mobile-menu-overlay');
        
        if ($sidebar.hasClass('open')) {
            // Close sidebar
            $sidebar.removeClass('open');
            $overlay.removeClass('show');
        } else {
            // Open sidebar
            $sidebar.addClass('open');
            $overlay.addClass('show');
        }
    });
    
    // Close sidebar when clicking overlay
    $('.mobile-menu-overlay').off('click').on('click', function() {
        $('.left-side-bar').removeClass('open');
        $(this).removeClass('show');
    });
    
    // Close sidebar button
    $('.close-sidebar').off('click').on('click', function() {
        $('.left-side-bar').removeClass('open');
        $('.mobile-menu-overlay').removeClass('show');
    });
});