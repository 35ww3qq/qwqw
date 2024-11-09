<?php
require_once '../../includes/init.php';
require_once '../../includes/auth.php';

if (!check_auth() || !is_admin()) {
    header('Location: ../../login.php');
    exit;
}

$current_page = 'invoices';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faturalar - Hacklink Market</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-900">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Faturalar</h2>
            <!-- Invoices content will go here -->
            <div class="text-gray-300">
                Fatura listesi yakında eklenecek...
            </div>
        </div>
    </div>
</body>
</html>