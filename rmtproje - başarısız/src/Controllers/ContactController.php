<?php

namespace App\Controllers;

class ContactController extends BaseController {
    public function submitForm() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Form verilerini al
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $message = $_POST['message'] ?? '';

            // Basit validasyon
            $errors = [];
            if (empty($name)) $errors[] = "İsim alanı zorunludur";
            if (empty($email)) $errors[] = "E-posta alanı zorunludur";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Geçerli bir e-posta adresi giriniz";
            if (empty($message)) $errors[] = "Mesaj alanı zorunludur";

            if (empty($errors)) {
                // Mail gönderme işlemi
                $to = "info@rmtproje.com";
                $subject = "RMT Proje - İletişim Formu";
                $mailContent = "İsim: $name\n";
                $mailContent .= "E-posta: $email\n";
                $mailContent .= "Telefon: $phone\n\n";
                $mailContent .= "Mesaj:\n$message";

                $headers = "From: $email\r\n";
                $headers .= "Reply-To: $email\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                if (mail($to, $subject, $mailContent, $headers)) {
                    $_SESSION['success'] = "Mesajınız başarıyla gönderildi.";
                } else {
                    $_SESSION['error'] = "Mesaj gönderilirken bir hata oluştu.";
                }
            } else {
                $_SESSION['errors'] = $errors;
            }

            // Forma geri yönlendir
            header('Location: /rmtproje/public/iletisim');
            exit;
        }
    }
}