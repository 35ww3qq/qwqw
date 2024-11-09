<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

$security = new Security();
$current_page = 'users';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $security->validateCSRF()) {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;
    
    switch ($action) {
        case 'toggle_status':
            $db->query("UPDATE users SET is_active = NOT is_active WHERE id = $user_id AND role != 'admin'");
            break;
            
        case 'add_credits':
            $credits = (int)$_POST['credits'] ?? 0;
            if ($credits > 0) {
                $db->query("UPDATE users SET credits = credits + $credits WHERE id = $user_id");
            }
            break;
            
        case 'delete':
            $db->query("DELETE FROM users WHERE id = $user_id AND role != 'admin'");
            break;
    }
}

// Get users with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total_users = $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_pages = ceil($total_users / $limit);

$users = $db->query("
    SELECT u.*, 
           COUNT(DISTINCT s.id) as site_count,
           COUNT(DISTINCT b.id) as backlink_count
    FROM users u
    LEFT JOIN sites s ON u.id = s.user_id
    LEFT JOIN backlinks b ON s.id = b.site_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $offset, $limit
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php require_once '../../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Kullanıcı Yönetimi</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Kullanıcı</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Rol</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Krediler</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Site/Backlink</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Kayıt: <?php echo date('d.m.Y', strtotime($user['created_at'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" action="" class="flex items-center space-x-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="add_credits">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <span class="text-sm font-medium"><?php echo $user['credits']; ?></span>
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <input type="number" name="credits" class="w-20 px-2 py-1 border rounded text-sm" min="1" placeholder="Ekle">
                                        <button type="submit" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $user['site_count']; ?> / <?php echo $user['backlink_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="<?php echo $user['is_active'] ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo $user['is_active'] ? 'Aktif' : 'Pasif'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-green-600">Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($user['role'] !== 'admin'): ?>
                                    <form method="POST" action="" class="inline" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
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
</body>
</html>