<?php

namespace App\Controllers;

class HomeController extends BaseController {
    public function index() {
        $data = [
            'title' => 'Ana Sayfa',
            'currentPage' => 'home',
            'content' => $this->getViewContent('home')
        ];
        
        // Ana şablon dosyasını render et
        echo $this->render('main', $data);
    }

    public function services() {
        $data = [
            'title' => 'Hizmetlerimiz',
            'currentPage' => 'services',
            'content' => $this->getViewContent('services')
        ];
        echo $this->render('main', $data);
    }

    public function projects() {
        $data = [
            'title' => 'Projelerimiz',
            'currentPage' => 'projects',
            'content' => $this->getViewContent('projects')
        ];
        echo $this->render('main', $data);
    }

    public function contact() {
        $data = [
            'title' => 'İletişim',
            'currentPage' => 'contact',
            'content' => $this->getViewContent('contact')
        ];
        echo $this->render('main', $data);
    }
}