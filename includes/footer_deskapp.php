        </div>
    </div>
    
    <!-- js -->
    <script src="assets/deskapp/vendors/scripts/core.js"></script>
    <script src="assets/deskapp/vendors/scripts/script.min.js"></script>
    <script src="assets/deskapp/vendors/scripts/process.js"></script>
    <script src="assets/deskapp/vendors/scripts/layout-settings.js"></script>
    <script src="assets/deskapp/src/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="assets/deskapp/src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/deskapp/src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="assets/deskapp/src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
    
    <!-- Add custom script for datatable initialization -->
    <script>
        $(document).ready(function() {
            $('.data-table').DataTable({
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
        });
    </script>
</body>
</html>