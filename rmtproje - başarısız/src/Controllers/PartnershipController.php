<?php

namespace App\Controllers;

class PartnershipController extends BaseController {
    public function index() {
        $data = [
            'title' => 'İş Ortaklığı Programı',
            'currentPage' => 'partnership',
            'content' => $this->getViewContent('partnership')
        ];
        echo $this->render('main', $data);
    }

    public function submitForm() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $company = $_POST['company'] ?? '';
            $name = $_POST['name'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $city = $_POST['city'] ?? '';
            $message = $_POST['message'] ?? '';

            // Validasyon
            $errors = [];
            if (empty($company)) $errors[] = "Firma adı zorunludur";
            if (empty($name)) $errors[] = "Yetkili adı zorunludur";
            if (empty($phone)) $errors[] = "Telefon numarası zorunludur";
            if (empty($email)) $errors[] = "E-posta adresi zorunludur";
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Geçerli bir e-posta adresi giriniz";

            if (empty($errors)) {
                // Mail gönderme
                $to = "info@rmtproje.com";
                $subject = "RMT Proje - İş Ortaklığı Başvurusu";
                $mailContent = "Firma: $company\n";
                $mailContent .= "Yetkili: $name\n";
                $mailContent .= "Telefon: $phone\n";
                $mailContent .= "E-posta: $email\n";
                $mailContent .= "Şehir: $city\n\n";
                $mailContent .= "Mesaj:\n$message";

                $headers = "From: $email\r\n";
                $headers .= "Reply-To: $email\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                if (mail($to, $subject, $mailContent, $headers)) {
                    $_SESSION['success'] = "Başvurunuz başarıyla alındı. En kısa sürede size dönüş yapacağız.";
                } else {
                    $_SESSION['error'] = "Başvuru gönderilirken bir hata oluştu.";
                }
            } else {
                $_SESSION['errors'] = $errors;
            }

            header('Location: /rmtproje/public/is-ortakligi');
            exit;
        }
    }
}