<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include '../config/database.php';
include '../includes/istatistik_tracker.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['navbar_item'])) {
        kayitNavbarTiklama($pdo, $input['navbar_item']);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Navbar item belirtilmedi']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Sadece POST metodu desteklenir']);
}
?>