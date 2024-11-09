<?php
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

header('Content-Type: application/json');

if (!check_auth() || !is_admin()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$security = new Security();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $user_id = $_GET['id'] ?? null;
    
    if ($user_id) {
        // Get specific user
        $stmt = $db->prepare("SELECT id, username, email, role, credits, is_active, created_at, last_login FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        // Get all users with pagination
        $page = $_GET['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $total = $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
        
        $users = $db->query("
            SELECT id, username, email, role, credits, is_active, created_at, last_login 
            FROM users 
            ORDER BY created_at DESC 
            LIMIT $offset, $limit
        ")->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['username'], $data['email'], $data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];
    $role = $data['role'] ?? 'customer';
    $credits = $data['credits'] ?? 0;
    
    // Validate input
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email']);
        exit;
    }
    
    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Password too short']);
        exit;
    }
    
    // Check if username or email exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Username or email already exists']);
        exit;
    }
    
    // Create user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare("
        INSERT INTO users (username, email, password, role, credits, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param("ssssi", $username, $email, $hashed_password, $role, $credits);
    
    if ($stmt->execute()) {
        $user_id = $db->insert_id;
        echo json_encode(['success' => true, 'user_id' => $user_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create user']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $data['id'] ?? null;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID required']);
        exit;
    }
    
    $updates = [];
    $params = [];
    $types = "";
    
    if (isset($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email']);
            exit;
        }
        $updates[] = "email = ?";
        $params[] = $data['email'];
        $types .= "s";
    }
    
    if (isset($data['password'])) {
        if (strlen($data['password']) < 8) {
            http_response_code(400);
            echo json_encode(['error' => 'Password too short']);
            exit;
        }
        $updates[] = "password = ?";
        $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        $types .= "s";
    }
    
    if (isset($data['role'])) {
        $updates[] = "role = ?";
        $params[] = $data['role'];
        $types .= "s";
    }
    
    if (isset($data['credits'])) {
        $updates[] = "credits = ?";
        $params[] = $data['credits'];
        $types .= "i";
    }
    
    if (isset($data['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = $data['is_active'];
        $types .= "i";
    }
    
    if (empty($updates)) {Let's continue with the next 5 files:

<boltArtifact id="backlink-system-part2" title="Backlink System Files - Part 2">
<boltAction type="file" filePath="panel/api/stats.php"><?php
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

header('Content-Type: application/json');

if (!check_auth()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$security = new Security();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $period = $_GET['period'] ?? '30days';
    
    switch ($period) {
        case '7days':
            $interval = '7 DAY';
            break;
        case '30days':
            $interval = '30 DAY';
            break;
        case '90days':
            $interval = '90 DAY';
            break;
        default:
            $interval = '30 DAY';
    }
    
    // Get backlink stats
    $backlink_stats = $db->query("
        SELECT 
            status,
            COUNT(*) as count
        FROM backlinks b
        JOIN sites s ON b.site_id = s.id
        WHERE s.user_id = $user_id
        GROUP BY status
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Get daily backlink additions
    $daily_stats = $db->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM backlinks b
        JOIN sites s ON b.site_id = s.id
        WHERE s.user_id = $user_id
        AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
        GROUP BY DATE(created_at)
        ORDER BY date
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Get site stats
    $site_stats = $db->query("
        SELECT
            COUNT(*) as total_sites,
            SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as verified_sites
        FROM sites
        WHERE user_id = $user_id
    ")->fetch_assoc();
    
    // Get credit usage
    $credit_usage = $db->query("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as credits_used
        FROM backlinks b
        JOIN sites s ON b.site_id = s.id
        WHERE s.user_id = $user_id
        AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)
        GROUP BY DATE(created_at)
        ORDER BY date
    ")->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'backlinks' => $backlink_stats,
            'daily' => $daily_stats,
            'sites' => $site_stats,
            'credits' => $credit_usage
        ]
    ]);
}