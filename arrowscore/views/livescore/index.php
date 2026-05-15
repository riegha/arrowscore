<?php ob_start(); ?>
<div class="max-w-4xl mx-auto mt-6">
    <h2 class="text-2xl font-bold mb-6 text-center">Daftar Kompetisi</h2>
    <?php if (empty($competitions)): ?>
        <p class="text-center text-gray-500">Belum ada kompetisi.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($competitions as $comp): ?>
                <a href="<?= BASE_URL ?>/live-score/<?= $comp['public_link_slug'] ?>" class="bg-white rounded shadow p-6 hover:shadow-lg">
                    <h3 class="font-bold text-lg"><?= htmlspecialchars($comp['name']) ?></h3>
                    <p class="text-sm text-gray-500"><?= $comp['type'] ?> | <?= $comp['status'] ?></p>
                    <span class="text-blue-600 text-sm">Lihat Live Score &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>