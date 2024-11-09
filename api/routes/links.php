<?php
if (!defined('INCLUDED_FROM_API')) {
    exit('Direct access not allowed');
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $link_id = $_GET['id'] ?? null;
        if ($link_id) {
            // Get single link details
            $stmt = $db->prepare("
                SELECT l.*, s.domain, s.da, s.pa,
                       COUNT(DISTINCT b.id) as active_backlinks,
                       AVG(m.loading_time) as avg_loading_time
                FROM links l
                JOIN sites s ON l.site_id = s.id
                LEFT JOIN backlinks b ON s.id = b.site_id AND b.status = 'active'
                LEFT JOIN link_metrics m ON l.id = m.link_id
                WHERE l.id = ?
                GROUP BY l.id
            ");
            $stmt->bind_param("i", $link_id);
            $stmt->execute();
            $link = $stmt->get_result()->fetch_assoc();

            if (!$link) {
                http_response_code(404);
                echo json_encode(['error' => 'Link not found']);
                exit;
            }

            // Get link history
            $stmt = $db->prepare("
                SELECT * FROM link_metrics
                WHERE link_id = ?
                ORDER BY checked_at DESC
                LIMIT 10
            ");
            $stmt->bind_param("i", $link_id);
            $stmt->execute();
            $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            echo json_encode([
                'success' => true,
                'link' => $link,
                'history' => $history
            ]);
        } else {
            // Get filtered links list
            require_once 'links/list.php';
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'bulk-add':
                require_once 'links/bulk_add.php';
                break;
            case 'bulk-extend':
                require_once 'links/bulk_extend.php';
                break;
            case 'bulk-renew':
                require_once 'links/bulk_renew.php';
                break;
            case 'bulk-refund':
                require_once 'links/bulk_refund.php';
                break;
            case 'bulk-edit':
                require_once 'links/bulk_edit.php';
                break;
            case 'purchase':
                require_once 'links/purchase.php';
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?>