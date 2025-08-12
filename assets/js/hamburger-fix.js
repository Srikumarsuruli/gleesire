// Direct hamburger menu fix
$(document).ready(function() {
    // Remove all existing handlers and add direct handler
    $(document).off('click', '.menu-icon');
    $(document).on('click', '.menu-icon', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var sidebar = document.querySelector('.left-side-bar');
        var overlay = document.querySelector('.mobile-menu-overlay');
        
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        } else {
            sidebar.classList.add('open');
            overlay.classList.add('show');
        }
    });
    
    // Close on overlay click
    $(document).on('click', '.mobile-menu-overlay', function() {
        document.querySelector('.left-side-bar').classList.remove('open');
        document.querySelector('.mobile-menu-overlay').classList.remove('show');
    });
});