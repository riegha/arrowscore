<?php ob_start(); ?>
<h2 class="text-xl font-bold mb-4">Import Peserta CSV ke Kategori: <?= htmlspecialchars($category['name']) ?></h2>

<div class="bg-white p-4 rounded shadow max-w-lg">
    <p class="mb-2 text-sm">Format CSV: <strong>Nama, Klub, Tanggal Lahir (YYYY-MM-DD), Alamat</strong></p>
    <div class="mb-3 flex gap-2">
        <a href="<?= BASE_URL ?>/cp/<?= $category['id'] ?>/download-template" 
           class="bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm hover:bg-gray-400">
           Download Template CSV
        </a>
    </div>
    <form method="post" enctype="multipart/form-data" class="space-y-3">
	<?= CSRF::getTokenField() ?>
        <input type="hidden" name="category_id" value="<?= $category['id'] ?>">
        <div>
            <label class="block mb-1">File CSV</label>
            <input type="file" name="csv_file" accept=".csv" class="w-full border p-2 rounded" required>
        </div>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Import</button>
        <a href="<?= BASE_URL ?>/cp/<?= $category['id'] ?>" class="text-gray-600 ml-2">Batal</a>
    </form>
</div>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>