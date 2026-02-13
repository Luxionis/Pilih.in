<?php
/**
 * User Profile API Endpoint
 * Method: GET, PUT
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

// Get user ID from header (simulated)
$user_id = 2; // Demo user

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user profile
    try {
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.email, u.fullname, u.phone, u.province, u.city, 
                   u.profile_picture, u.referral_code, u.created_at,
                   up.total_points, up.level, up.lifetime_points
            FROM users u
            LEFT JOIN user_points up ON u.id = up.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            http_response_code(404);
            exit;
        }

        // Get waste logs count
        $wasteStmt = $conn->prepare("SELECT COUNT(*) as total_logs, SUM(weight_kg) as total_weight FROM waste_logs WHERE user_id = ?");
        $wasteStmt->execute([$user_id]);
        $wasteStats = $wasteStmt->fetch();

        // Get events joined
        $eventStmt = $conn->prepare("SELECT COUNT(*) as total_events FROM event_participants WHERE user_id = ?");
        $eventStmt->execute([$user_id]);
        $eventStats = $eventStmt->fetch();

        // Get achievements count
        $achStmt = $conn->prepare("SELECT COUNT(*) as total_achievements FROM user_achievements WHERE user_id = ?");
        $achStmt->execute([$user_id]);
        $achStats = $achStmt->fetch();

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'fullname' => $user['fullname'],
                'phone' => $user['phone'],
                'province' => $user['province'],
                'city' => $user['city'],
                'profile_picture' => $user['profile_picture'],
                'referral_code' => $user['referral_code'],
                'points' => $user['total_points'] ?? 0,
                'level' => $user['level'] ?? 1,
                'stats' => [
                    'total_logs' => $wasteStats['total_logs'] ?? 0,
                    'total_weight' => $wasteStats['total_weight'] ?? 0,
                    'total_events' => $eventStats['total_events'] ?? 0,
                    'total_achievements' => $achStats['total_achievements'] ?? 0
                ]
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        http_response_code(500);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Update user profile
    $input = json_decode(file_get_contents('php://input'), true);
    
    $fullname = $input['fullname'] ?? '';
    $phone = $input['phone'] ?? '';
    $province = $input['province'] ?? '';
    $city = $input['city'] ?? '';

    try {
        $stmt = $conn->prepare("
            UPDATE users 
            SET fullname = ?, phone = ?, province = ?, city = ?
            WHERE id = ?
        ");
        $stmt->execute([$fullname, $phone, $province, $city, $user_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully'
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
