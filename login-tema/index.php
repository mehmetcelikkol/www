<?php
// Rastgele Pastel Renk Temaları
$themes = [
    'pink' => [
        'body' => '#ffcce0',
        'secondary' => '#ffa8a8',
        'accent' => '#ff8787',
        'accent_dark' => '#ff6b81',
        'grad_start' => '#ff9a9e',
        'grad_end' => '#fecfef',
        'bg_dots' => '#ffdada'
    ],
    'blue' => [
        'body' => '#cce0ff',
        'secondary' => '#a8cbf0',
        'accent' => '#87b8f0',
        'accent_dark' => '#5c9ce6',
        'grad_start' => '#a1c4fd',
        'grad_end' => '#c2e9fb',
        'bg_dots' => '#dbe9ff'
    ],
    'green' => [
        'body' => '#d4f5e1',
        'secondary' => '#a8f0c9',
        'accent' => '#7ee0ae',
        'accent_dark' => '#4bd18f',
        'grad_start' => '#84fab0',
        'grad_end' => '#e0facc',
        'bg_dots' => '#daf5e6'
    ],
    'purple' => [
        'body' => '#e6ccff',
        'secondary' => '#d4a8f0',
        'accent' => '#c287f0',
        'accent_dark' => '#ab5ce6',
        'grad_start' => '#d49bf0',
        'grad_end' => '#f6dbfc',
        'bg_dots' => '#eedbff'
    ],
    'orange' => [
        'body' => '#ffe8cc',
        'secondary' => '#f0cba8',
        'accent' => '#f0b087',
        'accent_dark' => '#e68a5c',
        'grad_start' => '#f6d365',
        'grad_end' => '#ffebd4',
        'bg_dots' => '#ffeedb'
    ]
];

// Rastgele bir tema seç
$themeKeys = array_keys($themes);
$randomThemeKey = $themeKeys[array_rand($themeKeys)];
$theme = $themes[$randomThemeKey];

$error = false;
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'admin' && $password === '123456') {
        $success = true;
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sevimli Giriş Ekranı</title>
    <!-- CSS Değişkenlerini Tanımlama -->
    <style>
        :root {
            --c-body: <?= $theme['body'] ?>;
            --c-secondary: <?= $theme['secondary'] ?>;
            --c-accent: <?= $theme['accent'] ?>;
            --c-accent-dark: <?= $theme['accent_dark'] ?>;
            --c-grad-start: <?= $theme['grad_start'] ?>;
            --c-grad-end: <?= $theme['grad_end'] ?>;
            --c-bg-dots: <?= $theme['bg_dots'] ?>;
        }
    </style>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&display=swap" rel="stylesheet">
</head>
<body class="<?php if($error) echo 'error-state'; ?>">
    <div class="login-container">
        <div class="character-container">
            <!-- Sevimli Karakter -->
            <svg id="cute-monster" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <filter id="blush">
                        <feGaussianBlur stdDeviation="4"/>
                    </filter>
                </defs>
                
                <!-- Kulaklar / Boynuzçuklar -->
                <path class="ear" d="M 40 120 Q 0 110 20 160 Z" fill="var(--c-secondary)"/>
                <path class="ear" d="M 160 120 Q 200 110 180 160 Z" fill="var(--c-secondary)"/>

                <!-- Vücut -->
                <path class="body" d="M30 200 C30 90, 170 90, 170 200 Z" fill="var(--c-body)"/>
                
                <!-- Yüz Arka Planı (Hafif beyaz bölge) -->
                <path class="face" d="M45 200 C45 120, 155 120, 155 200 Z" fill="#ffffff" opacity="0.6"/>
                
                <!-- Yanaklar (Allık) -->
                <ellipse cx="60" cy="155" rx="12" ry="8" fill="var(--c-accent)" opacity="0.7" filter="url(#blush)"/>
                <ellipse cx="140" cy="155" rx="12" ry="8" fill="var(--c-accent)" opacity="0.7" filter="url(#blush)"/>
                
                <!-- Gözler -->
                <g class="eyes">
                    <circle cx="75" cy="140" r="14" fill="#ffffff" stroke="#2d3436" stroke-width="2"/>
                    <g class="pupil-group left-pupil">
                        <circle cx="75" cy="140" r="7" fill="#2d3436"/>
                        <circle cx="72" cy="137" r="2.5" fill="#ffffff"/> <!-- Işıltı -->
                    </g>
                    <circle cx="125" cy="140" r="14" fill="#ffffff" stroke="#2d3436" stroke-width="2"/>
                    <g class="pupil-group right-pupil">
                        <circle cx="125" cy="140" r="7" fill="#2d3436"/>
                        <circle cx="122" cy="137" r="2.5" fill="#ffffff"/> <!-- Işıltı -->
                    </g>
                </g>
                
                <!-- Ağız Çeşitleri -->
                <g class="mouth-group">
                    <path class="mouth idle" d="M 90 163 Q 100 173 110 163" fill="none" stroke="#2d3436" stroke-width="3" stroke-linecap="round"/>
                    <circle class="mouth whistle" cx="100" cy="165" r="4" fill="var(--c-accent)" stroke="#2d3436" stroke-width="2"/>
                    <path class="mouth mock" d="M 88 163 Q 100 168 112 163" fill="none" stroke="#2d3436" stroke-width="3" stroke-linecap="round"/>
                    <path class="mouth mock-tongue" d="M 94 165 C 94 180, 106 180, 106 165 Z" fill="var(--c-accent-dark)" stroke="#2d3436" stroke-width="2"/>
                </g>

                <!-- Kollar / Patiler -->
                <g class="hands">
                    <g class="hand left-hand">
                        <ellipse cx="60" cy="200" rx="18" ry="14" fill="var(--c-secondary)" stroke="#2d3436" stroke-width="2"/>
                        <path d="M 48 196 L 52 206 M 60 193 L 60 206 M 72 196 L 68 206" stroke="#2d3436" stroke-width="2" stroke-linecap="round"/>
                    </g>
                    <g class="hand right-hand">
                        <ellipse cx="140" cy="200" rx="18" ry="14" fill="var(--c-secondary)" stroke="#2d3436" stroke-width="2"/>
                        <path d="M 128 196 L 132 206 M 140 193 L 140 206 M 152 196 L 148 206" stroke="#2d3436" stroke-width="2" stroke-linecap="round"/>
                    </g>
                </g>
            </svg>
        </div>
        
        <form class="login-form" method="POST" action="">
            <h2>Giriş Yap</h2>
            
            <?php if($error): ?>
                <div class="error-msg">Hay aksi! Şifre yanlış...</div>
            <?php elseif($success): ?>
                <div class="success-msg">Harika! Giriş başarılı.</div>
            <?php endif; ?>
            
            <div class="input-group">
                <input type="text" id="username" name="username" placeholder="Kullanıcı Adı" required autocomplete="off">
            </div>
            
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Şifre" required>
            </div>
            
            <button type="submit">Giriş Yap</button>
        </form>
    </div>
    
    <script>
        window.formState = {
            error: <?php echo $error ? 'true' : 'false'; ?>,
            success: <?php echo $success ? 'true' : 'false'; ?>
        };
    </script>
    <script src="script.js"></script>
</body>
</html>
