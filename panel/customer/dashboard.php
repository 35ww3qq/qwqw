<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

if (!check_auth()) {
    header('Location: ../../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$current_page = 'dashboard';

// Initialize stats array
$stats = [
    'total_sites' => 0,
    'active_backlinks' => 0,
    'credits' => 0,
    'verified_sites' => 0
];

try {
    // Get total sites
    $stmt = $db->prepare("SELECT COUNT(*) FROM sites WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['total_sites'] = $stmt->get_result()->fetch_row()[0];

    // Get active backlinks
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM backlinks b 
        JOIN sites s ON b.site_id = s.id 
        WHERE s.user_id = ? AND b.status = 'active'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['active_backlinks'] = $stmt->get_result()->fetch_row()[0];

    // Get user credits
    $stmt = $db->prepare("SELECT credits FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['credits'] = $stmt->get_result()->fetch_row()[0];

    // Get verified sites
    $stmt = $db->prepare("SELECT COUNT(*) FROM sites WHERE user_id = ? AND is_verified = 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['verified_sites'] = $stmt->get_result()->fetch_row()[0];

    // Get recent activity
    $stmt = $db->prepare("
        SELECT b.*, s.domain 
        FROM backlinks b 
        JOIN sites s ON b.site_id = s.id 
        WHERE s.user_id = ? 
        ORDER BY b.created_at DESC 
        LIMIT 5
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "Bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Hacklink Market</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/customer.css" rel="stylesheet">
</head>
<body class="bg-gray-900">
    <?php include '../includes/customer-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($error)): ?>
            <div class="bg-red-900 text-red-100 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Sites -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-900 text-blue-300">
                        <i class="fas fa-globe text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Toplam Site</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo $stats['total_sites']; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Active Backlinks -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-900 text-green-300">
                        <i class="fas fa-link text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Aktif Backlink</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo $stats['active_backlinks']; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Credits -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-900 text-purple-300">
                        <i class="fas fa-coins text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Kalan Kredi</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo $stats['credits']; ?></h3>
                    </div>
                </div>
            </div>

            <!-- Verified Sites -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-900 text-yellow-300">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Doğrulanmış Site</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo $stats['verified_sites']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Son Aktiviteler</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Site</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Hedef URL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($recent_activity as $activity): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                    <?php echo htmlspecialchars($activity['domain']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="<?php echo htmlspecialchars($activity['target_url']); ?>" 
                                       class="text-blue-400 hover:text-blue-300" 
                                       target="_blank">
                                        <?php echo htmlspecialchars($activity['target_url']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_colors = [
                                        'active' => 'bg-green-900 text-green-300',
                                        'pending' => 'bg-yellow-900 text-yellow-300',
                                        'removed' => 'bg-red-900 text-red-300'
                                    ];
                                    $color = $status_colors[$activity['status']] ?? 'bg-gray-900 text-gray-300';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $color; ?>">
                                        <?php echo ucfirst($activity['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                    <?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_activity)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-400">
                                    Henüz aktivite bulunmuyor
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../js/customer-dashboard.js"></script>
</body>
</html>