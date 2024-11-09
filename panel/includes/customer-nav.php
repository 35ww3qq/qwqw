<?php
$current_page = $current_page ?? '';
?>
<nav class="bg-white shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <img class="h-8 w-auto" src="../assets/logo.svg" alt="Logo">
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="dashboard.php" class="<?php echo $current_page === 'dashboard' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="backlinks.php" class="<?php echo $current_page === 'backlinks' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Backlinkler
                    </a>
                    <a href="sites.php" class="<?php echo $current_page === 'sites' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Siteler
                    </a>
                    <a href="credits.php" class="<?php echo $current_page === 'credits' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Krediler
                    </a>
                    <a href="profile.php" class="<?php echo $current_page === 'profile' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Profil
                    </a>
                </div>
            </div>
            <div class="flex items-center">
                <div class="ml-3 relative">
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">Kredi: <?php echo get_user_credits($_SESSION['user_id']); ?></span>
                        <a href="../logout.php" class="text-gray-700 hover:text-gray-900">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>