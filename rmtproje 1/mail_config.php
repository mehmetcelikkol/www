<?php
// SMTP yapılandırması - ŞİFREYİ canlı sunucuda .gitignore edilen ayrı bir dosyada saklayın.
return [
    'host' => 'mail.rmtproje.com',
    'port' => 587,
    'secure' => 'tls', // 587 => tls, 465 => ssl
    'username' => 'iletisim@rmtproje.com',
    'password' => '0120a0120A', // PROD: Ortam değişkenine taşıyın.
    'from_email' => 'iletisim@rmtproje.com',
    'from_name' => 'RMT Proje İletişim',
    'to_contact' => 'mehmet@rmtproje.com',
    'to_partner' => 'mehmet@rmtproje.com'
];
