<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

$current_page = 'link-market';

// Get available links
$links = $db->query("
    SELECT l.*, s.domain, s.da, s.pa,
           COUNT(DISTINCT b.id) as active_backlinks,
           AVG(m.loading_time) as avg_loading_time
    FROM links l
    JOIN sites s ON l.site_id = s.id
    LEFT JOIN backlinks b ON s.id = b.site_id AND b.status = 'active'
    LEFT JOIN link_metrics m ON l.id = m.link_id
    GROUP BY l.id
    ORDER BY l.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Market - Hacklink Market</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-900">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Actions -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Link Market</h2>
            <div class="flex space-x-4">
                <button onclick="bulkAdd()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i> Toplu Link Ekle
                </button>
                <button onclick="bulkEdit()" class="btn-secondary">
                    <i class="fas fa-edit mr-2"></i> Toplu Düzenle
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-gray-800 rounded-lg p-4 mb-6">
            <form id="filters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400">DA/PA Aralığı</label>
                    <div class="flex space-x-2">
                        <input type="number" name="da_min" placeholder="Min DA" class="form-input bg-gray-700 text-white">
                        <input type="number" name="da_max" placeholder="Max DA" class="form-input bg-gray-700 text-white">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">Fiyat Aralığı</label>
                    <div class="flex space-x-2">
                        <input type="number" name="price_min" placeholder="Min" class="form-input bg-gray-700 text-white">
                        <input type="number" name="price_max" placeholder="Max" class="form-input bg-gray-700 text-white">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">Link Tipi</label>
                    <select name="type" class="form-select bg-gray-700 text-white">
                        <option value="">Tümü</option>
                        <option value="PHP">PHP</option>
                        <option value="JS">JS</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">Durum</label>
                    <select name="status" class="form-select bg-gray-700 text-white">
                        <option value="">Tümü</option>
                        <option value="active">Aktif</option>
                        <option value="pending">Beklemede</option>
                        <option value="expired">Süresi Dolmuş</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Links Table -->
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-700">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-900">
                            <input type="checkbox" class="form-checkbox" onclick="toggleAll(this)">
                        </th>
                        <th class="px-6 py-3 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase">Domain</th>
                        <th class="px-6 py-3 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase">DA/PA</th>
                        <th class="px-6 py-3 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase">Tip</th>
                        <th class="px-6 py-3 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase">Fiyat</th>
                        <th class="px-6 py-3 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase">Durum</th>
                        <th class="px-6 py-3 bg-gray-900 text-left text-xs font-medium text-gray-400 uppercase">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach ($links as $link): ?>
                    <tr class="hover:bg-gray-750">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="form-checkbox" value="<?php echo $link['id']; ?>">
                        </td>
                        <td class="px-6 py-4">
                            <a href="<?php echo htmlspecialchars($link['domain']); ?>" target="_blank" 
                               class="text-blue-400 hover:text-blue-300">
                                <?php echo htmlspecialchars($link['domain']); ?>
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-green-400"><?php echo $link['da']; ?></span> /
                            <span class="text-blue-400"><?php echo $link['pa']; ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $link['type'] === 'PHP' ? 'bg-purple-900 text-purple-300' : 'bg-green-900 text-green-300'; ?>">
                                <?php echo $link['type']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo number_format($link['price'], 2); ?> ₺
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $status_colors = [
                                'active' => 'bg-green-900 text-green-300',
                                'pending' => 'bg-yellow-900 text-yellow-300',
                                'expired' => 'bg-red-900 text-red-300'
                            ];
                            $color = $status_colors[$link['status']] ?? 'bg-gray-900 text-gray-300';
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $color; ?>">
                                <?php echo ucfirst($link['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button onclick="editLink(<?php echo $link['id']; ?>)" 
                                        class="text-blue-400 hover:text-blue-300">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="deleteLink(<?php echo $link['id']; ?>)"
                                        class="text-red-400 hover:text-red-300">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Link Market functionality
    function bulkAdd() {
        // Show bulk add modal
    }

    function bulkEdit() {
        // Show bulk edit modal
    }

    function editLink(id) {
        // Show edit modal
    }

    function deleteLink(id) {
        if (confirm('Bu linki silmek istediğinizden emin misiniz?')) {
            // Delete link
        }
    }

    function toggleAll(checkbox) {
        document.querySelectorAll('tbody input[type="checkbox"]')
            .forEach(cb => cb.checked = checkbox.checked);
    }

    // Initialize filters
    document.getElementById('filters').addEventListener('change', function() {
        this.submit();
    });
    </script>
</body>
</html>