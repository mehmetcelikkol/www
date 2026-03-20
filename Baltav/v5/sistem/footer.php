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
</body>
</html>
