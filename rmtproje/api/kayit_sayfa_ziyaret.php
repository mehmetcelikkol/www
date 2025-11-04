<?php
include '../config/database.php';
include '../includes/istatistik_tracker.php';

kayitIstatistik($pdo);

echo json_encode(['success' => true]);
?>