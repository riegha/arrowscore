<?php
$isLoggedIn = Auth::isLoggedIn();
if ($isLoggedIn) {
    require_once 'models/Competition.php';
    $compModel = new Competition();
    $sidebarComps = $compModel->getAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archery Live Score</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
<?php if ($isLoggedIn): ?>
    <!-- ========== TAMPILAN DENGAN SIDEBAR (ADMIN) ========== -->
    <div class="flex">
        <!-- Sidebar Fixed -->
        <aside class="w-64 bg-green-800 text-white flex-shrink-0 h-screen sticky top-0 overflow-y-auto">
            <div class="p-4 border-b border-green-700 flex items-center space-x-3">
                <!-- Logo di sidebar -->
                <img src="<?= BASE_URL ?>/assets/images/logo_fespati.png" alt="Logo" class="w-20 h-20 rounded-full object-cover">
                <a href="<?= BASE_URL ?>" class="text-xl font-bold">Archery Live Score</a>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <p class="text-xs uppercase text-green-300 tracking-wider mb-2">Menu</p>
                <a href="<?= BASE_URL ?>/admin/dashboard" class="flex items-center space-x-3 px-3 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-tachometer-alt w-5"></i><span>Dashboard</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/competitions" class="flex items-center space-x-3 px-3 py-2 rounded hover:bg-green-700 transition">
                    <i class="fas fa-plus-circle w-5"></i><span>Tambah Kompetisi</span>
                </a>
                <?php if (!empty($sidebarComps)): ?>
                    <p class="text-xs uppercase text-green-300 tracking-wider mt-4 mb-1">Kompetisi</p>
                    <?php foreach ($sidebarComps as $comp): ?>
                        <a href="<?= BASE_URL ?>/admin/rounds/<?= $comp['id'] ?>" class="block text-sm px-3 py-1 rounded hover:bg-green-700 transition truncate">
                            <?= htmlspecialchars($comp['name']) ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            <div class="p-4 border-t border-green-700 mt-auto">
                <div class="text-xs text-green-300">Akun</div>
                <div class="text-sm"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></div>
                <a href="<?= BASE_URL ?>/admin/logout" class="text-sm text-red-300 hover:text-red-100 mt-2 inline-block">
                    <i class="fas fa-sign-out-alt mr-1"></i> Logout
                </a>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="flex-1 p-6 overflow-auto h-screen">
            <?= $content ?? '' ?>
        </main>
    </div>
<?php else: ?>
    <!-- ========== TAMPILAN PUBLIK (TANPA LOGIN) ========== -->
    <nav class="bg-green-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <!-- Logo di header publik -->
                <img src="<?= BASE_URL ?>/assets/images/logo_fespati.png" alt="Logo" class="w-20 h-20 rounded-full object-cover">
                <a href="<?= BASE_URL ?>" class="font-bold text-xl">Archery Live Score</a>
            </div>
            <a href="<?= BASE_URL ?>/admin" class="bg-green-600 px-1 py-1 rounded hover:bg-green-700">Login Admin</a>
        </div>
    </nav>
    <main class="container mx-auto py-6 px-4">
        <?= $content ?? '' ?>
    </main>
<?php endif; ?>
</body>
</html>