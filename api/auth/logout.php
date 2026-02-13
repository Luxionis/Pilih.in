<?php
/**
 * Logout API Endpoint
 * Method: POST
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    http_response_code(405);
    exit;
}

// In a real implementation, you would invalidate the token in the database
// For now, we'll just return success

echo json_encode([
    'success' => true,
    'message' => 'Logout berhasil'
]);
