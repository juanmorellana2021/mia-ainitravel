    </main><!-- /.mc-content -->
</div><!-- /.mc-main-area -->

</div><!-- /.mc-wrapper -->
<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
<script>
// Close sidebar on mobile when clicking outside
document.addEventListener('click', function(e) {
    var sidebar = document.getElementById('mcSidebar');
    if (sidebar && sidebar.classList.contains('open') && !sidebar.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});
</script>
</body>
</html>
