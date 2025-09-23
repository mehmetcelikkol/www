<?php
// Contact Form Handler for RMT Proje
// Production version for rmtproje.com

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit('Method not allowed');
}

// CSRF protection - basic implementation
session_start();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    exit('CSRF token validation failed');
}

// Input validation and sanitization
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Collect and validate form data
$name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '';
$company = isset($_POST['company']) ? sanitize_input($_POST['company']) : '';
$subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

// Validation errors
$errors = [];

if (empty($name) || strlen($name) < 2) {
    $errors[] = 'İsim en az 2 karakter olmalıdır.';
}

if (empty($email) || !validate_email($email)) {
    $errors[] = 'Geçerli bir e-posta adresi giriniz.';
}

if (empty($message) || strlen($message) < 10) {
    $errors[] = 'Mesaj en az 10 karakter olmalıdır.';
}

// Optional phone validation
if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $phone)) {
    $errors[] = 'Geçerli bir telefon numarası giriniz.';
}

// If there are validation errors
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Form doğrulama hatası',
        'errors' => $errors
    ]);
    exit;
}

// Email configuration
$to = 'rmt@rmtproje.com'; // Main company email
$from = 'noreply@rmtproje.com'; // No-reply sender
$reply_to = $email; // User's email for replies

// Email subject
$email_subject = 'Web Sitesi İletişim Formu: ' . ($subject ?: 'Yeni Mesaj');

// Email headers
$headers = [
    'From: ' . $from,
    'Reply-To: ' . $reply_to,
    'X-Mailer: PHP/' . phpversion(),
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit'
];

// Email body (HTML format)
$email_body = '
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RMT Proje - İletişim Formu</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 10px; }
        .header { background: #c8102e; color: white; padding: 15px; text-align: center; border-radius: 5px; margin-bottom: 20px; }
        .content { background: white; padding: 20px; border-radius: 5px; }
        .field { margin-bottom: 15px; }
        .field strong { color: #c8102e; }
        .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>RMT Proje - Web Sitesi İletişim Formu</h2>
        </div>
        <div class="content">
            <div class="field">
                <strong>Ad Soyad:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '
            </div>
            <div class="field">
                <strong>E-posta:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '
            </div>';

if (!empty($phone)) {
    $email_body .= '
            <div class="field">
                <strong>Telefon:</strong> ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '
            </div>';
}

if (!empty($company)) {
    $email_body .= '
            <div class="field">
                <strong>Şirket:</strong> ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '
            </div>';
}

if (!empty($subject)) {
    $email_body .= '
            <div class="field">
                <strong>Konu:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '
            </div>';
}

$email_body .= '
            <div class="field">
                <strong>Mesaj:</strong><br>
                ' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '
            </div>
        </div>
        <div class="footer">
            <p>Bu mesaj www.rmtproje.com web sitesi iletişim formundan gönderilmiştir.</p>
            <p>Gönderim Tarihi: ' . date('d.m.Y H:i:s') . '</p>
        </div>
    </div>
</body>
</html>';

// Send email
$mail_sent = mail($to, $email_subject, $email_body, implode("\r\n", $headers));

// Response
header('Content-Type: application/json');

if ($mail_sent) {
    // Log successful submission (optional)
    error_log("Contact form submission from: $email at " . date('Y-m-d H:i:s'));
    
    echo json_encode([
        'success' => true,
        'message' => 'Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.'
    ]);
} else {
    // Log failed submission
    error_log("Failed to send contact form email from: $email at " . date('Y-m-d H:i:s'));
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Mesaj gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyiniz veya doğrudan e-posta gönderiniz.'
    ]);
}
?>
