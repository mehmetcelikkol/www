<?php
// submit_form.php - Handles saving user form data

header('Content-Type: application/json');

$db_file = __DIR__ . '/logs.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create user_details table if not exists
    $createDetailsTable = "
        CREATE TABLE IF NOT EXISTS user_details (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            action_type TEXT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            country TEXT NOT NULL,
            city TEXT,
            phone TEXT,
            email TEXT,
            instagram TEXT,
            ip_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($createDetailsTable);

    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if ($data && isset($data['first_name']) && isset($data['last_name']) && isset($data['country'])) {
        
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $action_type = isset($data['action_type']) ? $data['action_type'] : 'unknown';

        // 1. Insert into user_details
        $stmt = $pdo->prepare("INSERT INTO user_details 
            (action_type, first_name, last_name, country, city, phone, email, instagram, ip_address) 
            VALUES (:action_type, :first_name, :last_name, :country, :city, :phone, :email, :instagram, :ip_address)");
            
        $stmt->execute([
            ':action_type' => $action_type,
            ':first_name' => $data['first_name'],
            ':last_name' => $data['last_name'],
            ':country' => $data['country'],
            ':city' => $data['city'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':email' => $data['email'] ?? null,
            ':instagram' => $data['instagram'] ?? null,
            ':ip_address' => $ip_address
        ]);

        // 2. Log the form submission in user_logs
        $logStmt = $pdo->prepare("INSERT INTO user_logs (action_type, element_info, ip_address, user_agent) VALUES ('form_submitted', :element_info, :ip, :ua)");
        $logStmt->execute([
            ':element_info' => 'Agreed to ' . $action_type,
            ':ip' => $ip_address,
            ':ua' => $user_agent
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Data saved and logged']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
