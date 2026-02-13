<?php
/**
 * Notifications API Endpoint
 * Method: GET, PUT
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

// Demo user ID
$user_id = 2;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get notifications
    $stmt = $conn->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();
    
    // Get unread count
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $countStmt->execute([$user_id]);
    $unreadCount = $countStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'unread_count' => $unreadCount['unread_count']
        ]
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Mark as read
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_id = $input['notification_id'] ?? 0;
    $mark_all_read = $input['mark_all_read'] ?? false;
    
    try {
        if ($mark_all_read) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);
        } elseif (!empty($notification_id)) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$notification_id, $user_id]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Notifications updated'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        http_response_code(500);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    http_response_code(405);
}

$database->closeConnection();
