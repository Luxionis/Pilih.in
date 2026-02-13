<?php
/**
 * Waste Logging API Endpoint
 * Method: GET, POST
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

// Demo user ID
$user_id = 2;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get waste categories
    $stmt = $conn->query("SELECT * FROM waste_categories WHERE is_active = 1");
    $categories = $stmt->fetchAll();
    
    // Get user's waste logs
    $logStmt = $conn->prepare("
        SELECT wl.*, wc.name as category_name, wc.points_per_kg, wc.color
        FROM waste_logs wl
        JOIN waste_categories wc ON wl.category_id = wc.id
        WHERE wl.user_id = ?
        ORDER BY wl.logged_at DESC
        LIMIT 50
    ");
    $logStmt->execute([$user_id]);
    $logs = $logStmt->fetchAll();
    
    // Get user stats
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_logs,
            SUM(weight_kg) as total_weight,
            SUM(points_earned) as total_points,
            SUM(co2_reduced) as total_co2
        FROM waste_logs 
        WHERE user_id = ?
    ");
    $statsStmt->execute([$user_id]);
    $stats = $statsStmt->fetch();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'categories' => $categories,
            'logs' => $logs,
            'stats' => [
                'total_logs' => $stats['total_logs'] ?? 0,
                'total_weight' => round($stats['total_weight'] ?? 0, 2),
                'total_points' => $stats['total_points'] ?? 0,
                'total_co2' => round($stats['total_co2'] ?? 0, 2)
            ]
        ]
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log waste
    $input = json_decode(file_get_contents('php://input'), true);
    
    $category_id = $input['category_id'] ?? 0;
    $weight = floatval($input['weight_kg'] ?? 0);
    $notes = $input['notes'] ?? '';
    
    if (empty($category_id) || $weight <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Kategori dan berat sampah harus diisi'
        ]);
        http_response_code(400);
        exit;
    }
    
    try {
        // Get category points
        $catStmt = $conn->prepare("SELECT points_per_kg, co2_reduction_per_kg FROM waste_categories WHERE id = ?");
        $catStmt->execute([$category_id]);
        $category = $catStmt->fetch();
        
        if (!$category) {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak ditemukan']);
            http_response_code(404);
            exit;
        }
        
        // Calculate points and CO2
        $points_earned = $weight * $category['points_per_kg'];
        $co2_reduced = $weight * $category['co2_reduction_per_kg'];
        
        // Insert waste log
        $stmt = $conn->prepare("
            INSERT INTO waste_logs (user_id, category_id, weight_kg, points_earned, co2_reduced, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $category_id, $weight, $points_earned, $co2_reduced, $notes]);
        
        // Update user points
        $updatePoints = $conn->prepare("
            UPDATE user_points 
            SET total_points = total_points + ?, 
                lifetime_points = lifetime_points + ?
            WHERE user_id = ?
        ");
        $updatePoints->execute([$points_earned, $points_earned, $user_id]);
        
        // Record transaction
        $transStmt = $conn->prepare("
            INSERT INTO points_transactions (user_id, points, transaction_type, source, description)
            VALUES (?, ?, 'earn', 'waste_log', ?)
        ");
        $transStmt->execute([$user_id, $points_earned, "Log {$weight}kg sampah"]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Log sampah berhasil!',
            'data' => [
                'weight' => $weight,
                'points_earned' => $points_earned,
                'co2_reduced' => $co2_reduced
            ]
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
