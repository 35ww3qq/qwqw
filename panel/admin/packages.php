<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

$current_page = 'packages';

// Get all packages
$packages = $db->query("
    SELECT p.*, COUNT(DISTINCT o.id) as total_sales, SUM(o.amount) as total_revenue
    FROM packages p
    LEFT JOIN orders o ON p.id = o.package_id
    GROUP BY p.id
    ORDER BY p.price ASC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paketler - Hacklink Market</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-900">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Actions -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Paketler</h2>
            <button onclick="addPackage()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i> Yeni Paket Ekle
            </button>
        </div>

        <!-- Packages Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($packages as $package): ?>
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($package['name']); ?></h3>
                        <p class="text-gray-400"><?php echo htmlspecialchars($package['description']); ?></p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-white"><?php echo number_format($package['price'], 2); ?> ₺</div>
                        <div class="text-sm text-gray-400"><?php echo number_format($package['credits']); ?> Kredi</div>
                    </div>
                </div>

                <div class="space-y-2 mb-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Toplam Satış</span>
                        <span class="text-white"><?php echo number_format($package['total_sales']); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-400">Toplam Gelir</span>
                        <span class="text-white"><?php echo number_format($package['total_revenue'], 2); ?> ₺</span>
                    </div>
                </div>

                <div class="flex space-x-2">
                    <button onclick="editPackage(<?php echo $package['id']; ?>)" 
                            class="flex-1 btn-secondary">
                        <i class="fas fa-edit mr-2"></i> Düzenle
                    </button>
                    <button onclick="deletePackage(<?php echo $package['id']; ?>)" 
                            class="flex-1 btn-danger">
                        <i class="fas fa-trash mr-2"></i> Sil
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function addPackage() {
        // Show add package modal
    }

    function editPackage(id) {
        // Show edit package modal
    }

    function deletePackage(id) {
        if (confirm('Bu paketi silmek istediğinizden emin misiniz?')) {
            // Delete package
        }
    }
    </script>
</body>
</html>