<?php
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

if (!check_auth()) {
    header('Location: ../../login.php');
    exit;
}

$security = new Security();
$user_id = $_SESSION['user_id'];
$credits = get_user_credits($user_id);

// Get credit packages
$packages = [
    ['id' => 1, 'credits' => 100, 'price' => 50, 'name' => 'Başlangıç Paketi'],
    ['id' => 2, 'credits' => 250, 'price' => 100, 'name' => 'Profesyonel Paket'],
    ['id' => 3, 'credits' => 500, 'price' => 175, 'name' => 'İşletme Paketi'],
    ['id' => 4, 'credits' => 1000, 'price' => 300, 'name' => 'Kurumsal Paket']
];

// Get payment history
$stmt = $db->prepare("
    SELECT * FROM payments 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$current_page = 'credits';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Krediler - Backlink System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../includes/customer-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Credit Status -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold">Mevcut Kredileriniz</h2>
                    <p class="text-gray-600 mt-1">Her backlink için 1 kredi kullanılır</p>
                </div>
                <div class="text-3xl font-bold text-blue-600">
                    <?php echo $credits; ?> <span class="text-sm text-gray-500">kredi</span>
                </div>
            </div>
        </div>

        <!-- Credit Packages -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php foreach ($packages as $package): ?>
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="text-center">
                    <h3 class="text-lg font-semibold mb-2"><?php echo $package['name']; ?></h3>
                    <div class="text-3xl font-bold text-blue-600 mb-2">
                        <?php echo $package['credits']; ?>
                        <span class="text-sm text-gray-500">kredi</span>
                    </div>
                    <div class="text-2xl font-semibold mb-4">
                        <?php echo $package['price']; ?> TL
                    </div>
                    <button onclick="buyCredits(<?php echo $package['id']; ?>)" 
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-200">
                        Satın Al
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Payment History -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Ödeme Geçmişi</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Tutar</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Kredi</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">İşlem No</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo number_format($payment['amount'], 2); ?> TL
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $payment['credits']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'completed' => 'green',
                                    'pending' => 'yellow',
                                    'failed' => 'red'
                                ];
                                $color = $status_colors[$payment['status']] ?? 'gray';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-800">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $payment['transaction_id']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Henüz ödeme geçmişi yok
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="../js/credits.js"></script>
</body>
</html>