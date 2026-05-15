<?php ob_start(); ?>
<h2 class="text-xl font-bold mb-4">Tambah Peserta - <?= htmlspecialchars($competition['name']) ?></h2>
<form method="post" class="bg-white p-4 rounded shadow space-y-3 max-w-lg">
<?= CSRF::getTokenField() ?>
    <div>
        <label class="block mb-1">Nama Lengkap</label>
        <input name="name" class="w-full border p-2 rounded" required>
    </div>
    <div>
        <label class="block mb-1">Klub</label>
        <select name="club_id" class="w-full border p-2 rounded">
            <option value="">-- Perorangan --</option>
            <?php foreach ($clubs as $club): ?>
                <option value="<?= $club['id'] ?>"><?= htmlspecialchars($club['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="block mb-1">Tanggal Lahir</label>
        <input name="birth_date" type="date" class="w-full border p-2 rounded" required>
    </div>
    <div>
        <label class="block mb-1">Alamat</label>
        <textarea name="address" class="w-full border p-2 rounded" rows="2"></textarea>
    </div>
    <div>
        <label class="block mb-1">Kategori</label>
        <select name="category_id" class="w-full border p-2 rounded">
            <option value="">-- Pilih Kategori (opsional) --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
    <a href="<?= BASE_URL ?>/admin/participants/<?= $competition['id'] ?>" class="text-gray-600 ml-2">Batal</a>
</form>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>