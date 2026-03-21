    </div> <!-- wrapper sonu -->
    <!-- Bootstrap JS -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    
    <script>
    function tabloExcelIndir(tableID, filename = 'Rapor'){
        var tb = document.getElementById(tableID);
        if(!tb) return;
        var wb = XLSX.utils.table_to_book(tb, {sheet:"Sayfa1"});
        XLSX.writeFile(wb, filename + '.xlsx');
    }

    function tabloPdfIndir(elementID, filename = 'Rapor'){
        var element = document.getElementById(elementID);
        if(!element) return;
        
        var opt = {
            margin:       0.2,
            filename:     filename + '.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'landscape' }
        };
        html2pdf().set(opt).from(element).save();
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobil Sidebar Toggle
        const topbars = document.querySelectorAll('.topbar');
        if(topbars.length > 0) {
            // Overlay ekle
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);

            // Toggle Butonu Ekle
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'btn btn-outline-primary d-md-none me-3';
            toggleBtn.innerHTML = '<i class="fa-solid fa-bars"></i>';
            
            toggleBtn.style.display = 'inline-block';
            
            const topbarSearch = topbars[0].querySelector('.topbar-search');
            if (topbarSearch) {
               topbarSearch.style.display = 'flex';
               topbarSearch.style.alignItems = 'center';
               topbarSearch.prepend(toggleBtn);
            } else {
               topbars[0].prepend(toggleBtn);
               topbars[0].style.display = 'flex';
               topbars[0].style.alignItems = 'center';
            }

            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                const toggleSidebar = () => {
                    sidebar.classList.toggle('show');
                    if(sidebar.classList.contains('show')) {
                        overlay.style.display = 'block';
                        setTimeout(() => overlay.style.opacity = '1', 10);
                    } else {
                        overlay.style.opacity = '0';
                        setTimeout(() => overlay.style.display = 'none', 300);
                    }
                };

                toggleBtn.addEventListener('click', toggleSidebar);
                overlay.addEventListener('click', toggleSidebar);
            }
        }
    });
    </script>
</body>
</html>
