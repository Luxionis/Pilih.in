<?php
/**
 * Rewards API Endpoint (Tukar Poin)
 * Method: GET, POST
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

// Demo user ID
$user_id = 2;

// Get user's points
$pointsStmt = $conn->prepare("SELECT total_points, level FROM user_points WHERE user_id = ?");
$pointsStmt->execute([$user_id]);
$userPoints = $pointsStmt->fetch();
$totalPoints = $userPoints['total_points'] ?? 0;

// Rewards catalog
$rewards = [
    [
        'id' => 1,
        'name' => 'Voucher Belanja Supermarket',
        'description' => 'Voucher Rp 25.000 untuk belanja di supermarket terdekat',
        'category' => 'voucher',
        'points_cost' => 50,
        'partner' => 'Indomaret',
        'valid_days' => 30
    ],
    [
        'id' => 2,
        'name' => 'Voucher Transport Online',
        'description' => 'Voucher Rp 35.000 untuk Gojek/Grab',
        'category' => 'voucher',
        'points_cost' => 75,
        'partner' => 'Gojek/Grab',
        'valid_days' => 30
    ],
    [
        'id' => 3,
        'name' => 'Voucher Makanan',
        'description' => 'Voucher Rp 20.000 untuk pesan makanan online',
        'category' => 'voucher',
        'points_cost' => 40,
        'partner' => 'Food Delivery',
        'valid_days' => 30
    ],
    [
        'id' => 4,
        'name' => 'Voucher Listrik PLN',
        'description' => 'Voucher Rp 50.000 untuk pembayaran listrik',
        'category' => 'voucher',
        'points_cost' => 100,
        'partner' => 'PLN',
        'valid_days' => 60
    ],
    [
        'id' => 5,
        'name' => 'Voucher Pulsa',
        'description' => 'Voucher Rp 15.000 untuk pembelian pulsa',
        'category' => 'voucher',
        'points_cost' => 25,
        'partner' => 'Telkomsel',
        'valid_days' => 30
    ],
    [
        'id' => 6,
        'name' => 'Produk Ramah Lingkungan',
        'description' => 'Tas kanvas/Tumbler eco-friendly Pilah.in',
        'category' => 'product',
        'points_cost' => 150,
        'partner' => 'Pilah.in Store',
        'valid_days' => 90
    ]
];

// Tier discounts
$tiers = [
    ['points' => 30, 'discount' => 10],
    ['points' => 60, 'discount' => 15],
    ['points' => 90, 'discount' => 30]
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get available tier discount
    $currentDiscount = 0;
    foreach ($tiers as $tier) {
        if ($totalPoints >= $tier['points']) {
            $currentDiscount = $tier['discount'];
        }
    }
    
    // Check which tier user is eligible for
    $eligibleTiers = [];
    foreach ($tiers as $tier) {
        $eligibleTiers[] = [
            'required_points' => $tier['points'],
            'discount' => $tier['discount'],
            'eligible' => $totalPoints >= $tier['points']
        ];
    }
    
    // Check which rewards user can afford
    $availableRewards = array_map(function($r) use ($totalPoints) {
        $r['can_redeem'] = $totalPoints >= $r['points_cost'];
        return $r;
    }, $rewards);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'user_points' => $totalPoints,
            'current_discount' => $currentDiscount,
            'tiers' => $eligibleTiers,
            'rewards' => $availableRewards
        ]
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Redeem reward
    $input = json_decode(file_get_contents('php://input'), true);
    
    $reward_id = $input['reward_id'] ?? 0;
    $tier_id = $input['tier_id'] ?? 0; // For tier discounts
    
    if (!empty($reward_id)) {
        // Find reward
        $reward = null;
        foreach ($rewards as $r) {
            if ($r['id'] == $reward_id) {
                $reward = $r;
                break;
            }
        }
        
        if (!$reward) {
            echo json_encode(['success' => false, 'message' => 'Reward tidak ditemukan']);
            http_response_code(404);
            exit;
        }
        
        if ($totalPoints < $reward['points_cost']) {
            echo json_encode(['success' => false, 'message' => 'Poin tidak mencukupi']);
            http_response_code(400);
            exit;
        }
        
        // Deduct points
        $newPoints = $totalPoints - $reward['points_cost'];
        $updateStmt = $conn->prepare("UPDATE user_points SET total_points = ? WHERE user_id = ?");
        $updateStmt->execute([$newPoints, $user_id]);
        
        // Generate redemption code
        $redemptionCode = 'PRD-' . strtoupper(bin2hex(random_bytes(4)));
        
        echo json_encode([
            'success' => true,
            'message' => 'Penukaran berhasil!',
            'data' => [
                'redemption_code' => $redemptionCode,
                'reward' => $reward['name'],
                'points_spent' => $reward['points_cost'],
                'remaining_points' => $newPoints
            ]
        ]);
        
    } elseif (!empty($tier_id)) {
        // Process tier discount (for iuran sampah discount)
        // This would typically create a discount code for the next billing cycle
        
        $tier = null;
        foreach ($tiers as $t) {
            if ($t['points'] == $tier_id) {
                $tier = $t;
                break;
            }
        }
        
        if (!$tier) {
            echo json_encode(['success' => false, 'message' => 'Tier tidak ditemukan']);
            http_response_code(404);
            exit;
        }
        
        if ($totalPoints < $tier['points']) {
            echo json_encode(['success' => false, 'message' => 'Poin tidak mencukupi']);
            http_response_code(400);
            exit;
        }
        
        // Deduct points for tier
        $newPoints = $totalPoints - $tier['points'];
        $updateStmt = $conn->prepare("UPDATE user_points SET total_points = ? WHERE user_id = ?");
        $updateStmt->execute([$newPoints, $user_id]);
        
        // Generate discount code
        $discountCode = 'DISC' . $tier['discount'] . '-' . strtoupper(bin2hex(random_bytes(3)));
        
        echo json_encode([
            'success' => true,
            'message' => 'Penukaran diskon berhasil!',
            'data' => [
                'discount_code' => $discountCode,
                'discount_percent' => $tier['discount'],
                'points_spent' => $tier['points'],
                'remaining_points' => $newPoints,
                'valid_until' => date('Y-m-t', strtotime('+1 month')) // End of current month
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        http_response_code(400);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    http_response_code(405);
}

$database->closeConnection();
