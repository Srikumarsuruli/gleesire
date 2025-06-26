// Fast load optimization script
document.addEventListener('DOMContentLoaded', function() {
    // Defer non-critical CSS loading
    function loadDeferredStyles() {
        const stylesToDefer = [
            'assets/deskapp/src/plugins/datatables/css/dataTables.bootstrap4.min.css',
            'assets/deskapp/src/plugins/datatables/css/responsive.bootstrap4.min.css'
        ];
        
        stylesToDefer.forEach(function(href) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
        });
    }
    
    // Load non-critical styles after page load
    if (window.requestAnimationFrame) {
        window.requestAnimationFrame(function() {
            window.setTimeout(loadDeferredStyles, 0);
        });
    } else {
        window.addEventListener('load', loadDeferredStyles);
    }
    
    // Initialize page faster
    setTimeout(function() {
        document.body.classList.add('loaded');
    }, 100);
});