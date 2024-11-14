<nav class="bg-white shadow-sm">
    <div class="container mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <img class="h-8 w-auto" src="<?php echo asset_url('logo.svg'); ?>" alt="Logo">
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="<?php echo panel_url('admin/dashboard.php'); ?>" 
                       class="<?php echo $current_page === 'dashboard' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Dashboard
                    </a>
                    <a href="<?php echo panel_url('admin/users.php'); ?>"
                       class="<?php echo $current_page === 'users' ? 'border-blue-500' : 'border-transparent'; ?> text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Kullanıcılar
                    </a>
                    <!-- ... diğer menü öğeleri ... -->
                </div>
            </div>
            <div class="flex items-center">
                <a href="<?php echo site_url('logout.php'); ?>" class="text-gray-700 hover:text-gray-900">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
    </div>
</nav> 