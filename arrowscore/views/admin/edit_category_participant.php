<?php ob_start(); ?>
<h2 class="text-xl font-bold mb-4">Edit Peserta - Kategori: <?= htmlspecialchars($category['name']) ?></h2>

<form method="post" class="bg-white p-4 rounded shadow space-y-3 max-w-lg">
<?= CSRF::getTokenField() ?>
    <div>
        <label class="block mb-1">Nama Lengkap</label>
        <input type="text" name="name" class="w-full border p-2 rounded" value="<?= htmlspecialchars($participant['name']) ?>" required>
    </div>
    <div>
        <label class="block mb-1">Klub</label>
        <input type="text" name="club_name" class="w-full border p-2 rounded" value="<?= htmlspecialchars($participant['club_name'] ?? '') ?>" placeholder="Ketik nama klub" list="club-list">
        <datalist id="club-list">
            <?php foreach ($clubs as $club): ?>
                <option value="<?= htmlspecialchars($club['name']) ?>">
            <?php endforeach; ?>
        </datalist>
    </div>
    <div>
        <label class="block mb-1">Tanggal Lahir</label>
        <input type="date" name="birth_date" class="w-full border p-2 rounded" value="<?= htmlspecialchars($participant['birth_date']) ?>">
    </div>
    <div>
        <label class="block mb-1">Alamat</label>
        <textarea name="address" class="w-full border p-2 rounded" rows="2"><?= htmlspecialchars($participant['address'] ?? '') ?></textarea>
    </div>
    <div>
        <label class="block mb-1">Jenis Kelamin</label>
        <select name="gender" class="w-full border p-2 rounded">
            <option value="">-- Pilih --</option>
            <option value="laki-laki" <?= ($participant['gender'] ?? '') == 'laki-laki' ? 'selected' : '' ?>>Laki‑laki</option>
            <option value="perempuan" <?= ($participant['gender'] ?? '') == 'perempuan' ? 'selected' : '' ?>>Perempuan</option>
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Perubahan</button>
        <a href="<?= BASE_URL ?>/cp/<?= $category['id'] ?>" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">Batal</a>
    </div>
</form>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>