<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

if (!check_auth()) {
    header('Location: ../../login.php');
    exit;
}

$security = new Security();
$user_id = $_SESSION['user_id'];
$current_page = 'backlinks';

// Get site ID if provided
$site_id = $_GET['site_id'] ?? null;

// Get site info if site_id is provided
$site = null;
if ($site_id) {
    $stmt = $db->prepare("SELECT * FROM sites WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $site_id, $user_id);
    $stmt->execute();
    $site = $stmt->get_result()->fetch_assoc();
    
    if (!$site) {
        header('Location: sites.php');
        exit;
    }
}

// Handle backlink actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $security->validateCSRF()) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            $target_url = $_POST['target_url'] ?? '';
            $anchor_text = $_POST['anchor_text'] ?? '';
            
            // Check if user has enough credits
            $credits = get_user_credits($user_id);
            if ($credits < 1) {
                $error = 'Yetersiz kredi';
            } elseif (empty($target_url) || empty($anchor_text)) {
                $error = 'Tüm alanları doldurun';
            } else {
                $db->begin_transaction();
                try {
                    // Add backlink
                    $stmt = $db->prepare("
                        INSERT INTO backlinks (site_id, target_url, anchor_text, status) 
                        VALUES (?, ?, ?, 'pending')
                    ");
                    $stmt->bind_param("iss", $site_id, $target_url, $anchor_text);
                    $stmt->execute();
                    
                    // Deduct credit
                    $db->query("UPDATE users SET credits = credits - 1 WHERE id = $user_id");
                    
                    $db->commit();
                    header("Location: backlinks.php?site_id=$site_id");
                    exit;
                } catch (Exception $e) {
                    $db->rollback();
                    $error = 'Backlink eklenirken bir hata oluştu';
                }
            }
            break;
            
        case 'delete':
            $backlink_id = $_POST['backlink_id'] ?? 0;
            if ($backlink_id) {
                $db->query("DELETE FROM backlinks WHERE id = $backlink_id AND site_id = $site_id");
                header("Location: backlinks.php?site_id=$site_id");
                exit;
            }
            break;
    }
}

// Get user's sites for dropdown
$sites = get_user_sites($user_id);

// Get backlinks if site is selected
$backlinks = [];
if ($site) {
    $backlinks = $db->query("
        SELECT * FROM backlinks 
        WHERE site_id = $site_id 
        ORDER BY created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backlinklerim - Backlink System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php require_once '../includes/customer-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Site Selection -->
        <div class="mb-8">
            <select id="site-selector" onchange="changeSite(this.value)" class="block w-full md:w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">Site Seçin</option>
                <?php foreach ($sites as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo $site_id == $s['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s['domain']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($site): ?>
            <!-- Site Info -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($site['domain']); ?></h2>
                        <p class="text-gray-600">
                            Durum: 
                            <?php if ($site['is_verified']): ?>
                                <span class="text-green-600">Doğrulanmış</span>
                            <?php else: ?>
                                <span class="text-yellow-600">Doğrulanmamış</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <button onclick="showAddBacklinkModal()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-plus mr-2"></i> Backlink Ekle
                    </button>
                </div>
            </div>

            <!-- Backlinks Table -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Hedef URL</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Anchor Text</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Son Kontrol</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($backlinks as $backlink): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="<?php echo htmlspecialchars($backlink['target_url']); ?>" target="_blank" class="text-blue-600 hover:text-blue-900">
                                    <?php echo htmlspecialchars($backlink['target_url']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo htmlspecialchars($backlink['anchor_text']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'active' => 'green',
                                    'pending' => 'yellow',
                                    'removed' => 'red'
                                ];
                                $color = $status_colors[$backlink['status']] ?? 'gray';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                    <?php echo ucfirst($backlink['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $backlink['last_checked'] ? date('d.m.Y H:i', strtotime($backlink['last_checked'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" action="" class="inline" onsubmit="return confirm('Bu backlinki silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="backlink_id" value="<?php echo $backlink['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($backlinks)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Henüz backlink eklenmemiş
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Backlink Modal -->
            <div id="addBacklinkModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold mb-4">Yeni Backlink Ekle</h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hedef URL</label>
                                <input type="url" name="target_url" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Anchor Text</label>
                                <input type="text" name="anchor_text" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="text-sm text-gray-500">
                                Kalan krediniz: <?php echo get_user_credits($user_id); ?>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end space-x-3">
                            <button type="button" onclick="hideAddBacklinkModal()"
                                    class="px-4 py-2 text-gray-700 hover:text-gray-900">
                                İptal
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                Ekle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="text-gray-500 mb-4">
                    <i class="fas fa-link text-4xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Site Seçin</h3>
                <p class="text-gray-600">
                    Backlinkleri görüntülemek için bir site seçin veya
                    <a href="sites.php" class="text-blue-600 hover:text-blue-500">yeni bir site ekleyin</a>.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function changeSite(siteId) {
        if (siteId) {
            window.location.href = `backlinks.php?site_id=${siteId}`;
        } else {
            window.location.href = 'backlinks.php';
        }
    }
    
    function showAddBacklinkModal() {
        document.getElementById('addBacklinkModal').classList.remove('hidden');
    }
    
    function hideAddBacklinkModal() {
        document.getElementById('addBacklinkModal').classList.add('hidden');
    }
    </script>
</body>
</html>