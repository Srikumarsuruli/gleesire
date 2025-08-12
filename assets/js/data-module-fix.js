// Data module hamburger menu fix
$(document).ready(function() {
    // Wait for all scripts to load, then override
    setTimeout(function() {
        $('.menu-icon').off('click').on('click', function(e) {
            e.preventDefault();
            $('.left-side-bar').toggleClass('open');
            $('.mobile-menu-overlay').toggleClass('show');
        });
    }, 100);
});