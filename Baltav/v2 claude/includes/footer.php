        </div><!-- /ss-content -->
    </main><!-- /ss-main -->
</div><!-- /ss-wrapper -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.49.0/dist/apexcharts.min.js"></script>
<!-- Özel JS -->
<script src="/assets/js/app.js"></script>

<script>
// Saat göstergesi
function saatiGuncelle() {
    const el = document.getElementById('ssClock');
    if (!el) return;
    const simdi = new Date();
    el.textContent = simdi.toLocaleTimeString('tr-TR', {hour: '2-digit', minute: '2-digit', second: '2-digit'});
}
saatiGuncelle();
setInterval(saatiGuncelle, 1000);

// Sidebar toggle (mobil)
document.getElementById('menuToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.toggle('ss-sidebar-open');
});
</script>

<?php if (isset($sayfa_js)): ?>
<script><?= $sayfa_js ?></script>
<?php endif; ?>

</body>
</html>
