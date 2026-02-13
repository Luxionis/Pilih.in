<?php
/**
 * Leaderboard API Endpoint
 * Method: GET
 * GET Params: period (weekly, monthly, all)
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    http_response_code(405);
    exit;
}

try {
    $period = $_GET['period'] ?? 'all';
    
    // Get leaderboard data
    $query = "
        SELECT u.id, u.username, u.fullname, u.profile_picture, u.city,
               up.total_points, up.level, up.rank_position,
               (SELECT COUNT(*) FROM waste_logs WHERE user_id = u.id) as activity_count
        FROM users u
        JOIN user_points up ON u.id = up.user_id
        WHERE u.is_active = 1
    ";
    
    if ($period === 'weekly') {
        // For weekly, we'd need more complex query with date filtering
        // For now, we'll just return all-time
    } elseif ($period === 'monthly') {
        // Same as above
    }
    
    $query .= " ORDER BY up.total_points DESC LIMIT 100";
    
    $stmt = $conn->query($query);
    $users = $stmt->fetchAll();
    
    // Format leaderboard
    $leaderboard = array_map(function($index, $user) {
        $rank = $index + 1;
        $badge = 'bronze';
        if ($rank === 1) $badge = 'gold';
        elseif ($rank === 2) $badge = 'silver';
        elseif ($rank === 3) $badge = 'bronze';
        
        return [
            'rank' => $rank,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'avatar' => $user['profile_picture'],
                'city' => $user['city']
            ],
            'points' => $user['total_points'],
            'level' => $user['level'],
            'activities' => $user['activity_count'],
            'badge' => $badge
        ];
    }, array_keys($users), $users);
    
    echo json_encode([
        'success' => true,
        'data' => $leaderboard,
        'total' => count($leaderboard)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    http_response_code(500);
}

$database->closeConnection();
