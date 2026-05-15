<?php ob_start(); ?>
<h2 class="text-2xl font-bold mb-4">Edit Babak - <?= htmlspecialchars($round['name']) ?></h2>

<form method="post" class="bg-white p-4 rounded shadow max-w-lg">
<?= CSRF::getTokenField() ?>
    <div class="space-y-3">
        <div>
            <label class="block mb-1">Nama Babak</label>
            <input name="round_name" class="w-full border p-2 rounded" value="<?= htmlspecialchars($round['name']) ?>" required>
        </div>
        <div>
            <label class="block mb-1">Kategori</label>
            <select name="category_id" class="w-full border p-2 rounded" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $round['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block mb-1">Format</label>
            <select name="format" class="w-full border p-2 rounded">
                <option value="qualification" <?= $round['format'] == 'qualification' ? 'selected' : '' ?>>Qualification</option>
                <option value="bracket" <?= $round['format'] == 'bracket' ? 'selected' : '' ?>>Bracket</option>
            </select>
        </div>
        <div>
            <label class="block mb-1">Jumlah Rambahan</label>
            <input name="total_rambahan" type="number" class="w-full border p-2 rounded" value="<?= $round['total_rambahan'] ?>" required>
        </div>
        <div>
            <label class="block mb-1">Tembakan per Rambahan</label>
            <input name="shots_per_rambahan" type="number" class="w-full border p-2 rounded" value="<?= $round['shots_per_rambahan'] ?>" required>
        </div>
        <div>
            <label class="block mb-1">Jumlah Total Target</label>
            <input name="face_target_count" type="number" class="w-full border p-2 rounded" value="<?= $round['face_target_count'] ?>" required>
        </div>
        <div>
            <label class="block mb-1">Jumlah Bantalan</label>
            <input name="cushion_count" type="number" class="w-full border p-2 rounded" value="<?= $round['cushion_count'] ?>" required>
        </div>
		<div>
			<label class="block mb-1">Jumlah Urutan per Target</label>
			<input name="shooting_orders" type="number" class="w-full border p-2 rounded" value="<?= $round['shooting_orders'] ?>" required>
		</div>
		<div class="flex items-center gap-2">
			<input type="checkbox" name="has_cushion_champion" id="has_cushion_champion" value="1" <?= ($round['has_cushion_champion'] ?? 0) ? 'checked' : '' ?>>
			<label for="has_cushion_champion">Aktifkan Juara Bantalan</label>
		</div>
        <div>
            <label class="block mb-1">Jenis Kelamin yang Diizinkan</label>
            <select name="allowed_gender" class="w-full border p-2 rounded">
                <option value="semua" <?= ($round['allowed_gender'] ?? 'semua') == 'semua' ? 'selected' : '' ?>>Semua</option>
                <option value="laki-laki" <?= ($round['allowed_gender'] ?? '') == 'laki-laki' ? 'selected' : '' ?>>Laki‑laki</option>
                <option value="perempuan" <?= ($round['allowed_gender'] ?? '') == 'perempuan' ? 'selected' : '' ?>>Perempuan</option>
            </select>
        </div>
    </div>
    <div class="mt-4 flex gap-2">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Perubahan</button>
        <a href="<?= BASE_URL ?>/admin/rounds/<?= $competition['id'] ?>" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">Batal</a>
    </div>
</form>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>