<?php
// filepath: c:\wamp64\www\limanrapor\includes\header.php
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCADA Rapor Sistemi</title>
    
    <!-- LOCAL Chart.js dosyaları -->
    <script src="js/chart.umd.js"></script>
    <script src="js/chartjs-adapter-date-fns.bundle.min.js"></script>
    
  <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        color: #2d3748;
        line-height: 1.6;
        min-height: 100vh;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        min-height: calc(100vh - 200px);
    }

    .header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2rem;
        border-radius: 8px;
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
    }

    .header-logo {
        margin-bottom: 1rem;
    }

    .company-logo {
        height: 60px;
        width: auto;
        filter: brightness(0) invert(1);
        opacity: 0.9;
    }

    .header h1 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        font-weight: 700;
    }

    .header p {
        opacity: 0.9;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    .company-info {
        margin-top: 0.5rem;
        opacity: 0.8;
    }

    .company-info small {
        font-size: 0.9rem;
        font-style: italic;
    }

    .btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-decoration: none;
        display: inline-block;
    }

    .btn:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .button-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .data-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 25px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .data-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .data-count {
        color: white;
        font-weight: 600;
        background: rgba(255,255,255,0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
    }

    .table-container {
        overflow-x: auto;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table th,
    .data-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }

    .data-table th {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        font-weight: 600;
        color: white;
        position: sticky;
        top: 0;
    }

    .data-table tr:nth-child(even) {
        background: #f8fafc;
    }

    .data-table tr:nth-child(odd) {
        background: white;
    }

    .data-table tr:hover {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%) !important;
        transform: scale(1.005);
        transition: all 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .error {
        background: #fee;
        color: #c53030;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        border: 1px solid #fed7d7;
    }

    .debug-panel {
        background: #e6fffa;
        color: #234e52;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        border: 1px solid #81e6d9;
    }

    .debug-panel h4 {
        margin-bottom: 0.5rem;
        color: #234e52;
    }

    .debug-panel ul {
        margin-left: 1.5rem;
    }

    .debug-panel li {
        margin-bottom: 0.25rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #718096;
    }

    .empty-state svg {
        width: 64px;
        height: 64px;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Chart bölümü stilleri */
    .chart-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 25px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .chart-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }

    .chart-header h3 {
        margin: 0;
        font-weight: 600;
    }

    .chart-container {
        padding: 2rem;
        height: 400px;
        position: relative;
    }

    #flowChart {
        max-height: 350px;
        width: 100% !important;
        height: 100% !important;
    }

    @media (max-width: 768px) {
        .container {
            padding: 10px;
        }

        .header {
            padding: 1.5rem;
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .company-logo {
            height: 40px;
        }

        .data-table {
            font-size: 0.9rem;
        }

        .data-table th,
        .data-table td {
            padding: 0.5rem;
        }

        .footer-content {
            grid-template-columns: 1fr;
            padding: 0 1rem;
        }

        .footer-section {
            margin-bottom: 1.5rem;
        }

        .footer-bottom {
            padding: 1rem;
        }

        .tanks-container {
            grid-template-columns: 1fr;
            padding: 1rem;
            gap: 1rem;
        }
        
        .tank-image {
            width: 80px;
        }
        
        .tank-values {
            grid-template-columns: repeat(3, 1fr);
            font-size: 0.8rem;
            gap: 0.3rem;
        }
        
        .tank-value-label {
            font-size: 0.7rem !important;
        }
        
        .tank-value-data {
            font-size: 0.85rem !important;
        }
    }

    .export-btn {
        background: rgba(255,255,255,0.2);
        color: white;
        border: 2px solid white;
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }

    .export-btn:hover {
        background: white;
        color: #4CAF50;
    }

    /* Tır tablosu özel stilleri */
    .plate-number {
        font-weight: 700;
        color: #1a202c;
        background: linear-gradient(135deg, #edf2f7 0%, #e2e8f0 100%);
        text-align: center;
        border-radius: 4px;
        padding: 0.5rem;
        border: 2px solid #cbd5e0;
    }

    /* Gemi boşaltma özel stilleri */
    .sensor-name {
        font-weight: 700;
        color: #1a202c;
        background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%);
        text-align: center;
        border-radius: 4px;
        padding: 0.5rem;
        border: 2px solid #81e6d9;
    }

    /* Tank özel stilleri */
    .tank-1 {
        font-weight: 700;
        color: #1a202c;
        background: linear-gradient(135deg, #fef5e7 0%, #fed7aa 100%);
        text-align: center;
        border-radius: 4px;
        padding: 0.5rem;
        border: 2px solid #f59e0b;
    }

    .tank-2 {
        font-weight: 700;
        color: #1a202c;
        background: linear-gradient(135deg, #eff6ff 0%, #bfdbfe 100%);
        text-align: center;
        border-radius: 4px;
        padding: 0.5rem;
        border: 2px solid #3b82f6;
    }

    .amount {
        font-weight: 600;
        color: #2d3748;
        text-align: right;
    }

    .data-table td:nth-child(4),
    .data-table td:nth-child(5) {
        font-size: 0.9rem;
        color: #4a5568;
    }

    /* Footer ve Debug Stilleri */
    .main-footer {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        margin-top: 3rem;
        padding: 2rem 0 1rem 0;
        border-radius: 8px 8px 0 0;
    }

    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        padding: 0 2rem;
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-section h4 {
        color: #ecf0f1;
        font-size: 1.2rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #3498db;
    }

    .footer-section p {
        margin-bottom: 0.8rem;
        line-height: 1.6;
        color: #bdc3c7;
    }

    .footer-section ul {
        list-style: none;
        padding: 0;
    }

    .footer-section ul li {
        margin-bottom: 0.5rem;
        padding-left: 1rem;
        position: relative;
        color: #bdc3c7;
    }

    .footer-section ul li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #3498db;
        font-weight: bold;
    }

    .contact-info p {
        margin-bottom: 0.5rem;
    }

    .contact-info a {
        color: #3498db;
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .contact-info a:hover {
        color: #5dade2;
        text-decoration: underline;
    }

    .footer-bottom {
        background: #1a252f;
        margin-top: 2rem;
        padding: 1rem 2rem;
        text-align: center;
        border-top: 1px solid #34495e;
    }

    .footer-bottom p {
        margin: 0.5rem 0;
        color: #95a5a6;
        font-size: 0.9rem;
    }

    .debug-footer {
        margin-top: 2rem;
        padding: 1rem 0;
        border-top: 1px solid #e9ecef;
        text-align: center;
    }
    .footer {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        text-align: center;
        padding: 2.5rem 0;
        margin-top: 3rem;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.1);
        border-top: none;
    }

    .debug-toggle {
        margin-bottom: 1rem;
    }

    .debug-btn {
        background: #718096;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: background-color 0.3s ease;
    }

    .debug-btn:hover {
        background: #4a5568;
    }

    .debug-panel {
        background: #f7fafc;
        color: #2d3748;
        padding: 1rem;
        border-radius: 6px;
        margin-top: 1rem;
        border: 1px solid #e2e8f0;
        text-align: left;
        max-height: 400px;
        overflow-y: auto;
    }

    .debug-panel h4 {
        margin-bottom: 0.5rem;
        color: #2d3748;
    }

    .debug-panel ul {
        margin-left: 1.5rem;
    }

    .debug-panel li {
        margin-bottom: 0.25rem;
    }

    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        
        .header-actions,
        .debug-toggle,
        .debug-panel,
        .debug-footer,
        .main-footer {
            display: none !important;
        }
        
        .container {
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .header {
            background: none !important;
            color: black !important;
            border: 2px solid black;
            margin-bottom: 1rem !important;
        }
        
        .data-section {
            box-shadow: none !important;
            border: 1px solid black;
        }
        
        .data-header {
            background: #f0f0f0 !important;
            color: black !important;
            border-bottom: 2px solid black !important;
        }
        
        .data-table th {
            background: #f0f0f0 !important;
            color: black !important;
            border: 1px solid black !important;
        }
        
        .data-table td {
            border: 1px solid black !important;
        }
        
        .data-table tr:nth-child(even) {
            background: #f8f8f8 !important;
        }
        
        .data-table tr:nth-child(odd) {
            background: white !important;
        }
        
        .plate-number {
            background: #f0f0f0 !important;
            border: 1px solid black !important;
        }
        
        .tank-1 {
            background: #f0f0f0 !important;
            border: 1px solid black !important;
        }
        
        .tank-2 {
            background: #e0e0e0 !important;
            border: 1px solid black !important;
        }
        
        .amount {
            color: black !important;
        }
        
        .data-table {
            font-size: 10px !important;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.3rem !important;
        }

        .chart-section {
            display: none !important;
        }
        
        .tank-dashboard {
            display: none !important;
        }
    }

    /* Tank Dashboard Stilleri */
    .tank-dashboard {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 25px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .tank-dashboard-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.25rem 1.5rem;
        text-align: center;
    }

    .tanks-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        padding: 2rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }

    .tank-display {
        position: relative;
        text-align: center;
        padding: 1rem;
        border-radius: 12px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .tank-display:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    /* Tank tıklama efekti */
    .tank-display:active {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.25);
    }

    .tank-1-display {
        background: linear-gradient(135deg, #fef5e7 0%, #fed7aa 100%);
        border: 3px solid #f59e0b;
    }

    .tank-2-display {
        background: linear-gradient(135deg, #eff6ff 0%, #bfdbfe 100%);
        border: 3px solid #3b82f6;
    }

    .tank-title {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 1rem;
        color: #1a202c;
    }

    .tank-image {
        width: 120px;
        height: auto;
        margin: 1rem 0;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.1));
    }

    .tank-values {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(3, auto);
        gap: 0.5rem;
        margin-top: 1rem;
        font-size: 0.9rem;
    }

    /* Grid düzeni: 
       Radar      Radar(cm)     Radar(kg)
       Basınç     Basınç(cm)    Basınç(kg)
       Sıcaklık   Son Güncelleme (2 sütun)
    */

    /* Son güncelleme kutusunu 2 sütun genişliğinde yap */
    .tank-timestamp-value {
        grid-column: span 2;
    }

    .tank-value {
        background: rgba(255,255,255,0.8);
        padding: 0.5rem;
        border-radius: 6px;
        border: 1px solid rgba(0,0,0,0.1);
    }

    .tank-value-label {
        font-weight: 600;
        color: #4a5568;
        font-size: 0.8rem;
        margin-bottom: 0.2rem;
    }

    .tank-value-data {
        font-weight: bold;
        color: #1a202c;
        font-size: 1rem;
    }

    /* Tank kg değerleri için özel stiller - KALDIRILDI */
    
    /* Son satır için özel stiller */
    .tank-value:nth-child(7) {
        /* Sıcaklık - Gradient mavi tonları */
        background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%) !important;
        border: 2px solid #2196f3 !important;
    }

    /* Son güncelleme kutusu için özel stiller */
    .tank-timestamp-value {
        /* Gradient gri tonları, daha büyük alan */
        background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%) !important;
        border: 2px solid #9e9e9e !important;
        grid-column: span 2;
    }

    .tank-timestamp-value .tank-value-data {
        font-size: 0.85rem !important;
        color: #424242 !important;
        font-weight: 600 !important;
    }

    /* Tablo filtreleme stilleri */
    .table-filter {
        padding: 1rem 1.5rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-bottom: 1px solid #e2e8f0;
    }

    .filter-controls {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-tag {
        background: #667eea;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-tag.tank-1-filter {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .filter-tag.tank-2-filter {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }

    .filter-tag .close-btn {
        background: rgba(255,255,255,0.3);
        border: none;
        color: white;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 12px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .filter-tag .close-btn:hover {
        background: rgba(255,255,255,0.5);
    }

    .clear-all-btn {
        background: #718096;
        color: white;
        border: none;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        cursor: pointer;
        font-size: 0.9rem;
    }

    .clear-all-btn:hover {
        background: #4a5568;
    }

    /* Gizli satırlar için */
    .data-table tr.hidden {
        display: none;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: bold;
        display: inline-block;
    }

    .status-active {
        background: linear-gradient(135deg, #00b894, #00a085);
        color: white;
    }

    .status-inactive {
        background: linear-gradient(135deg, #e17055, #d63031);
        color: white;
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .alert-warning {
        background: linear-gradient(135deg, #fdcb6e, #e17055);
        color: white;
    }

    @media (max-width: 768px) {
        .tanks-container {
            grid-template-columns: 1fr;
            padding: 1rem;
            gap: 1rem;
        }
        
        .tank-image {
            width: 80px;
        }
        
        .tank-values {
            grid-template-columns: repeat(3, 1fr);
            font-size: 0.8rem;
            gap: 0.3rem;
        }
        
        .tank-value-label {
            font-size: 0.7rem !important;
        }
        
        .tank-value-data {
            font-size: 0.85rem !important;
        }
    }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-logo">
                <img src="img/logo.png" alt="RMT Proje Logo" class="company-logo" onerror="this.style.display='none';">
            </div>
            <h1>RMT Liman SCADA İzleme Sistemi</h1>
            <p>Gerçek zamanlı tank seviyeleri, gemi boşaltma operasyonları ve tır yükleme işlemlerini takip edin</p>
            <div class="company-info">
                <small>RMT Proje ve Endüstriyel Otomasyon Ltd. Şti.</small>
            </div>
            
            <!-- Navigasyon Butonları -->
            <div style="margin-top: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap;">
                    <a href="?page=tank_izleme" class="btn" style="<?= ($page ?? '') === 'tank_izleme' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">🛢️ Tank İzleme</a>
                    <a href="?page=akis_hizi" class="btn" style="<?= ($page ?? '') === 'akis_hizi' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">📊 Akış Hızı</a>
                    <a href="?page=gemi_bosaltma" class="btn" style="<?= ($page ?? '') === 'gemi_bosaltma' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">🚢 Gemi Boşaltma</a>
                    <a href="?page=tir_islemleri" class="btn" style="<?= ($page ?? '') === 'tir_islemleri' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">🚛 Tır İşlemleri</a>
                    <a href="?page=kontrol_listeleri" class="btn" style="<?= ($page ?? '') === 'kontrol_listeleri' ? 'background: linear-gradient(135deg, #10b981 0%, #059669 100%);' : '' ?>">📋 Kontrol Listeleri</a>
                </div>
            </div>
        </div>