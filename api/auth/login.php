<?php
/**
 * Login API Endpoint
 * Method: POST
 * Body: { email, password, remember }
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    http_response_code(405);
    exit;
}

// Validate input
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Email dan password harus diisi'
    ]);
    http_response_code(400);
    exit;
}

try {
    // Check if user exists
    $stmt = $conn->prepare("
        SELECT id, username, email, fullname, password_hash, role, is_active 
        FROM users 
        WHERE email = ? OR username = ?
    ");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Email atau password salah'
        ]);
        http_response_code(401);
        exit;
    }

    // Check if user is active
    if (!$user['is_active']) {
        echo json_encode([
            'success' => false,
            'message' => 'Akun Anda tidak aktif'
        ]);
        http_response_code(401);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Email atau password salah'
        ]);
        http_response_code(401);
        exit;
    }

    // Generate token (simple JWT-like token)
    $token = bin2hex(random_bytes(32));
    $user_id = $user['id'];

    // Store token in database (you might want to create a tokens table)
    // For now, we'll just return the user data
    
    // Get user points
    $pointsStmt = $conn->prepare("SELECT total_points, level, rank_position FROM user_points WHERE user_id = ?");
    $pointsStmt->execute([$user_id]);
    $points = $pointsStmt->fetch();

    // Update last login
    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user_id]);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil',
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'fullname' => $user['fullname'],
                'role' => $user['role'],
                'points' => $points['total_points'] ?? 0,
                'level' => $points['level'] ?? 1
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
    http_response_code(500);
}

$database->closeConnection();
