<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';

if (!check_auth()) {
    header('Location: /login');
    exit;
}

$current_page = 'packages';

$packages = [
    ['id' => 1, 'name' => 'Platinum', 'price' => 80, 'credits' => 100],
    ['id' => 2, 'name' => 'Legend', 'price' => 100, 'credits' => 150],
    ['id' => 3, 'name' => 'Major', 'price' => 120, 'credits' => 200],
    ['id' => 4, 'name' => 'Master', 'price' => 140, 'credits' => 250],
    ['id' => 5, 'name' => 'Diamond', 'price' => 160, 'credits' => 300],
    ['id' => 6, 'name' => 'Professional', 'price' => 180, 'credits' => 350],
    ['id' => 7, 'name' => 'Ultra-1', 'price' => 240, 'credits' => 500],
    ['id' => 8, 'name' => 'Ultra-2', 'price' => 280, 'credits' => 600],
    ['id' => 9, 'name' => 'Ultra-3', 'price' => 320, 'credits' => 700],
    ['id' => 10, 'name' => 'Ultra-4', 'price' => 400, 'credits' => 1000],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paketler</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="alert alert-info mb-4">
            <i class="ti ti-info-circle"></i>
            <span>Tüm paketlerimizi görebilmek için lütfen aşağıdaki "Platinum | 80 USDT" butonuna tıklayın.</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($packages as $package): ?>
            <div class="package-card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold"><?php echo $package['name']; ?></h3>
                    <span class="price"><?php echo $package['price']; ?> USDT</span>
                </div>

                <ul class="features">
                    <li>
                        <i class="ti ti-check text-success"></i>
                        <span><?php echo $package['credits']; ?> Kredi</span>
                    </li>
                    <li>
                        <i class="ti ti-check text-success"></i>
                        <span>Panel Erişimi</span>
                    </li>
                    <li>
                        <i class="ti ti-check text-success"></i>
                        <span>API Erişim Hakkı</span>
                    </li>
                    <li>
                        <i class="ti ti-check text-success"></i>
                        <span>24 Saat Destek</span>
                    </li>
                </ul>

                <div class="mt-6">
                    <button class="btn btn-primary w-full" onclick="buyPackage(<?php echo $package['id']; ?>)">
                        USDT ile Ödeme Yap
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="/assets/js/packages.js"></script>
</body>
</html>