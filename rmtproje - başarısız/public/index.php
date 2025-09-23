<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\HomeController;
use App\Controllers\ContactController;
use App\Controllers\PartnershipController;

$controller = new HomeController();
$contactController = new ContactController();
$partnershipController = new PartnershipController();

$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/rmtproje/public';
$route = str_replace($basePath, '', $requestUri);

switch ($route) {
    case '/':
        $controller->index();
        break;
    case '/iletisim':
        $controller->contact();
        break;
    case '/iletisim/submit':
        $contactController->submitForm();
        break;
    case '/hizmetler':
        $controller->services();
        break;
    case '/projeler':
        $controller->projects();
        break;
    case '/is-ortakligi':
        $partnershipController->index();
        break;
    case '/is-ortakligi/submit':
        $partnershipController->submitForm();
        break;
    default:
        http_response_code(404);
        $controller->notFound();
        break;
}