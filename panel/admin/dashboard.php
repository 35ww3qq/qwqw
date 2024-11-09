<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

$current_page = 'dashboard';

// Initialize stats array with default values
$stats = [
    'pending_sites' => 0,
    'total_revenue' => 0,
    'total_users' => 0,
    'active_backlinks' => 0,
    'verified_sites' => 0,
    'total_credits' => 0
];

try {
    // Get pending sites count
    $result = $db->query("SELECT COUNT(*) FROM sites WHERE is_verified = 0");
    if ($result && $row = $result->fetch_row()) {
        $stats['pending_sites'] = $row[0];
    }

    // Get total revenue
    $result = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'");
    if ($result && $row = $result->fetch_row()) {
        $stats['total_revenue'] = $row[0];
    }

    // Get total users (excluding admins)
    $result = $db->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    if ($result && $row = $result->fetch_row()) {
        $stats['total_users'] = $row[0];
    }

    // Get active backlinks count
    $result = $db->query("SELECT COUNT(*) FROM backlinks WHERE status = 'active'");
    if ($result && $row = $result->fetch_row()) {
        $stats['active_backlinks'] = $row[0];
    }

    // Get verified sites count
    $result = $db->query("SELECT COUNT(*) FROM sites WHERE is_verified = 1");
    if ($result && $row = $result->fetch_row()) {
        $stats['verified_sites'] = $row[0];
    }

    // Get total credits
    $result = $db->query("SELECT COALESCE(SUM(credits), 0) FROM users");
    if ($result && $row = $result->fetch_row()) {
        $stats['total_credits'] = $row[0];
    }

    // Get last backlink check
    $result = $db->query("SELECT MAX(last_checked) FROM backlinks");
    $last_check = ($result && $row = $result->fetch_row()) ? $row[0] : null;

    // Get recent activities
    $activities = $db->query("
        SELECT al.*, u.username 
        FROM activity_log al
        JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC 
        LIMIT 10
    ");
    $activities = $activities ? $activities->fetch_all(MYSQLI_ASSOC) : [];

} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $error = "Bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hacklink Market</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-900">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($error)): ?>
            <div class="bg-red-900 text-red-100 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Top Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Pending Sites -->
            <div class="bg-orange-500 text-white rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-2">Bekleyen Siteler</h3>
                <p class="text-4xl font-bold"><?php echo number_format($stats['pending_sites']); ?></p>
            </div>

            <!-- Total Revenue -->
            <div class="bg-emerald-500 text-white rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-2">Toplam Gelir</h3>
                <p class="text-4xl font-bold"><?php echo number_format($stats['total_revenue'], 2); ?> TL</p>
            </div>

            <!-- Backlink Check -->
            <div class="bg-blue-500 text-white rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-2">Backlink Kontrolü</h3>
                <p class="text-sm">Son kontrol: <?php echo $last_check ? date('d.m.Y H:i', strtotime($last_check)) : 'Henüz kontrol yapılmadı'; ?></p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-900 text-blue-300">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Toplam Kullanıcı</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo number_format($stats['total_users']); ?></h3>
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
                        <h3 class="text-2xl font-bold text-white"><?php echo number_format($stats['active_backlinks']); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Verified Sites -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-900 text-purple-300">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Doğrulanmış Site</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo number_format($stats['verified_sites']); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Total Credits -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-900 text-yellow-300">
                        <i class="fas fa-coins text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-400">Toplam Kredi</p>
                        <h3 class="text-2xl font-bold text-white"><?php echo number_format($stats['total_credits']); ?></h3>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Kullanıcı</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">İşlem</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Detay</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tarih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                <?php echo htmlspecialchars($activity['username']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                <?php echo htmlspecialchars($activity['action']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                <?php echo htmlspecialchars($activity['details']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-300">
                                <?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($activities)): ?>
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
</body>
</html>