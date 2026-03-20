    </div> <!-- #content -->
</div> <!-- .wrapper -->

<footer class="p-3 small text-muted text-center border-top" style="border-color: var(--border-color) !important; background: var(--bg-color);">
    © <?php echo date('Y'); ?> <span class="neon-text" style="font-size: 0.8rem; color: var(--accent-color);">SILOSENSE V4</span> • RMT Proje Operasyon Merkezi
</footer>

<!-- JS libs -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Animate.css for entrance effects -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<script>
// V4 Global Scripts
$(document).ready(function() {
    // Tooltip init
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Dark Mode Force (v4 always dark)
    document.documentElement.setAttribute('data-theme', 'dark');
});
</script>

</body>
</html>
