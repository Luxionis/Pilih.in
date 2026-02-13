<?php
/**
 * Events API Endpoint
 * Method: GET, POST
 * GET Params: type, status
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
    // Get filter params
    $type = $_GET['type'] ?? '';
    $status = $_GET['status'] ?? 'published';
    
    // Build query
    $query = "
        SELECT e.*, u.fullname as organizer_name,
               (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) as participant_count
        FROM events e
        LEFT JOIN users u ON e.organizer_id = u.id
        WHERE e.status = ?
    ";
    $params = [$status];
    
    if (!empty($type)) {
        $query .= " AND e.event_type = ?";
        $params[] = $type;
    }
    
    $query .= " ORDER BY e.start_datetime ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
    
    // Format events
    $formattedEvents = array_map(function($event) {
        return [
            'id' => $event['id'],
            'title' => $event['title'],
            'slug' => $event['slug'],
            'description' => $event['description'],
            'banner_image' => $event['banner_image'],
            'event_type' => $event['event_type'],
            'custom_location' => $event['custom_location'],
            'organizer' => $event['organizer_name'],
            'max_participants' => $event['max_participants'],
            'current_participants' => $event['participant_count'],
            'points_reward' => $event['points_reward'],
            'start_datetime' => $event['start_datetime'],
            'end_datetime' => $event['end_datetime'],
            'status' => $event['status']
        ];
    }, $events);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedEvents,
        'total' => count($formattedEvents)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    http_response_code(500);
}

$database->closeConnection();
