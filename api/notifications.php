<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!check_auth()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user's notifications
$notifications = $db->query("
    SELECT * FROM notifications 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Format notifications
$formatted_notifications = array_map(function($notification) {
    return [
        'title' => $notification['title'],
        'message' => $notification['message'],
        'time' => time_ago($notification['created_at'])
    ];
}, $notifications);

echo json_encode([
    'success' => true,
    'notifications' => $formatted_notifications
]);
?>