<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizasyon Şeması</title>
    <script src="https://d3js.org/d3.v5.min.js"></script>
    <style>
        .node {
            cursor: pointer;
        }
        .link {
            fill: none;
            stroke: #ccc;
            stroke-width: 2px;
        }
        svg {
            width: 100%;
            height: 100%;
            overflow: visible;
        }
        .node circle {
            fill: #fff;
            stroke: #3182bd;
            stroke-width: 3px;
        }
        .node text {
            font-size: 12px;
        }

        /* Kaydırma işlemi için bir kapsayıcı div */
        .svg-container {
            width: 100%;
            height: 500px;  /* Bu yüksekliği değiştirebilirsiniz */
            overflow: auto;  /* Kaydırma çubuğu eklenecek */
            border: 1px solid #ccc; /* Kapsayıcıya bir sınır ekledik */
        }
    </style>
</head>
<body>

    <div>
        

        <?php
        function bagimliliklariTara($dizin) {
            $dosyalar = glob($dizin . '/*.php');
            echo '<table border="1" style="width: 100%; border-collapse: collapse; text-align: left;">';
            echo '<thead>';
            echo '<tr style="background-color: #f2f2f2;">';
            echo '<th style="padding: 8px;">Dosya Adı</th>';
            echo '<th style="padding: 8px;">Dahil Edilen Dosyalar</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($dosyalar as $dosya) {
                $icerik = file_get_contents($dosya);
                preg_match_all('/(include|require)(_once)?\s*\(?(["\'].*["\'])\)?;/', $icerik, $eslesmeler);
                
                // Dosya adını ve bağımlılıklarını bir satırda göster
                echo '<tr>';
                echo '<td style="padding: 8px; vertical-align: top; width: 30%;"><strong>' . htmlspecialchars($dosya) . '</strong></td>';
                echo '<td style="padding: 8px; vertical-align: top;">';
                
                if (!empty($eslesmeler[3])) {
                    echo '<ul>';
                    foreach ($eslesmeler[3] as $eslesme) {
                        echo '<li>' . htmlspecialchars($eslesme) . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo 'Dahil edilen dosya bulunamadı';
                }
                
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }

        bagimliliklariTara(__DIR__);
        ?>


    </div>

    <div>

        <h2>Dosya Bağlantı Şeması</h2>
        
        <!-- Kaydırılabilir div kapsayıcı -->
        <div class="svg-container">
            <svg></svg>
        </div>

        <script>
        // PHP'den gelen JSON verisini al
            var treeData = [{"file":"analiz.php","includes":[]},{"file":"api_chzlar.php","includes":["'conn.php'"]},{"file":"api_cihazlarim.php","includes":["'conn.php'"]},{"file":"api_create_key.php","includes":["'conn.php'"]},{"file":"api_data.php","includes":["'conn.php'"]},{"file":"api_endpoint.php","includes":["'conn.php'"]},{"file":"chzekle.php","includes":["\"header.php\"","\"conn.php\"","\"oturum.php\"","\"sidebar.php\"","\"navbar.php\"","\"footer.php\""]},{"file":"chzlar.php","includes":["\"header.php\"","\"conn.php\"","\"oturum.php\"","\"sidebar.php\"","\"navbar.php\""]},{"file":"cihazgor.php","includes":["\"header.php\"","\"sidebar.php\"","\"navbar.php\"","\"conn.php\"","\"footer.php\""]},{"file":"cihazgraf.php","includes":["\"header.php\"","\"sidebar.php\"","\"navbar.php\"","\"conn.php\"","\"footer.php\""]},{"file":"conn.php","includes":[]},{"file":"footer.php","includes":[]},{"file":"header.php","includes":[]},{"file":"index-orjinal.php","includes":[]},{"file":"index.php","includes":["\"header.php\"","\"conn.php\"","\"oturum.php\"","\"sidebar.php\"","\"navbar.php\""]},{"file":"login.php","includes":["\"header.php\"","\"conn.php\""]},{"file":"logout.php","includes":[]},{"file":"navbar.php","includes":[]},{"file":"oturum.php","includes":[]},{"file":"setle.php","includes":["\"header.php\"","\"conn.php\"","\"oturum.php\"","\"sidebar.php\"","\"navbar.php\""]},{"file":"sidebar.php","includes":[]},{"file":"signup.php","includes":["\"header.php\"","\"conn.php\""]},{"file":"tablogor.php","includes":["\"header.php\"","\"sidebar.php\"","\"navbar.php\"","\"conn.php\""]},{"file":"testgir.php","includes":[]},{"file":"verigir.php","includes":[]},{"file":"verioku.php","includes":["\"header.php\"","\"conn.php\"","\"oturum.php\"","\"sidebar.php\"","\"navbar.php\"","\"footer.php\""]},{"file":"yeni.php","includes":["\"conn.php\""]},{"file":"yuktesti.php","includes":[]}];

        // SVG elemanını seç
            var svg = d3.select("svg"),
            width = window.innerWidth, // Genişlik dinamik olarak ayarlanıyor
            height = 1500, // Yükseklik biraz daha fazla yaparak kaydırmayı test edin
            margin = { top: 50, right: 100, bottom: 50, left: 100 },
            g = svg.append("g").attr("transform", "translate(" + margin.left + "," + margin.top + ")");

        // D3.js ile ağaç şeması oluştur
            var tree = d3.tree().size([height - margin.top - margin.bottom, width - margin.left - margin.right]),
            root = d3.hierarchy({
                "name": "Root",
                "children": treeData.map(function(d) {
                    return {
                        "name": d.file,
                        "children": d.includes.map(function(include) {
                            return { "name": include };
                        })
                    };
                }), 
            });

            tree(root);

        // Bağlantıları oluştur
            var link = g.selectAll(".link")
            .data(root.links())
            .enter().append("path")
            .attr("class", "link")
            .attr("d", d3.linkHorizontal().x(function(d) { return d.y; }).y(function(d) { return d.x; }));

        // Düğümleri oluştur
            var node = g.selectAll(".node")
            .data(root.descendants())
            .enter().append("g")
            .attr("class", "node")
            .attr("transform", function(d) { return "translate(" + d.y + "," + d.x + ")"; });

            node.append("circle")
            .attr("r", 10);

            node.append("text")
            .attr("dy", 3)
            .attr("dx", function(d) { return d.children ? -12 : 12; })
            .style("text-anchor", function(d) { return d.children ? "end" : "start"; })
            .text(function(d) { return d.data.name; });
        </script>

    </div>

</body>
</html>
