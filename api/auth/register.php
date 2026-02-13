<?php
/**
 * Register API Endpoint
 * Method: POST
 * Body: { username, email, password, fullname, phone, province, city, referral }
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
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$fullname = trim($input['fullname'] ?? '');
$phone = trim($input['phone'] ?? '');
$province = trim($input['province'] ?? '');
$city = trim($input['city'] ?? '');
$referral = trim($input['referral'] ?? '');

// Validation
$errors = [];

if (empty($username) || strlen($username) < 4) {
    $errors[] = 'Username minimal 4 karakter';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email tidak valid';
}

if (empty($password) || strlen($password) < 8) {
    $errors[] = 'Password minimal 8 karakter';
}

if (empty($fullname)) {
    $errors[] = 'Nama lengkap harus diisi';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    http_response_code(400);
    exit;
}

try {
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Email atau username sudah digunakan'
        ]);
        http_response_code(409);
        exit;
    }

    // Generate referral code
    $referral_code = strtoupper(substr($username, 0, 4) . rand(1000, 9999));

    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password_hash, fullname, phone, province, city, referral_code) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$username, $email, $password_hash, $fullname, $phone, $province, $city, $referral_code]);
    
    $user_id = $conn->lastInsertId();

    // Create user points record
    $pointsStmt = $conn->prepare("INSERT INTO user_points (user_id, total_points, lifetime_points, level) VALUES (?, 0, 0, 1)");
    $pointsStmt->execute([$user_id]);

    // Create user preferences
    $prefStmt = $conn->prepare("INSERT INTO user_preferences (user_id) VALUES (?)");
    $prefStmt->execute([$user_id]);

    // Process referral bonus if provided
    if (!empty($referral)) {
        $refStmt = $conn->prepare("SELECT id FROM users WHERE referral_code = ?");
        $refStmt->execute([strtoupper($referral)]);
        $referrer = $refStmt->fetch();
        
        if ($referrer) {
            // Give bonus points to referrer
            $bonusStmt = $conn->prepare("UPDATE user_points SET total_points = total_points + 500 WHERE user_id = ?");
            $bonusStmt->execute([$referrer['id']]);
            
            // Give bonus to new user
            $newUserPoints = $conn->prepare("UPDATE user_points SET total_points = total_points + 500 WHERE user_id = ?");
            $newUserPoints->execute([$user_id]);
        }
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Registrasi berhasil! Silakan login.',
        'data' => [
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email
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
