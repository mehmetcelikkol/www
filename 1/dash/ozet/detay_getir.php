<?php
include '../conn.php';

if (isset($_GET['serino'])) {
    $serino = $_GET['serino'];

    $sql = "SELECT * FROM veriler WHERE serino = ? ORDER BY kayit_tarihi DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $serino);
    $stmt->execute();
    $result = $stmt->get_result();

    $detaylar = [];
    while ($row = $result->fetch_assoc()) {
        $detaylar[] = $row;
    }

    echo json_encode($detaylar);
}
?>
