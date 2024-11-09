<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

$current_page = 'premium-indexer';

// Get indexing stats
$stats = [
    'total_indexed' => $db->query("SELECT COUNT(*) FROM backlinks WHERE status = 'active'")->fetch_row()[0],
    'pending_index' => $db->query("SELECT COUNT(*) FROM backlinks WHERE status = 'pending'")->fetch_row()[0],
    'failed_index' => $db->query("SELECT COUNT(*) FROM backlinks WHERE status = 'removed'")->fetch_row()[0]
];

// Get recent indexing jobs
$jobs = $db->query("
    SELECT b.*, s.domain 
    FROM backlinks b
    JOIN sites s ON b.site_id = s.id
    ORDER BY b.last_checked DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Indexer - Hacklink Market</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-900">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Total Indexed -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-900 text-green-300">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">İndekslenen</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo number_format($stats['total_indexed']); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Pending Index -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-900 text-yellow-300">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Bekleyen</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo number_format($stats['pending_index']); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Failed Index -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-900 text-red-300">
                        <i class="fas fa-times-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Başarısız</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo number_format($stats['failed_index']); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indexing Tools -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Bulk Indexing -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">Toplu İndeksleme</h3>
                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400">URL Listesi</label>
                        <textarea name="urls" rows="5" 
                                  class="mt-1 block w-full bg-gray-700 border-gray-600 rounded-md text-white"
                                  placeholder="Her satıra bir URL gelecek şekilde"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400">İndeksleme Yöntemi</label>
                        <select name="method" class="mt-1 block w-full bg-gray-700 border-gray-600 rounded-md text-white">
                            <option value="google">Google</option>
                            <option value="bing">Bing</option>
                            <option value="yandex">Yandex</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full btn-primary">
                        <i class="fas fa-rocket mr-2"></i> İndekslemeyi Başlat
                    </button>
                </form>
            </div>

            <!-- Index Checker -->
            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-white mb-4">İndeks Kontrolü</h3>
                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400">URL</label>
                        <input type="url" name="url" 
                               class="mt-1 block w-full bg-gray-700 border-gray-600 rounded-md text-white"
                               placeholder="https://example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-400">Arama Motoru</label>
                        <select name="engine" class="mt-1 block w-full bg-gray-700 border-gray-600 rounded-md text-white">
                            <option value="google">Google</option>
                            <option value="bing">Bing</option>
                            <option value="yandex">Yandex</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full btn-primary">
                        <i class="fas fa-search mr-2"></i> İndeks Durumunu Kontrol Et
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Jobs -->
        <div class="bg-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-white mb-4">Son İndeksleme İşlemleri</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Domain</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">URL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Son Kontrol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                <?php echo htmlspecialchars($job['domain']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="<?php echo htmlspecialchars($job['target_url']); ?>" 
                                   class="text-blue-400 hover:text-blue-300"
                                   target="_blank">
                                    <?php echo htmlspecialchars($job['target_url']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'active' => 'bg-green-900 text-green-300',
                                    'pending' => 'bg-yellow-900 text-yellow-300',
                                    'removed' => 'bg-red-900 text-red-300'
                                ];
                                $color = $status_colors[$job['status']] ?? 'bg-gray-900 text-gray-300';
                                ?>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $color; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                <?php echo $job['last_checked'] ? date('d.m.Y H:i', strtotime($job['last_checked'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button onclick="reindexUrl(<?php echo $job['id']; ?>)"
                                        class="text-blue-400 hover:text-blue-300">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function reindexUrl(id) {
        if (confirm('Bu URL\'yi yeniden indekslemek istiyor musunuz?')) {
            // Reindex URL
        }
    }
    </script>
</body>
</html>