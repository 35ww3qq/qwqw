<?php
require_once '../includes/init.php';
require_once '../includes/security.php';
require_once '../includes/rate_limiter.php';

header('Content-Type: application/json');

$security = new Security();
$rate_limiter = new RateLimiter($db);

// Rate limiting check
$ip = $_SERVER['REMOTE_ADDR'];
if (!$rate_limiter->check($ip, 'api')) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests']);
    exit;
}

// CSRF protection for non-GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && !$security->validateCSRF()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$route = $_GET['route'] ?? '';

switch ($route) {
    case 'links':
        require_once 'routes/links.php';
        break;
    case 'sites':
        require_once 'routes/sites.php';
        break;
    case 'stats':
        require_once 'routes/stats.php';
        break;
    case 'favorites':
        require_once 'routes/favorites.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

$rate_limiter->log($ip, 'api');
?>