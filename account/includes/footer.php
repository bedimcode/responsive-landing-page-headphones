    </main><!-- end main content area -->
</div><!-- end lg:ml-[260px] wrapper -->

<!-- Cart JS -->
<script src="<?= e($basePath) ?>/assets/js/cart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Cart !== 'undefined') Cart.init();
});
</script>
</body>
</html>
