<?php
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

header('Content-Type: application/json');

if (!check_auth()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$security = new Security();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $site_id = $_GET['site_id'] ?? null;
    
    if (!$site_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Site ID required']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT user_id FROM sites WHERE id = ?");
    $stmt->bind_param("i", $site_id);
    $stmt->execute();
    $site = $stmt->get_result()->fetch_assoc();
    
    if (!$site || $site['user_id'] !== $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM backlinks WHERE site_id = ?");
    $stmt->bind_param("i", $site_id);
    $stmt->execute();
    $backlinks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode(['success' => true, 'backlinks' => $backlinks]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['site_id'], $data['target_url'], $data['anchor_text'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    if (!$security->validateUrl($data['target_url'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid target URL']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT s.user_id, u.credits FROM sites s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
    $stmt->bind_param("i", $data['site_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || $result['user_id'] !== $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
    
    if ($result['credits'] < 1) {
        http_response_code(402);
        echo json_encode(['error' => 'Insufficient credits']);
        exit;
    }
    
    $stmt = $db->prepare("INSERT INTO backlinks (site_id, target_url, anchor_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $data['site_id'], $data['target_url'], $data['anchor_text']);
    
    if ($stmt->execute()) {
        $db->query("UPDATE users SET credits = credits - 1 WHERE id = " . $_SESSION['user_id']);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add backlink']);
    }
}