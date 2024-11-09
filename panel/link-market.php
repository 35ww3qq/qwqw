<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

if (!check_auth()) {
    header('Location: /login');
    exit;
}

$security = new Security();
$current_page = 'link-market';

// Get links with filters and pagination
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$filters = [
    'status' => $_GET['status'] ?? 'all',
    'type' => $_GET['type'] ?? 'all',
    'da_min' => $_GET['da_min'] ?? '',
    'da_max' => $_GET['da_max'] ?? '',
    'pa_min' => $_GET['pa_min'] ?? '',
    'pa_max' => $_GET['pa_max'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'search' => $_GET['search'] ?? '',
    'sort' => $_GET['sort'] ?? 'created_at',
    'order' => $_GET['order'] ?? 'desc'
];

$where = [];
$params = [];

if ($filters['status'] !== 'all') {
    $where[] = "l.status = ?";
    $params[] = $filters['status'];
}

if ($filters['type'] !== 'all') {
    $where[] = "l.type = ?";
    $params[] = $filters['type'];
}

if ($filters['da_min'] !== '') {
    $where[] = "s.da >= ?";
    $params[] = $filters['da_min'];
}

if ($filters['da_max'] !== '') {
    $where[] = "s.da <= ?";
    $params[] = $filters['da_max'];
}

if ($filters['pa_min'] !== '') {
    $where[] = "s.pa >= ?";
    $params[] = $filters['pa_min'];
}

if ($filters['pa_max'] !== '') {
    $where[] = "s.pa <= ?";
    $params[] = $filters['pa_max'];
}

if ($filters['price_min'] !== '') {
    $where[] = "l.price >= ?";
    $params[] = $filters['price_min'];
}

if ($filters['price_max'] !== '') {
    $where[] = "l.price <= ?";
    $params[] = $filters['price_max'];
}

if ($filters['search']) {
    $where[] = "(s.domain LIKE ? OR l.anchor_text LIKE ?)";
    $params[] = "%{$filters['search']}%";
    $params[] = "%{$filters['search']}%";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Get total count for pagination
$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM links l 
    JOIN sites s ON l.site_id = s.id 
    $where_clause
");

if ($params) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}

$stmt->execute();
$total_links = $stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_links / $limit);

// Get links
$sort_whitelist = ['created_at', 'price', 'da', 'pa'];
$sort = in_array($filters['sort'], $sort_whitelist) ? $filters['sort'] : 'created_at';
$order = $filters['order'] === 'asc' ? 'ASC' : 'DESC';

$query = "
    SELECT l.*, s.domain, s.da, s.pa,
           (SELECT COUNT(*) FROM backlinks WHERE site_id = s.id AND status = 'active') as active_backlinks,
           (SELECT AVG(loading_time) FROM backlink_reports r JOIN backlinks b ON r.backlink_id = b.id WHERE b.site_id = s.id) as avg_loading_time
    FROM links l 
    JOIN sites s ON l.site_id = s.id 
    $where_clause
    ORDER BY $sort $order
    LIMIT $limit OFFSET $offset
";

$stmt = $db->prepare($query);
if ($params) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$links = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's favorites
$user_id = $_SESSION['user_id'];
$favorites = $db->query("
    SELECT link_id 
    FROM link_favorites 
    WHERE user_id = $user_id
")->fetch_all(MYSQLI_COLUMN);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Market</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Bulk Actions -->
        <div class="flex space-x-4 mb-6">
            <button class="btn-primary" onclick="bulkAdd()">
                <i class="fas fa-plus"></i> Toplu Ekle
            </button>
            <button class="btn-secondary" onclick="bulkExtend()">
                <i class="fas fa-clock"></i> Toplu Uzat
            </button>
            <button class="btn-success" onclick="bulkRenew()">
                <i class="fas fa-sync"></i> Toplu Yenileme
            </button>
            <button class="btn-warning" onclick="bulkRefund()">
                <i class="fas fa-undo"></i> Toplu İade
            </button>
            <button class="btn-info" onclick="bulkEdit()">
                <i class="fas fa-edit"></i> Toplu Düzenle
            </button>
        </div>

        <!-- Filters -->
        <div class="bg-gray-800 rounded-lg p-4 mb-6">
            <form id="filters-form" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-400">Durum</label>
                    <select name="status" class="form-select bg-gray-700 mt-1" onchange="this.form.submit()">
                        <option value="all">Tümü</option>
                        <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                        <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Beklemede</option>
                        <option value="expired" <?php echo $filters['status'] === 'expired' ? 'selected' : ''; ?>>Süresi Dolmuş</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">Tür</label>
                    <select name="type" class="form-select bg-gray-700 mt-1" onchange="this.form.submit()">
                        <option value="all">Tümü</option>
                        <option value="PHP" <?php echo $filters['type'] === 'PHP' ? 'selected' : ''; ?>>PHP</option>
                        <option value="JS" <?php echo $filters['type'] === 'JS' ? 'selected' : ''; ?>>JS</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">DA Aralığı</label>
                    <div class="flex space-x-2">
                        <input type="number" name="da_min" placeholder="Min" value="<?php echo $filters['da_min']; ?>"
                               class="form-input bg-gray-700 mt-1 w-1/2">
                        <input type="number" name="da_max" placeholder="Max" value="<?php echo $filters['da_max']; ?>"
                               class="form-input bg-gray-700 mt-1 w-1/2">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">PA Aralığı</label>
                    <div class="flex space-x-2">
                        <input type="number" name="pa_min" placeholder="Min" value="<?php echo $filters['pa_min']; ?>"
                               class="form-input bg-gray-700 mt-1 w-1/2">
                        <input type="number" name="pa_max" placeholder="Max" value="<?php echo $filters['pa_max']; ?>"
                               class="form-input bg-gray-700 mt-1 w-1/2">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">Fiyat Aralığı</label>
                    <div class="flex space-x-2">
                        <input type="number" name="price_min" placeholder="Min" value="<?php echo $filters['price_min']; ?>"
                               class="form-input bg-gray-700 mt-1 w-1/2">
                        <input type="number" name="price_max" placeholder="Max" value="<?php echo $filters['price_max']; ?>"
                               class="form-input bg-gray-700 mt-1 w-1/2">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">Sıralama</label>
                    <select name="sort" class="form-select bg-gray-700 mt-1" onchange="this.form.submit()">
                        <option value="created_at" <?php echo $filters['sort'] === 'created_at' ? 'selected' : ''; ?>>Eklenme Tarihi</option>
                        <option value="da" <?php echo $filters['sort'] === 'da' ? 'selected' : ''; ?>>DA</option>
                        <option value="pa" <?php echo $filters['sort'] === 'pa' ? 'selected' : ''; ?>>PA</option>
                        <option value="price" <?php echo $filters['sort'] === 'price' ? 'selected' : ''; ?>>Fiyat</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-400">Arama</label>
                    <input type="text" name="search" placeholder="Domain veya anchor text ara..." 
                           value="<?php echo htmlspecialchars($filters['search']); ?>"
                           class="form-input bg-gray-700 mt-1">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="btn-primary w-full">
                        <i class="fas fa-search mr-2"></i> Filtrele
                    </button>
                </div>
            </form>
        </div>

        <!-- Links Table -->
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-700">
                <thead>
                    <tr class="bg-gray-900">
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" class="form-checkbox" onclick="toggleAll(this)">
                        </th>
                        <th class="px-6 py-3 text-left">Domain</th>
                        <th class="px-6 py-3 text-left">DA/PA</th>
                        <th class="px-6 py-3 text-left">Tür</th>
                        <th class="px-6 py-3 text-left">Fiyat</th>
                        <th class="px-6 py-3 text-left">Durum</th>
                        <th class="px-6 py-3 text-left">Kalite Skoru</th>
                        <th class="px-6 py-3 text-left">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    <?php foreach ($links as $link): ?>
                    <tr class="hover:bg-gray-750">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="form-checkbox" value="<?php echo $link['id']; ?>">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <a href="<?php echo htmlspecialchars($link['domain']); ?>" target="_blank" 
                                   class="text-blue-400 hover:text-blue-300">
                                    <?php echo htmlspecialchars($link['domain']); ?>
                                </a>
                                <?php if (in_array($link['id'], $favorites)): ?>
                                <i class="fas fa-star text-yellow-400 ml-2"></i>
                                <?php endif; ?>
                            </div>
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
                            <span class="px-2 py-1 text-xs rounded-full <?php 
                                echo match($link['status']) {
                                    'active' => 'bg-green-900 text-green-300',
                                    'pending' => 'bg-yellow-900 text-yellow-300',
                                    'expired' => 'bg-red-900 text-red-300',
                                    default => 'bg-gray-900 text-gray-300'
                                };
                            ?>">
                                <?php echo ucfirst($link['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $quality_score = calculateQualityScore($link);
                            $score_color = match(true) {
                                $quality_score >= 80 => 'text-green-400',
                                $quality_score >= 60 => 'text-yellow-400',
                                default => 'text-red-400'
                            };
                            ?>
                            <span class="<?php echo $score_color; ?>">
                                <?php echo $quality_score; ?>%
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button onclick="viewDetails(<?php echo $link['id']; ?>)" 
                                        class="text-blue-400 hover:text-blue-300">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="toggleFavorite(<?php echo $link['id']; ?>)"
                                        class="text-yellow-400 hover:text-yellow-300">
                                    <i class="fas fa-star"></i>
                                </button>
                                <button onclick="purchaseLink(<?php echo $link['id']; ?>)"
                                        class="text-green-400 hover:text-green-300">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-4 flex justify-between items-center">
            <div class="text-sm text-gray-400">
                Toplam <?php echo $total_links; ?> link
            </div>
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&<?php echo http_build_query($filters); ?>" 
                       class="btn-secondary">
                        <i class="fas fa-chevron-left mr-2"></i> Önceki
                    </a>
                <?php endif; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&<?php echo http_build_query($filters); ?>" 
                       class="btn-secondary">
                        Sonraki <i class="fas fa-chevron-right ml-2"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="/assets/js/link-market.js"></script>
</body>
</html>
<?php
function calculateQualityScore($link) {
    $score = 0;
    
    // DA score (max 30 points)
    $score += min(30, $link['da'] * 3);
    
    // PA score (max 20 points)
    $score += min(20, $link['pa'] * 2);
    
    // Active backlinks ratio (max 20 points)
    $total_backlinks = $link['active_backlinks'] ?? 0;
    if ($total_backlinks > 0) {
        $score += 20;
    }
    
    // Loading time score (max 15 points)
    $loading_time = $link['avg_loading_time'] ?? 0;
    if ($loading_time > 0) {
        $score += max(0, 15 - ($loading_time * 3));
    }
    
    // Domain age score (max 15 points)
    $domain_age = calculateDomainAge($link['domain']);
    $score += min(15, $domain_age);
    
    return min(100, $score);
}

function calculateDomainAge($domain) {
    $domain_info = @dns_get_record($domain, DNS_ALL);
    if (!$domain_info) {
        return 0;
    }
    
    $whois_data = @shell_exec("whois " . escapeshellarg($domain));
    if (!$whois_data) {
        return 5; // Default score if whois fails
    }
    
    // Try to find creation date
    if (preg_match('/Creation Date: (.+)/', $whois_data, $matches)) {
        $creation_date = strtotime($matches[1]);
        $age_years = (time() - $creation_date) / (365 * 24 * 60 * 60);
        return min(15, $age_years);
    }
    
    return 5; // Default score
}
?>