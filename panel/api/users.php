<?php
require_once '../../includes/init.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if (!check_auth() || !is_admin()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

if (!$security->validateRequest()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid request']));
}

$response = ['success' => false];

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $user_id = $_GET['id'] ?? null;
        
        if ($user_id) {
            // Tek kullanıcı detayı
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $response['data'] = $stmt->get_result()->fetch_assoc();
            $response['success'] = true;
        } else {
            // Tüm kullanıcılar
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $offset = ($page - 1) * $limit;
            
            $total = $db->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
            
            $stmt = $db->prepare("
                SELECT u.*, 
                       COUNT(DISTINCT s.id) as site_count,
                       COUNT(DISTINCT b.id) as backlink_count
                FROM users u
                LEFT JOIN sites s ON u.id = s.user_id
                LEFT JOIN backlinks b ON s.id = b.site_id
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT ?, ?
            ");
            $stmt->bind_param("ii", $offset, $limit);
            $stmt->execute();
            
            $response['data'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $response['pagination'] = [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ];
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            $response['error'] = 'Tüm alanlar gereklidir';
            break;
        }
        
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $data['username'], $data['email']);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $response['error'] = 'Bu kullanıcı adı veya email zaten kullanılıyor';
            break;
        }
        
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, is_admin, credits) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $is_admin = $data['is_admin'] ?? 0;
        $credits = $data['credits'] ?? 10;
        $stmt->bind_param("ssiii", $data['username'], $data['email'], $hashed_password, $is_admin, $credits);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['id'] = $stmt->insert_id;
        } else {
            $response['error'] = 'Kullanıcı eklenirken bir hata oluştu';
        }
        break;
        
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            $response['error'] = 'Kullanıcı ID gereklidir';
            break;
        }
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['username'])) {
            $updates[] = "username = ?";
            $params[] = $data['username'];
            $types .= 's';
        }
        
        if (isset($data['email'])) {
            $updates[] = "email = ?";
            $params[] = $data['email'];
            $types .= 's';
        }
        
        if (!empty($data['password'])) {
            $updates[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            $types .= 's';
        }
        
        if (isset($data['is_admin'])) {
            $updates[] = "is_admin = ?";
            $params[] = $data['is_admin'];
            $types .= 'i';
        }
        
        if (isset($data['credits'])) {
            $updates[] = "credits = ?";
            $params[] = $data['credits'];
            $types .= 'i';
        }
        
        if (empty($updates)) {
            $response['error'] = 'Güncellenecek alan bulunamadı';
            break;
        }
        
        $params[] = $data['id'];
        $types .= 'i';
        
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        $response['success'] = $stmt->execute();
        if (!$response['success']) {
            $response['error'] = 'Kullanıcı güncellenirken bir hata oluştu';
        }
        break;
        
    case 'DELETE':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            $response['error'] = 'Kullanıcı ID gereklidir';
            break;
        }
        
        if ($data['id'] == $_SESSION['user_id']) {
            $response['error'] = 'Kendinizi silemezsiniz';
            break;
        }
        
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $data['id']);
        
        $response['success'] = $stmt->execute();
        if (!$response['success']) {
            $response['error'] = 'Kullanıcı silinirken bir hata oluştu';
        }
        break;
}

header('Content-Type: application/json');
echo json_encode($response);    