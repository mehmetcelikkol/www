<?php
// logger.php - Handles logging visits and clicks

header('Content-Type: application/json');

// SQLite database file path
$db_file = __DIR__ . '/logs.sqlite';

try {
    // Connect to SQLite (creates file if it doesn't exist)
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create table if not exists
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS user_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action_type TEXT NOT NULL, -- 'visit' or 'click'
            element_info TEXT,         -- ID or text of clicked element
            ip_address TEXT,
            user_agent TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createTableQuery);

    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data && isset($data['action_type'])) {
        $action_type = $data['action_type'];
        $element_info = isset($data['element_info']) ? $data['element_info'] : null;
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        // Prepare and execute insert statement
        $stmt = $pdo->prepare("INSERT INTO user_logs (action_type, element_info, ip_address, user_agent) VALUES (:action_type, :element_info, :ip_address, :user_agent)");
        $stmt->bindParam(':action_type', $action_type);
        $stmt->bindParam(':element_info', $element_info);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':user_agent', $user_agent);
        $stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Log saved']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
