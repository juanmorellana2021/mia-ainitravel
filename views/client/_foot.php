    </main><!-- /.mc-content -->
</div><!-- /.mc-main-area -->

</div><!-- /.mc-wrapper -->
<script src="<?= App::asset('js/bootstrap.bundle.min.js') ?>"></script>
<script>
function openSidebar() {
    document.getElementById('mcSidebar').classList.add('open');
    document.getElementById('mcBackdrop').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('mcSidebar').classList.remove('open');
    document.getElementById('mcBackdrop').classList.remove('show');
    document.body.style.overflow = '';
}
// Close sidebar when a nav link is tapped on mobile
document.querySelectorAll('#mcSidebar .mc-nav-item').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) closeSidebar();
    });
});
</script>
</body>
</html>
