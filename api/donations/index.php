<?php
/**
 * Donations API Endpoint
 * Method: GET, POST
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

// For demo, we'll return sample donation campaigns
// In production, this would come from a donations table

$donations = [
    [
        'id' => 1,
        'title' => 'Bantu Korban Banjir Jakarta',
        'description' => 'Donasikan pakaian layak pakai untuk korban banjir di wilayah Jakarta dan sekitarnya.',
        'category' => 'sandang',
        'target' => 25000000,
        'raised' => 12500000,
        'days_left' => 15,
        'status' => 'active'
    ],
    [
        'id' => 2,
        'title' => 'Paket Makanan untuk Panti',
        'description' => 'Bantu kami menyediakan paket makanan bergizi untuk anak-anak di panti asuhan.',
        'category' => 'pangan',
        'target' => 10000000,
        'raised' => 8200000,
        'days_left' => 7,
        'status' => 'active'
    ],
    [
        'id' => 3,
        'title' => 'Renovasi Rumah Tidak Layak Huni',
        'description' => 'Bantu renovasi rumah tidak layak huni untuk keluarga kurang mampu di Jawa Barat.',
        'category' => 'papan',
        'target' => 100000000,
        'raised' => 45000000,
        'days_left' => 30,
        'status' => 'active'
    ],
    [
        'id' => 4,
        'title' => 'Pembersihan Sampah Lumpur',
        'description' => 'Dukungan untuk pembersihan sampah lumpur pasca banjir di kawasan permukiman.',
        'category' => 'sampah',
        'target' => 5000000,
        'raised' => 3500000,
        'days_left' => 3,
        'status' => 'active'
    ],
    [
        'id' => 5,
        'title' => 'Pakaian Anak untuk Pedesaan',
        'description' => 'Donasi pakaian anak layak pakai untuk anak-anak di daerah pedesaan NTT.',
        'category' => 'sandang',
        'target' => 7500000,
        'raised' => 2800000,
        'days_left' => 20,
        'status' => 'active'
    ],
    [
        'id' => 6,
        'title' => 'Air Bersih untuk Terdampak',
        'description' => 'Bantu menyediakan air bersih dan sanitizer untuk korban banjir bandang.',
        'category' => 'bencana',
        'target' => 20000000,
        'raised' => 18900000,
        'days_left' => 2,
        'status' => 'active'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $category = $_GET['category'] ?? '';
    
    // Filter by category if provided
    if (!empty($category)) {
        $donations = array_filter($donations, function($d) use ($category) {
            return $d['category'] === $category;
        });
    }
    
    // Calculate progress percentage
    $donations = array_map(function($d) {
        $d['progress'] = round(($d['raised'] / $d['target']) * 100);
        return $d;
    }, $donations);
    
    echo json_encode([
        'success' => true,
        'data' => array_values($donations),
        'total' => count($donations)
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process donation
    $input = json_decode(file_get_contents('php://input'), true);
    
    $donation_id = $input['donation_id'] ?? 0;
    $amount = $input['amount'] ?? 0;
    $payment_method = $input['payment_method'] ?? '';
    
    if (empty($donation_id) || empty($amount) || $amount < 10000) {
        echo json_encode([
            'success' => false,
            'message' => 'Minimal donasi adalah Rp 10.000'
        ]);
        http_response_code(400);
        exit;
    }
    
    // In production, you would:
    // 1. Create donation record
    // 2. Process payment
    // 3. Send confirmation
    
    echo json_encode([
        'success' => true,
        'message' => 'Donasi berhasil! Terima kasih atas kebaikannya.',
        'data' => [
            'donation_id' => $donation_id,
            'amount' => $amount,
            'payment_method' => $payment_method
        ]
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    http_response_code(405);
}

$database->closeConnection();
