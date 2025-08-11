// Menu functionality fix
$(document).ready(function() {
    // Ensure menu icon click works
    $('.menu-icon').on('click', function(e) {
        e.preventDefault();
        $('body').toggleClass('sidebar-shrink');
        $('.left-side-bar').toggleClass('open');
        $('.mobile-menu-overlay').toggleClass('show');
    });
    
    // Ensure header search toggle works
    $('[data-toggle="header_search"]').on('click', function(e) {
        e.preventDefault();
        $('.header-search').slideToggle();
    });
    
    // Ensure dropdown menus work in header
    $('.header .dropdown-toggle').on('click', function(e) {
        e.preventDefault();
        var $dropdown = $(this).closest('.dropdown');
        var $menu = $dropdown.find('.dropdown-menu');
        
        // Close other dropdowns
        $('.header .dropdown-menu').not($menu).removeClass('show');
        
        // Toggle current dropdown
        $menu.toggleClass('show');
    });
    
    // Close dropdowns when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.header .dropdown-menu').removeClass('show');
        }
    });
    
    // Sidebar menu active state
    var currentPage = window.location.pathname.split('/').pop();
    $('#accordion-menu a').each(function() {
        var href = $(this).attr('href');
        if (href && href.indexOf(currentPage) !== -1) {
            $(this).addClass('active');
        }
    });
});