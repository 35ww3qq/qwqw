<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

// Check authentication and admin role
if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

$security = new Security();
$current_page = 'sites';

// Handle site actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $security->validateCSRF()) {
    $action = $_POST['action'] ?? '';
    $site_id = (int)$_POST['site_id'] ?? 0;
    
    switch ($action) {
        case 'verify':
            if ($site_id > 0) {
                $db->query("UPDATE sites SET is_verified = 1 WHERE id = $site_id");
                // Log activity
                log_activity($user_id, 'verify_site', "Site ID: $site_id verified");
            }
            break;
            
        case 'delete':
            if ($site_id > 0) {
                $db->begin_transaction();
                try {
                    // Delete backlinks first
                    $db->query("DELETE FROM backlinks WHERE site_id = $site_id");
                    // Then delete site
                    $db->query("DELETE FROM sites WHERE id = $site_id");
                    $db->commit();
                    // Log activity
                    log_activity($user_id, 'delete_site', "Site ID: $site_id deleted");
                } catch (Exception $e) {
                    $db->rollback();
                    error_log("Error deleting site: " . $e->getMessage());
                }
            }
            break;
    }
}

// Get sites with pagination
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$total_sites = $db->query("SELECT COUNT(*) FROM sites")->fetch_row()[0];
$total_pages = ceil($total_sites / $limit);

$sites = $db->query("
    SELECT s.*, 
           u.username,
           COUNT(b.id) as backlink_count,
           MAX(b.last_checked) as last_check
    FROM sites s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN backlinks b ON s.id = b.site_id
    GROUP BY s.id
    ORDER BY s.created_at DESC
    LIMIT $offset, $limit
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php require_once '../../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Site Yönetimi</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Domain</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Kullanıcı</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Backlink</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Son Kontrol</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sites as $site): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($site['domain']); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo date('d.m.Y H:i', strtotime($site['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($site['username']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($site['is_verified']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Doğrulanmış
                                    </span>
                                <?php else: ?>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="verify">
                                        <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                        <button type="submit" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Doğrulanmamış
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $site['backlink_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $site['last_check'] ? date('d.m.Y H:i', strtotime($site['last_check'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="backlinks.php?site_id=<?php echo $site['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-link"></i>
                                </a>
                                <form method="POST" action="" class="inline" onsubmit="return confirm('Bu siteyi silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($sites)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                Henüz site eklenmemiş
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex justify-between">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="text-blue-600 hover:text-blue-900">← Önceki</a>
                    <?php else: ?>
                        <span class="text-gray-400">← Önceki</span>
                    <?php endif; ?>
                    
                    <span class="text-gray-600">
                        Sayfa <?php echo $page; ?>/<?php echo $total_pages; ?>
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="text-blue-600 hover:text-blue-900">Sonraki →</a>
                    <?php else: ?>
                        <span class="text-gray-400">Sonraki →</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Add any JavaScript functionality here
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize any components if needed
    });
    </script>
</body>
</html>