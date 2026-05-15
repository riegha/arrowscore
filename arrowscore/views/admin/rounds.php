<?php ob_start(); ?>
<h2 class="text-2xl font-bold mb-4">Kelola Babak - <?= htmlspecialchars($competition['name']) ?></h2>

<!-- Form Tambah Kategori -->
<h3 class="text-lg font-semibold mb-2">Kategori</h3>
<form method="post" class="bg-white p-4 rounded shadow mb-6">
<?= CSRF::getTokenField() ?>
    <input type="hidden" name="add_category" value="1">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label class="block mb-1 text-sm">Nama Kategori</label>
            <input name="cat_name" placeholder="contoh: U12 Putra" class="border p-2 rounded w-full" required>
        </div>
        <div>
            <label class="block mb-1 text-sm">Nilai Maksimal per Tembakan</label>
            <input name="max_score" type="number" value="10" class="border p-2 rounded w-full">
        </div>
        <div>
            <label class="block mb-1 text-sm">Nilai Minimal per Tembakan</label>
            <input name="min_score" type="number" value="0" class="border p-2 rounded w-full">
        </div>
    </div>
    <button type="submit" class="mt-2 bg-green-600 text-white px-4 py-2 rounded">Tambah Kategori</button>
</form>

<!-- Daftar Kategori -->
<h3 class="text-lg font-semibold mb-2 mt-6">Kategori Tersedia</h3>
<?php if (empty($categories)): ?>
    <p class="text-gray-500">Belum ada kategori.</p>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <?php foreach ($categories as $cat): ?>
            <div class="bg-white p-3 rounded shadow flex justify-between items-center">
                <span><?= htmlspecialchars($cat['name']) ?></span>
                <div class="flex gap-1">
                    <a href="<?= BASE_URL ?>/admin/edit-category/<?= $cat['id'] ?>" class="bg-yellow-500 text-white px-2 py-1 rounded text-xs">Edit</a>
                    <a href="<?= BASE_URL ?>/admin/delete-category/<?= $cat['id'] ?>" class="bg-red-600 text-white px-2 py-1 rounded text-xs" onclick="return confirm('Hapus kategori ini? Peserta yang hanya terdaftar di kategori ini akan ikut terhapus.')">Hapus</a>
                    <a href="<?= BASE_URL ?>/cp/<?= $cat['id'] ?>" class="bg-yellow-600 text-white px-2 py-1 rounded text-xs">Peserta</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Daftar Babak -->
<h3 class="text-lg font-semibold mb-2 mt-6">Daftar Babak</h3>
<?php if (empty($rounds)): ?>
    <p class="text-gray-500">Belum ada babak.</p>
<?php else: ?>
    <div class="space-y-3 mb-6">
        <?php foreach ($rounds as $round): ?>
            <div class="bg-white p-4 rounded shadow flex flex-col md:flex-row md:justify-between md:items-center">
                <div>
                    <p class="font-semibold"><?= htmlspecialchars($round['name']) ?> - <?= htmlspecialchars($round['category_name']) ?></p>
                    <p class="text-sm text-gray-600">
                        Format: <?= $round['format'] ?>, <?= $round['total_rambahan'] ?> Rambahan, <?= $round['shots_per_rambahan'] ?> Tembakan, <?= $round['face_target_count'] ?> Target, <?= $round['cushion_count'] ?> Bantalan,
                        Gender: <?= $round['allowed_gender'] ?? 'semua' ?>
                    </p>
                    <p class="text-sm text-gray-500">Status: <?= $round['status'] ?></p>
                </div>
                <div class="mt-2 md:mt-0 flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/admin/round-participants/<?= $round['id'] ?>" class="bg-lime-600 text-white px-3 py-1 rounded text-sm">Peserta Babak</a>
                    <a href="<?= BASE_URL ?>/admin/generate-links/<?= $round['id'] ?>" class="bg-orange-500 text-white px-3 py-1 rounded text-sm">Buat Link Input</a>
                    <a href="<?= BASE_URL ?>/admin/assign-targets/<?= $round['id'] ?>" class="bg-indigo-600 text-white px-3 py-1 rounded text-sm">Atur Target</a>
                    <a href="<?= BASE_URL ?>/alokasi/<?= $round['id'] ?>" target="_blank" class="bg-teal-600 text-white px-3 py-1 rounded text-sm">Lihat Alokasi</a>
                    <a href="<?= BASE_URL ?>/admin/correction/<?= $round['id'] ?>" class="bg-purple-600 text-white px-3 py-1 rounded text-sm">Koreksi Skor</a>
                    <a href="<?= BASE_URL ?>/admin/edit-round/<?= $round['id'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded text-sm">Edit</a>
                    <a href="<?= BASE_URL ?>/admin/delete-round/<?= $round['id'] ?>" class="bg-red-600 text-white px-3 py-1 rounded text-sm" onclick="return confirm('Hapus babak ini?')">Hapus</a>
                    <?php if ($round['status'] !== 'finished'): ?>
                        <a href="<?= BASE_URL ?>/admin/finish-round/<?= $round['id'] ?>" class="bg-green-600 text-white px-3 py-1 rounded text-sm" onclick="return confirm('Selesaikan babak ini?')">Selesaikan</a>
                    <?php endif; ?>
					<?php if ($round['status'] === 'finished'): ?>
						<a href="<?= BASE_URL ?>/admin/open-round/<?= $round['id'] ?>" 
						   class="bg-blue-600 text-white px-3 py-1 rounded text-sm"
						   onclick="return confirm('Buka kembali babak ini? Status akan menjadi pending dan bisa diinput ulang.')">
						   Buka Kembali
						</a>
					<?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$sessionDefaults = null;
if (isset($_GET['create_session_from']) && is_numeric($_GET['create_session_from'])) {
    $sourceRoundId = (int)$_GET['create_session_from'];
    $sourceRound = null;
    foreach ($rounds as $r) {
        if ($r['id'] == $sourceRoundId) {
            $sourceRound = $r;
            break;
        }
    }
    if ($sourceRound) {
        $baseName = $sourceRound['name'];
        $existingSessions = array_filter($rounds, function($r) use ($baseName) {
            return strpos($r['name'], $baseName) !== false;
        });
        $nextSession = count($existingSessions) + 1;
        $sessionDefaults = [
            'category_id'     => $sourceRound['category_id'],
            'format'          => $sourceRound['format'],
            'shots'           => $sourceRound['shots_per_rambahan'],
            'rambahan'        => $sourceRound['total_rambahan'],
            'face_target'     => $sourceRound['face_target_count'],
            'cushion_count'   => $sourceRound['cushion_count'],
            'allowed_gender'  => $sourceRound['allowed_gender'] ?? 'semua',
            'name'            => $sourceRound['name'] . ' - Sesi ' . $nextSession
        ];
    }
}
?>

<!-- Form Tambah Babak -->
<h3 class="text-lg font-semibold mt-6 mb-2">Tambah Babak Baru</h3>
<form method="post" class="bg-white p-4 rounded shadow">
<?= CSRF::getTokenField() ?>
    <input type="hidden" name="add_round" value="1">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <select name="category_id" class="border p-2 rounded" required>
            <option value="">-- Pilih Kategori --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                    <?= (isset($sessionDefaults['category_id']) && $sessionDefaults['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input name="round_name" placeholder="Nama Babak" class="border p-2 rounded" required
               value="<?= htmlspecialchars($sessionDefaults['name'] ?? '') ?>">
        <select name="format" class="border p-2 rounded">
            <option value="qualification" <?= (isset($sessionDefaults['format']) && $sessionDefaults['format'] == 'qualification') ? 'selected' : '' ?>>Qualification</option>
            <option value="bracket" <?= (isset($sessionDefaults['format']) && $sessionDefaults['format'] == 'bracket') ? 'selected' : '' ?>>Bracket</option>
        </select>
        <input name="total_rambahan" type="number" placeholder="Jumlah Rambahan" class="border p-2 rounded" required
               value="<?= $sessionDefaults['rambahan'] ?? '' ?>">
        <input name="shots_per_rambahan" type="number" placeholder="Tembakan per Rambahan" class="border p-2 rounded" required
               value="<?= $sessionDefaults['shots'] ?? '' ?>">
        <input name="face_target_count" type="number" placeholder="Jumlah Total Target" class="border p-2 rounded" required
               value="<?= $sessionDefaults['face_target'] ?? '' ?>">
        <input name="cushion_count" type="number" placeholder="Jumlah Bantalan" class="border p-2 rounded" required
               value="<?= $sessionDefaults['cushion_count'] ?? '' ?>">
		<input name="shooting_orders" type="number" placeholder="Jumlah Urutan per Target" class="border p-2 rounded" value="<?= $sessionDefaults['shooting_orders'] ?? '2' ?>" required>
			<div class="flex items-center gap-2">
				<input type="checkbox" name="has_cushion_champion" id="has_cushion_champion" value="1" <?= (isset($sessionDefaults['has_cushion_champion']) && $sessionDefaults['has_cushion_champion']) ? 'checked' : '' ?>>
				<label for="has_cushion_champion">Aktifkan Juara Bantalan</label>
			</div>
        <select name="allowed_gender" class="border p-2 rounded" required>
            <option value="semua" <?= (isset($sessionDefaults['allowed_gender']) && $sessionDefaults['allowed_gender'] == 'semua') ? 'selected' : '' ?>>Semua Jenis Kelamin</option>
            <option value="laki-laki" <?= (isset($sessionDefaults['allowed_gender']) && $sessionDefaults['allowed_gender'] == 'laki-laki') ? 'selected' : '' ?>>Laki‑laki</option>
            <option value="perempuan" <?= (isset($sessionDefaults['allowed_gender']) && $sessionDefaults['allowed_gender'] == 'perempuan') ? 'selected' : '' ?>>Perempuan</option>
        </select>
        <?php if (isset($_GET['create_session_from'])): ?>
            <input type="hidden" name="source_round_id" value="<?= $sourceRoundId ?>">
        <?php endif; ?>
    </div>
    <button type="submit" class="mt-3 bg-blue-600 text-white px-4 py-2 rounded">Tambah Babak</button>
</form>

<div class="mt-6">
    <a href="<?= BASE_URL ?>/admin/dashboard" class="text-blue-600">&larr; Kembali</a>
</div>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>