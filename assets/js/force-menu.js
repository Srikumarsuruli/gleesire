// Force hamburger menu to work - override everything
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var menuIcon = document.querySelector('.menu-icon');
        if (menuIcon) {
            menuIcon.onclick = function(e) {
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
            };
        }
    }, 1000);
});