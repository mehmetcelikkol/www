<?php

namespace App\Controllers;

class BaseController {
    protected function render($view, $data = []) {
        // Data değişkenlerini extract et
        extract($data);
        
        // View dosyası yolu düzeltildi
        $layoutPath = __DIR__ . "/../views/layouts/{$view}.php";
        
        // View dosyası var mı kontrol et
        if (!file_exists($layoutPath)) {
            throw new \Exception("Layout dosyası bulunamadı: {$layoutPath}");
        }
        
        // Output buffer'ı başlat
        ob_start();
        
        // View dosyasını dahil et
        require $layoutPath;
        
        // Buffer'ı temizle ve gönder
        return ob_get_clean();
    }

    protected function getViewContent($view) {
        // View dosyası yolu
        $viewPath = __DIR__ . "/../views/pages/{$view}.php";
        
        // View dosyası var mı kontrol et
        if (!file_exists($viewPath)) {
            throw new \Exception("View dosyası bulunamadı: {$viewPath}");
        }
        
        // Output buffer'ı başlat
        ob_start();
        
        // View dosyasını dahil et
        require $viewPath;
        
        // Buffer'ı döndür
        return ob_get_clean();
    }
}