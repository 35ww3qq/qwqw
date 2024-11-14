<?php
require_once '../../includes/init.php';

if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

// Sayfalama
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Toplam kullanıcı sayısı
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_pages = ceil($total_users / $limit);

// Kullanıcıları al
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
    <title>Kullanıcılar - Admin Panel</title>
    <!-- Global URL değişkenleri -->
    <script>
        window.SITE_URL = '<?php echo SITE_URL; ?>';
        window.BASE_PATH = '<?php echo BASE_PATH; ?>';
        window.API_BASE_URL = '<?php echo api_url(''); ?>';
    </script>
    <link href="<?php echo asset_url('css/tailwind.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/fontawesome.min.css'); ?>" rel="stylesheet">
    <script src="<?php echo asset_url('js/app.js'); ?>"></script>
</head>
<body class="bg-gray-100">
    <?php require_once '../../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Kullanıcı Yönetimi</h1>
                <p class="mt-1 text-sm text-gray-500">Toplam <?php echo $total_users; ?> kullanıcı</p>
            </div>
            <button onclick="app.users.add()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-user-plus mr-2"></i> Yeni Kullanıcı
            </button>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kullanıcı</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Backlink</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kredi</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kayıt Tarihi</th>
                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">İşlemler</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['is_admin']): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        Admin
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Müşteri
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $user['site_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $user['backlink_count']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $user['credits']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="window.editUser(<?php echo $user['id']; ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                        title="Düzenle">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="window.deleteUser(<?php echo $user['id']; ?>)" 
                                            class="text-red-600 hover:text-red-900"
                                            title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Sayfalama -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-4 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="<?php echo $page == $i ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
        <?php endif; ?>
    </div>

    <!-- Düzenleme Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Kullanıcı Düzenle</h3>
                <button onclick="document.getElementById('editModal').style.display='none'" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editForm" onsubmit="return false;">
                <input type="hidden" id="editUserId">
                
                <div class="mb-4">
                    <label for="editUsername" class="block text-sm font-medium text-gray-700">Kullanıcı Adı</label>
                    <input type="text" id="editUsername" name="username" required autocomplete="username"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="editEmail" class="block text-sm font-medium text-gray-700">E-posta</label>
                    <input type="email" id="editEmail" name="email" required autocomplete="email"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="editPassword" class="block text-sm font-medium text-gray-700">Şifre (Boş bırakılırsa değişmez)</label>
                    <input type="password" id="editPassword" name="password" autocomplete="new-password"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="editCredits" class="block text-sm font-medium text-gray-700">Krediler</label>
                    <input type="number" id="editCredits" name="credits" required autocomplete="off"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="editIsAdmin" name="is_admin" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Admin Yetkisi</span>
                    </label>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="document.getElementById('editModal').style.display='none'"
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mr-2">
                        İptal
                    </button>
                    <button type="submit" onclick="app.users.submitEdit()"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Global fonksiyonlar
    function editUser(userId) {
        if (typeof app !== 'undefined' && app.users) {
            app.users.edit(userId);
        } else {
            console.error('app.users is not defined');
        }
    }

    function deleteUser(userId) {
        if (typeof app !== 'undefined' && app.users) {
            app.users.delete(userId);
        } else {
            console.error('app.users is not defined');
        }
    }

    // app.js yüklendikten sonra çalışacak kod
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof app === 'undefined') {
            console.error('app.js is not loaded correctly');
        }
    });
    </script>

    <script src="../../assets/js/app.js"></script>
</body>
</html>