<?php ob_start(); ?>
<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Daftar Kompetisi</h2>
    <a href="<?= BASE_URL ?>/admin/competitions" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">+ Tambah Kompetisi</a>
</div>
<?php if (empty($competitions)): ?>
    <p class="text-gray-500">Belum ada kompetisi.</p>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($competitions as $comp): ?>
            <div class="bg-white p-4 rounded shadow">
                <h3 class="font-semibold text-lg"><?= htmlspecialchars($comp['name']) ?></h3>
                <p class="text-sm text-gray-500">Tipe: <?= $comp['type'] ?> | Status: <?= ucfirst($comp['status']) ?></p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/admin/rounds/<?= $comp['id'] ?>" class="bg-blue-600 text-white px-3 py-1 rounded text-sm">Kelola Babak</a>
                    <a href="<?= BASE_URL ?>/admin/participants/<?= $comp['id'] ?>" class="bg-yellow-600 text-white px-3 py-1 rounded text-sm">Peserta</a>
                    <?php if ($comp['type'] === 'series'): ?>
                        <a href="<?= BASE_URL ?>/admin/series-points/<?= $comp['id'] ?>" class="bg-purple-600 text-white px-3 py-1 rounded text-sm">Aturan Poin</a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/live-score/<?= $comp['public_link_slug'] ?>" target="_blank" class="bg-green-600 text-white px-3 py-1 rounded text-sm">Live Score</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>