<?php ob_start(); ?>
<h2 class="text-xl font-bold mb-4">Aturan Poin - <?= htmlspecialchars($competition['name']) ?></h2>
<form method="post" action="<?= BASE_URL ?>/admin/series-points/<?= $competition['id'] ?>/save" class="bg-white p-4 rounded shadow max-w-md">
    <p class="mb-2 text-sm">Isi poin per peringkat. Kosongkan jika tidak memberikan poin.</p>
    <?php for ($rank = 1; $rank <= 20; $rank++): ?>
        <div class="flex items-center mb-2">
            <label class="w-20">Peringkat <?= $rank ?></label>
            <input type="number" name="points[<?= $rank ?>]" value="<?= $rules[$rank] ?? '' ?>" class="border p-1 w-24 rounded" min="0">
        </div>
    <?php endfor; ?>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Aturan</button>
    <a href="<?= BASE_URL ?>/admin/dashboard" class="text-gray-600 ml-2">Kembali</a>
</form>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>