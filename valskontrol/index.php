<?php
// index.php
$isDemo = !isset($_GET['demo']) || $_GET['demo'] != '0';
$hour = date('H');
$isShiftActive = ($hour >= 8 && $hour < 18);

function getStats($id, $isDemo) {
    if (!$isDemo) return ['min' => 0, 'max' => 0, 'err' => 0];
    return [
        'min' => rand(0, 15), 
        'max' => rand(0, 5), 
        'err' => (rand(0, 100) > 95) ? 1 : 0 
    ];
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>RMT Proje | Akıllı Vals Dashboard</title>
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg: #020617; --card: #1e293b; --border: #334155; --accent: #38bdf8; }
        
        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden;
            background-color: var(--bg);
            color: #f1f5f9;
            font-family: 'Inter', sans-serif;
        }

        .navbar { 
            height: 70px; 
            background: #020617; 
            border-bottom: 1px solid var(--border); 
            padding: 0 15px;
            display: flex;
            align-items: center;
        }

        .dashboard-wrapper {
            height: calc(100vh - 70px); 
            overflow-x: auto;
            overflow-y: hidden;
            padding: 15px 25px;
            display: flex;
            align-items: center;
        }

        .vals-grid {
            display: grid;
            grid-template-rows: repeat(6, calc((100vh - 160px) / 6)); 
            grid-template-columns: repeat(7, 370px); 
            gap: 15px;
            width: max-content;
        }

        .vals-card { 
            background: var(--card); 
            border-radius: 12px; 
            border: 1px solid var(--border); 
            padding: 10px 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: 0.3s;
            position: relative;
        }

        .pulse-danger { border-color: #ef4444 !important; animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } 
        }
        
        .hz-val { font-size: 1.5rem; font-weight: 800; color: var(--accent); line-height: 1; }
        .factory-status { font-size: 0.8rem; padding: 4px 12px; border-radius: 20px; background: #334155; }

        /* ── Progress Bar ── */
        .flow-progress-wrap {
            position: relative;
            margin: 6px 0 14px 0;
        }
        .flow-progress {
            height: 10px;
            background: #0f172a;
            border-radius: 10px;
            overflow: visible;
            position: relative;
        }
        .flow-bar {
            height: 100%;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            border-radius: 10px;
            transition: width 0.5s ease-in-out;
        }

        /* Min çizgisi – mavi */
        .marker-min {
            position: absolute;
            top: -4px;
            width: 2px;
            height: 18px;
            background: #38bdf8;
            border-radius: 2px;
            transform: translateX(-50%);
        }
        .marker-min::after {
            content: 'MIN';
            position: absolute;
            bottom: -14px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 8px;
            color: #38bdf8;
            white-space: nowrap;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* Max çizgisi – turuncu */
        .marker-max {
            position: absolute;
            top: -4px;
            width: 2px;
            height: 18px;
            background: #f97316;
            border-radius: 2px;
            transform: translateX(-50%);
        }
        .marker-max::after {
            content: 'MAX';
            position: absolute;
            bottom: -14px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 8px;
            color: #f97316;
            white-space: nowrap;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* Set oku – sarı üçgen */
        .marker-set {
            position: absolute;
            top: -8px;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 8px solid #facc15;
            filter: drop-shadow(0 0 3px rgba(250,204,21,0.7));
        }
        .marker-set::after {
            content: 'SET';
            position: absolute;
            top: -18px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 8px;
            color: #facc15;
            white-space: nowrap;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        /* ── Min / Max / Set Kontrol Satırı ── */
        .mms-row {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        .mms-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .mms-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .mms-label.lbl-min { color: #38bdf8; }
        .mms-label.lbl-max { color: #f97316; }
        .mms-label.lbl-set { color: #facc15; }

        .mms-ctrl {
            display: flex;
            align-items: center;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            overflow: hidden;
            height: 24px;
        }
        .mms-ctrl button {
            background: transparent;
            border: none;
            color: #94a3b8;
            width: 22px;
            height: 100%;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.15s, color 0.15s;
            flex-shrink: 0;
            padding: 0;
        }
        .mms-ctrl button:hover { background: #1e293b; color: #f1f5f9; }
        .mms-ctrl .mms-val {
            flex: 1;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
            color: #f1f5f9;
            pointer-events: none;
            user-select: none;
            min-width: 26px;
        }

        /* Scrollbar */
        .dashboard-wrapper::-webkit-scrollbar { height: 18px; cursor: pointer; }
        .dashboard-wrapper::-webkit-scrollbar-track { background: #0f172a; border-radius: 10px; }
        .dashboard-wrapper::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; border: 3px solid #0f172a; }
        .dashboard-wrapper::-webkit-scrollbar-thumb:hover { background: var(--accent); }

        /* Popover */
        .popover { background-color: #020617 !important; border: 1px solid var(--accent) !important; }
        .popover-header { background-color: #1e293b !important; color: var(--accent) !important; font-weight: bold; }
        .popover-body { color: #f1f5f9 !important; font-size: 0.85rem; }
    </style>
</head>
<body>

    <nav class="navbar sticky-top">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="images/logo.png" height="40" class="me-3" alt="RMT">
                <div>
                    <h5 class="m-0 fw-bold" style="letter-spacing: -1px;">VALS AKIŞ ANALİZİ</h5>
                    <span class="factory-status">
                        <i class="fas fa-industry me-1"></i> 
                        <?= $isShiftActive ? '<span class="text-success">ÜRETİM AKTİF</span>' : '<span class="text-warning">MESAİ DIŞI</span>' ?>
                    </span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-info btn-sm"><i class="fas fa-file-pdf me-2"></i>Vardiya Raporu</button>
                <a href="?demo=<?= $isDemo ? '0' : '1' ?>" class="btn btn-sm <?= $isDemo ? 'btn-danger' : 'btn-outline-secondary' ?>">
                    <i class="fas fa-flask me-1"></i> <?= $isDemo ? 'DEMO AKTİF' : 'CANLI VERİ' ?>
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="vals-grid">
            <?php for($i=1; $i<=42; $i++): 
                $st = getStats($i, $isDemo);
                $akim = $isDemo ? (rand(10, 28) / 10) : 0;
                $hz  = $isDemo ? rand(12, 50) : 0;

                // Demo modunda min/max/set değerleri (Hz cinsinden, 0-50 arası)
                $minVal = $isDemo ? rand(10, 18) : 12;
                $maxVal = $isDemo ? rand(38, 50) : 50;
                $setVal = $isDemo ? rand($minVal, $maxVal) : 25;

                $isHighAmper = ($akim > 2.4);

                // Progress bar yüzdeleri (50 Hz = %100)
                $hzPct  = round(($hz  / 50) * 100);
                $minPct = round(($minVal / 50) * 100);
                $maxPct = round(($maxVal / 50) * 100);
                $setPct = round(($setVal / 50) * 100);
            ?>
            <div class="vals-card <?= $isHighAmper ? 'pulse-danger' : '' ?>" 
                 data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true"
                 title="VALS #<?= $i ?> ANALİZİ" 
                 data-bs-content="📉 Seviye Duruşu: <?= $st['min'] ?> kez<br>🚀 Max Hız Zorlama: <?= $st['max'] ?> kez<br>⚠️ Kritik Durum: <?= $st['err'] ?> kez">
                
                <!-- Başlık satırı -->
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-dark text-info">#<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></span>
                    <div class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" checked></div>
                </div>

                <!-- Hz / Akım -->
                <div class="d-flex justify-content-between align-items-center my-1">
                    <div class="hz-val"><?= $hz ?> <small style="font-size: 0.75rem; font-weight: normal;">Hz</small></div>
                    <div class="text-warning fw-bold"><?= $akim ?> <small>A</small></div>
                </div>

                <!-- Progress bar + işaretçiler -->
                <div class="flow-progress-wrap">
                    <div class="flow-progress">
                        <div class="flow-bar" 
                             data-hz="<?= $hz ?>"
                             style="width: <?= $hzPct ?>%">
                        </div>

                        <?php if($isDemo): ?>
                            <!-- Min çizgisi -->
                            <div class="marker-min" style="left: <?= $minPct ?>%;"></div>
                            <!-- Max çizgisi -->
                            <div class="marker-max" style="left: <?= $maxPct ?>%;"></div>
                            <!-- Set oku -->
                            <div class="marker-set" style="left: <?= $setPct ?>%;"></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Durum satırı -->
                <div class="d-flex justify-content-between align-items-center" style="font-size: 0.72rem; margin-top: 8px;">
                    <span class="text-secondary"><i class="fas fa-microchip me-1"></i>ATV12</span>
                    <span class="<?= $hz == 12 ? 'text-info fw-bold' : 'text-success' ?>">
                        <i class="fas <?= $hz == 12 ? 'fa-shield-halved' : 'fa-check-circle' ?> me-1"></i>
                        <?= $hz == 12 ? 'MİN KORUMA' : 'AKTİF' ?>
                    </span>
                </div>

                <!-- Min / Max / Set kontrol satırı -->
                <div class="mms-row">
                    <!-- MIN -->
                    <div class="mms-group">
                        <span class="mms-label lbl-min">Min</span>
                        <div class="mms-ctrl">
                            <button onclick="adjustVal(this, -1, 0, 50)" title="Azalt">&#8722;</button>
                            <span class="mms-val" data-type="min"><?= $minVal ?></span>
                            <button onclick="adjustVal(this, +1, 0, 50)" title="Artır">&#43;</button>
                        </div>
                    </div>
                    <!-- MAX -->
                    <div class="mms-group">
                        <span class="mms-label lbl-max">Max</span>
                        <div class="mms-ctrl">
                            <button onclick="adjustVal(this, -1, 0, 50)" title="Azalt">&#8722;</button>
                            <span class="mms-val" data-type="max"><?= $maxVal ?></span>
                            <button onclick="adjustVal(this, +1, 0, 50)" title="Artır">&#43;</button>
                        </div>
                    </div>
                    <!-- SET -->
                    <div class="mms-group">
                        <span class="mms-label lbl-set">Set</span>
                        <div class="mms-ctrl">
                            <button onclick="adjustVal(this, -1, 0, 50)" title="Azalt">&#8722;</button>
                            <span class="mms-val" data-type="set"><?= $setVal ?></span>
                            <button onclick="adjustVal(this, +1, 0, 50)" title="Artır">&#43;</button>
                        </div>
                    </div>
                </div>

            </div>
            <?php endfor; ?>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Popover başlatma
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (el) {
        return new bootstrap.Popover(el);
    });

    /**
     * adjustVal — + / - butonlarına bağlı değer güncelleme
     * Değer değişince ilgili kartın progress bar işaretçisini de günceller.
     */
    function adjustVal(btn, delta, minLimit, maxLimit) {
        var ctrl   = btn.parentElement;              // .mms-ctrl
        var valEl  = ctrl.querySelector('.mms-val'); // değer span'ı
        var type   = valEl.dataset.type;             // "min" | "max" | "set"
        var card   = ctrl.closest('.vals-card');

        var current = parseInt(valEl.textContent, 10);
        var newVal  = Math.min(maxLimit, Math.max(minLimit, current + delta));
        valEl.textContent = newVal;

        // Progress bar işaretçisini güncelle
        updateMarker(card, type, newVal, maxLimit);
    }

    /**
     * updateMarker — ilgili marker'ın left konumunu yeniler
     */
    function updateMarker(card, type, val, maxHz) {
        var pct = (val / maxHz) * 100;
        var selector = '.marker-' + type; // .marker-min | .marker-max | .marker-set
        var marker = card.querySelector(selector);
        if (marker) {
            marker.style.left = pct + '%';
        }
    }
</script>
</body>
</html>
