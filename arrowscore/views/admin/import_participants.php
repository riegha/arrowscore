<?php ob_start(); ?>
<h2 class="text-xl font-bold mb-4">Import Peserta CSV - <?= htmlspecialchars($competition['name']) ?></h2>
<div class="bg-white p-4 rounded shadow max-w-lg">
    <p class="mb-2 text-sm">Format: Nama, Klub, Tanggal Lahir (YYYY-MM-DD), Alamat</p>
    <form method="post" enctype="multipart/form-data" class="space-y-3">
	<?= CSRF::getTokenField() ?>
        <div>
            <label class="block mb-1">Kategori Tujuan (opsional)</label>
            <select name="category_id" class="w-full border p-2 rounded">
                <option value="">-- Semua --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block mb-1">File CSV</label>
            <input type="file" name="csv_file" accept=".csv" class="w-full border p-2 rounded" required>
        </div>
        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Import</button>
        <a href="<?= BASE_URL ?>/admin/participants/<?= $competition['id'] ?>" class="text-gray-600 ml-2">Batal</a>
    </form>
</div>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>