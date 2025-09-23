<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form verilerini al ve temizle
    $formType = isset($_GET['form']) ? $_GET['form'] : 'contact';
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    $errors = [];
    
    // Temel doğrulama
    if (empty($name)) $errors[] = "İsim alanı zorunludur";
    if (empty($email)) $errors[] = "E-posta alanı zorunludur";
    if (!validateEmail($email)) $errors[] = "Geçerli bir e-posta adresi giriniz";
    if (empty($message)) $errors[] = "Mesaj alanı zorunludur";
    
    // İş ortaklığı formu için ek alanlar
    if ($formType === 'partnership') {
        $company = sanitizeInput($_POST['company'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        
        if (empty($company)) $errors[] = "Firma adı zorunludur";
        if (empty($phone)) $errors[] = "Telefon numarası zorunludur";
    }
    
    if (empty($errors)) {
        // E-posta gönderimi için başlıklar
        $to = "info@rmtproje.com";
        $subject = $formType === 'partnership' ? 
                  "RMT Proje - İş Ortaklığı Başvurusu" : 
                  "RMT Proje - İletişim Formu";
        
        // E-posta içeriğini oluştur
        $emailContent = "Yeni Form Gönderimi\n\n";
        $emailContent .= "İsim: $name\n";
        $emailContent .= "E-posta: $email\n";
        
        if ($formType === 'partnership') {
            $emailContent .= "Firma: $company\n";
            $emailContent .= "Telefon: $phone\n";
            $emailContent .= "Şehir: $city\n";
        }
        
        $emailContent .= "\nMesaj:\n$message\n";
        
        // E-posta başlıkları
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // E-posta gönderimi
        if (mail($to, $subject, $emailContent, $headers)) {
            $response = [
                'success' => true,
                'message' => 'Mesajınız başarıyla gönderildi.'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'E-posta gönderiminde bir hata oluştu.'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Form hatası: ' . implode(', ', $errors)
        ];
    }
    
    // JSON yanıtı döndür
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// POST olmayan istekleri yönlendir
header('Location: /');
exit;