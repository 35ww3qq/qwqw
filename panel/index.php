<?php
require_once '../includes/auth.php';

if (!check_auth()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = is_admin();

// Get user's sites and backlinks
$sites_query = "SELECT * FROM sites WHERE user_id = ?";
$stmt = $db->prepare($sites_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backlink Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold">Backlink Management Panel</h1>
            <div class="flex items-center space-x-4">
                <span class="text-gray-600">Credits: <?php echo get_user_credits($user_id); ?></span>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded">Logout</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sites Management -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Your Sites</h2>
                <div class="space-y-4">
                    <?php foreach ($sites as $site): ?>
                    <div class="border p-4 rounded">
                        <h3 class="font-medium"><?php echo htmlspecialchars($site['domain']); ?></h3>
                        <div class="mt-2 flex space-x-2">
                            <button onclick="showBacklinks(<?php echo $site['id']; ?>)" class="bg-blue-500 text-white px-3 py-1 rounded text-sm">
                                View Backlinks
                            </button>
                            <button onclick="addBacklink(<?php echo $site['id']; ?>)" class="bg-green-500 text-white px-3 py-1 rounded text-sm">
                                Add Backlink
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button onclick="addSite()" class="mt-4 bg-indigo-500 text-white px-4 py-2 rounded">
                    Add New Site
                </button>
            </div>

            <!-- Backlinks View -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Backlinks</h2>
                <div id="backlinks-container" class="space-y-4">
                    <!-- Backlinks will be loaded here dynamically -->
                </div>
            </div>
        </div>
    </div>

    <script src="js/panel.js"></script>
</body>
</html>