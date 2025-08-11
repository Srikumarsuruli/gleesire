        </div>
    </div>
    
    <!-- DataTables js -->
    <script src="assets/deskapp/src/plugins/datatables/js/jquery.dataTables.min.js" defer></script>
    <script src="assets/deskapp/src/plugins/datatables/js/dataTables.bootstrap4.min.js" defer></script>
    <script src="assets/deskapp/src/plugins/datatables/js/dataTables.responsive.min.js" defer></script>
    <script src="assets/deskapp/src/plugins/datatables/js/responsive.bootstrap4.min.js" defer></script>
    
    <!-- Fast load optimization -->
    <script src="assets/js/fast-load.js"></script>
    
    <!-- Sidebar toggle functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Menu toggle functionality
            const menuIcon = document.querySelector('.menu-icon');
            const body = document.body;
            
            if (menuIcon) {
                menuIcon.addEventListener('click', function() {
                    body.classList.toggle('sidebar-shrink');
                });
            }
            
            // Close sidebar on mobile overlay click
            const overlay = document.querySelector('.mobile-menu-overlay');
            if (overlay) {
                overlay.addEventListener('click', function() {
                    body.classList.remove('sidebar-shrink');
                });
            }
        });
    </script>
    
    <!-- Add custom script for datatable initialization -->
    <script>
        // Initialize datatables after all scripts are loaded
        window.addEventListener('load', function() {
            setTimeout(function() {
                if ($.fn.DataTable) {
                    // Only initialize tables that haven't been initialized yet
                    $('.data-table:not(.dataTable)').DataTable({
                        scrollCollapse: true,
                        autoWidth: false,
                        responsive: true,
                        columnDefs: [{
                            targets: "datatable-nosort",
                            orderable: false,
                        }],
                        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
                        "language": {
                            "info": "_START_-_END_ of _TOTAL_ entries",
                            searchPlaceholder: "Search",
                            paginate: {
                                next: '<i class="ion-chevron-right"></i>',
                                previous: '<i class="ion-chevron-left"></i>'
                            }
                        }
                    });
                }
            }, 100);
        });
    </script>
</body>
</html>