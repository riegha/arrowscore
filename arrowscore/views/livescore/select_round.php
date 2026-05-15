<?php ob_start(); ?>
<div class="max-w-4xl mx-auto mt-6">
    <h2 class="text-2xl font-bold mb-4">Live Score - <?= htmlspecialchars($comp['name']) ?></h2>
    <?php if (empty($rounds)): ?>
        <p class="text-gray-500">Belum ada babak.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($rounds as $round): ?>
                <a href="?round_id=<?= $round['id'] ?>" class="bg-white p-4 rounded shadow hover:shadow-md">
                    <div class="font-semibold"><?= htmlspecialchars($round['name']) ?></div>
                    <div class="text-sm text-gray-500">Kategori: <?= htmlspecialchars($round['category_name']) ?></div>
                    <div class="text-sm text-gray-500">Status: <?= $round['status'] ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($comp['type'] === 'series'): ?>
        <a href="<?= BASE_URL ?>/series-standings/<?= $comp['public_link_slug'] ?>" class="mt-4 inline-block bg-yellow-600 text-white px-4 py-2 rounded">Lihat Klasemen Series</a>
    <?php endif; ?>
    <div class="mt-4"><a href="<?= BASE_URL ?>" class="text-blue-600">&larr; Kembali</a></div>
</div>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>