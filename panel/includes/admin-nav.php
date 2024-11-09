<?php
$current_page = $current_page ?? '';
?>
<nav class="bg-gray-800 border-b border-gray-700">
    <div class="container mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <img src="/assets/logo.svg" alt="Logo" class="h-8 w-auto">
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="dashboard.php" 
                       class="<?php echo $current_page === 'dashboard' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Dashboard
                    </a>
                    <a href="link-market.php" 
                       class="<?php echo $current_page === 'link-market' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        <i class="fas fa-store mr-2"></i>
                        Link Market
                    </a>
                    <a href="links.php" 
                       class="<?php echo $current_page === 'links' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        <i class="fas fa-link mr-2"></i>
                        Linkler
                    </a>
                    <a href="premium-indexer.php" 
                       class="<?php echo $current_page === 'premium-indexer' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        <i class="fas fa-rocket mr-2"></i>
                        Premium Indexer
                    </a>
                    <a href="packages.php" 
                       class="<?php echo $current_page === 'packages' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        <i class="fas fa-box mr-2"></i>
                        Paketler
                    </a>
                    <a href="invoices.php" 
                       class="<?php echo $current_page === 'invoices' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-300 hover:text-white inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        <i class="fas fa-file-invoice mr-2"></i>
                        Faturalar
                    </a>
                </div>
            </div>
            <div class="flex items-center">
                <span class="text-gray-300 mr-4">
                    <i class="fas fa-coins mr-2"></i>
                    <?php echo number_format(get_user_credits($_SESSION['user_id'])); ?> Kredi
                </span>
                <div class="ml-3 relative">
                    <div class="flex items-center">
                        <span class="text-gray-300 mr-2"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="/logout.php" class="text-gray-300 hover:text-white">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>