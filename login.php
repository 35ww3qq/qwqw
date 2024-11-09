<?php
require_once 'includes/init.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

// Redirect if already logged in
if (check_auth()) {
    header('Location: ' . (is_admin() ? 'panel/admin/dashboard.php' : 'panel/customer/dashboard.php'));
    exit;
}

$security = new Security();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurun';
    } else {
        if (login($username, $password)) {
            header('Location: ' . (is_admin() ? 'panel/admin/dashboard.php' : 'panel/customer/dashboard.php'));
            exit;
        } else {
            $error = 'Geçersiz kullanıcı adı veya şifre';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - Hacklink Market</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white">Hacklink Market</h1>
            <p class="mt-2 text-gray-400">Güvenli backlink yönetim sistemi</p>
        </div>

        <div class="bg-gray-800 rounded-lg shadow-xl p-8">
            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo $security->getCSRFToken(); ?>">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-300">Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" required
                           class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300">Şifre</label>
                    <input type="password" id="password" name="password" required
                           class="mt-1 block w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-600 rounded bg-gray-700">
                        <label for="remember" class="ml-2 block text-sm text-gray-300">
                            Beni Hatırla
                        </label>
                    </div>

                    <a href="forgot-password.php" class="text-sm font-medium text-blue-400 hover:text-blue-300">
                        Şifremi Unuttum
                    </a>
                </div>

                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-sign-in-alt mr-2"></i> Giriş Yap
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-400">
                    Hesabınız yok mu?
                    <a href="register.php" class="font-medium text-blue-400 hover:text-blue-300">
                        Kayıt Ol
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>