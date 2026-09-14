<?php
declare(strict_types=1);
?>
    <!-- Standalone Mobile Sidebar Script (Guaranteed zero-lag execution) -->
    <script>
    function toggleMobileSidebar() {
        var sidebar = document.getElementById('app-sidebar');
        var backdrop = document.getElementById('sidebar-backdrop');
        if (!sidebar) return;
        var isOpen = sidebar.classList.contains('mobile-open') || sidebar.style.left === '0px';
        if (isOpen) {
            sidebar.classList.remove('mobile-open');
            sidebar.style.setProperty('left', '-320px', 'important');
            if (backdrop) {
                backdrop.classList.remove('show');
                backdrop.style.display = 'none';
                backdrop.style.opacity = '0';
            }
            document.body.style.overflow = '';
        } else {
            sidebar.classList.add('mobile-open');
            sidebar.style.setProperty('left', '0px', 'important');
            if (backdrop) {
                backdrop.classList.add('show');
                backdrop.style.display = 'block';
                backdrop.style.opacity = '1';
            }
            document.body.style.overflow = 'hidden';
        }
    }
    function closeMobileSidebar() {
        var sidebar = document.getElementById('app-sidebar');
        var backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar) {
            sidebar.classList.remove('mobile-open');
            sidebar.style.setProperty('left', '-320px', 'important');
        }
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.style.display = 'none';
            backdrop.style.opacity = '0';
        }
        document.body.style.overflow = '';
    }
    </script>

    <!-- jQuery, Bootstrap 5, Chart.js, DataTables, FontAwesome -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="assets/js/admin-ajax.js?v=<?php echo time(); ?>"></script>
</body>
</html>
