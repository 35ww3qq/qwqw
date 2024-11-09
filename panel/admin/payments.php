<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';
require_once '../../includes/security.php';

if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

$security = new Security();
$current_page = 'payments';

// Handle payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $security->validateCSRF()) {
    $action = $_POST['action'] ?? '';
    $payment_id = (int)$_POST['payment_id'] ?? 0;
    
    switch ($action) {
        case 'approve':
            $db->begin_transaction();
            try {
                // Get payment details
                $stmt = $db->prepare("
                    SELECT user_id, credits, status 
                    FROM payments 
                    WHERE id = ? AND status = 'pending'
                ");
                $stmt->bind_param("i", $payment_id);
                $stmt->execute();
                $payment = $stmt->get_result()->fetch_assoc();
                
                if ($payment) {
                    // Update payment status
                    $db->query("UPDATE payments SET status = 'completed' WHERE id = $payment_id");
                    
                    // Add credits to user
                    $db->query("
                        UPDATE users 
                        SET credits = credits + {$payment['credits']} 
                        WHERE id = {$payment['user_id']}
                    ");
                    
                    // Log activity
                    log_activity($payment['user_id'], 'payment_approved', "Payment ID: $payment_id");
                    
                    $db->commit();
                }
            } catch (Exception $e) {
                $db->rollback();
                error_log("Error approving payment: " . $e->getMessage());
            }
            break;
            
        case 'reject':
            $db->query("UPDATE payments SET status = 'failed' WHERE id = $payment_id");
            break;
    }
}

// Get payments with pagination
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$total_payments = $db->query("SELECT COUNT(*) FROM payments")->fetch_row()[0];
$total_pages = ceil($total_payments / $limit);

$payments = $db->query("
    SELECT p.*, u.username, u.email 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT $offset, $limit
")->fetch_all(MYSQLI_ASSOC);

// Get payment statistics
$stats = [
    'total_revenue' => $db->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'")->fetch_row()[0] ?? 0,
    'pending_amount' => $db->query("SELECT SUM(amount) FROM payments WHERE status = 'pending'")->fetch_row()[0] ?? 0,
    'total_transactions' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'completed'")->fetch_row()[0],
    'pending_transactions' => $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetch_row()[0]
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ödeme Yönetimi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php require_once '../../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Ödeme Yönetimi</h1>
        </div>

        <!-- Payment Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-500 bg-opacity-10">
                        <i class="fas fa-money-bill-wave text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Toplam Gelir</p>
                        <h3 class="text-2xl font-bold"><?php echo number_format($stats['total_revenue'], 2); ?> TL</h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-500 bg-opacity-10">
                        <i class="fas fa-clock text-yellow-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Bekleyen Ödemeler</p>
                        <h3 class="text-2xl font-bold"><?php echo number_format($stats['pending_amount'], 2); ?> TL</h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-500 bg-opacity-10">
                        <i class="fas fa-exchange-alt text-blue-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Toplam İşlem</p>
                        <h3 class="text-2xl font-bold"><?php echo $stats['total_transactions']; ?></h3>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-500 bg-opacity-10">
                        <i class="fas fa-hourglass-half text-purple-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-500">Bekleyen İşlem</p>
                        <h3 class="text-2xl font-bold"><?php echo $stats['pending_transactions']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Kullanıcı</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Tutar</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Kredi</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Ödeme Yöntemi</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($payment['username']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($payment['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo number_format($payment['amount'], 2); ?> TL
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $payment['credits']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo ucfirst($payment['payment_method']); ?>
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
                                <?php echo date('d.m.Y H:i', strtotime($payment['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($payment['status'] === 'pending'): ?>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                        <button type="submit" class="text-green-600 hover:text-green-900 mr-3">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="" class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 border-t border-gray-200">
                <div class="flex justify-between">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="text-blue-600 hover:text-blue-900">← Önceki</a>
                    <?php else: ?>
                        <span class="text-gray-400">← Önceki</span>
                    <?php endif; ?>
                    
                    <span class="text-gray-600">
                        Sayfa <?php echo $page; ?>/<?php echo $total_pages; ?>
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="text-blue-600 hover:text-blue-900">Sonraki →</a>
                    <?php else: ?>
                        <span class="text-gray-400">Sonraki →</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>