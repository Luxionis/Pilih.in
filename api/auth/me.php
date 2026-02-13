<?php
/**
 * Get Current User API Endpoint
 * Method: GET
 * Headers: Authorization: Bearer <token>
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    http_response_code(405);
    exit;
}

// Get token from header
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

if (empty($authHeader)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    http_response_code(401);
    exit;
}

// Extract token
$token = str_replace('Bearer ', '', $authHeader);

// For demo purposes, we'll accept any token and return demo user
// In production, you would validate the token against your database

// Demo user data (simulating logged in user)
$user_id = 2; // Simulated user ID

try {
    // Get user data
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
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        http_response_code(404);
        exit;
    }

    // Get user achievements
    $achievementsStmt = $conn->prepare("
        SELECT a.name, a.icon, a.tier, ua.earned_at
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ?
    ");
    $achievementsStmt->execute([$user_id]);
    $achievements = $achievementsStmt->fetchAll();

    // Get recent activity
    $activityStmt = $conn->prepare("
        SELECT * FROM points_transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $activityStmt->execute([$user_id]);
    $activities = $activityStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'fullname' => $user['fullname'],
                'phone' => $user['phone'],
                'province' => $user['province'],
                'city' => $user['city'],
                'profile_picture' => $user['profile_picture'],
                'referral_code' => $user['referral_code'],
                'created_at' => $user['created_at']
            ],
            'points' => [
                'total' => $user['total_points'] ?? 0,
                'level' => $user['level'] ?? 1,
                'lifetime' => $user['lifetime_points'] ?? 0
            ],
            'achievements' => $achievements,
            'recent_activity' => $activities
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
    http_response_code(500);
}

$database->closeConnection();
