<?php ob_start(); ?>
<h2 class="text-xl font-bold mb-4">Edit Kategori - <?= htmlspecialchars($category['name']) ?></h2>

<form method="post" class="bg-white p-4 rounded shadow max-w-lg">
<?= CSRF::getTokenField() ?>
    <div class="space-y-3">
        <div>
            <label class="block mb-1">Nama Kategori</label>
            <input name="cat_name" class="w-full border p-2 rounded" value="<?= htmlspecialchars($category['name']) ?>" required>
        </div>
        <div>
            <label class="block mb-1">Nilai Maksimal per Tembakan</label>
            <input name="max_score" type="number" class="w-full border p-2 rounded" value="<?= $category['max_score_per_shot'] ?>">
        </div>
        <div>
            <label class="block mb-1">Nilai Minimal per Tembakan</label>
            <input name="min_score" type="number" class="w-full border p-2 rounded" value="<?= $category['min_score_per_shot'] ?>">
        </div>
    </div>
    <div class="mt-4 flex gap-2">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
        <a href="<?= BASE_URL ?>/admin/rounds/<?= $competition['id'] ?>" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">Batal</a>
    </div>
</form>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>