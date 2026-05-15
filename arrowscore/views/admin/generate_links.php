<?php ob_start(); ?>
<h2 class="text-2xl font-bold mb-4">Generate Link Input - <?= htmlspecialchars($round['name']) ?></h2>

<?php if (empty($targetGroups)): ?>
    <div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">
        Semua peserta sudah memiliki link input, atau belum ada alokasi target.
    </div>
<?php else: ?>
<form method="post" class="bg-white p-4 rounded shadow mb-6">
    <?= CSRF::getTokenField() ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($targetGroups as $tid => $group): ?>
        <div class="border p-2 rounded">
            <label>
                <input type="checkbox" name="targets[]" value="<?= $tid ?>">
                <?= htmlspecialchars($group[0]['cushion_label'] ?? 'Bantalan') ?> - <?= htmlspecialchars($group[0]['target_label'] ?? 'Target') ?>
                <?php foreach ($group as $p): ?>
                    <br><small><?= $p['participant_name'] ?> (Urutan <?= $p['shooting_order'] ?>)</small>
                <?php endforeach; ?>
            </label>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-3 flex items-center gap-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="no_password" value="1">
            Tanpa Password (langsung ke input skor)
        </label>
    </div>
    <div class="mt-3">
        <label>Password:</label>
        <input type="text" name="password" value="1234" class="border p-1 rounded" id="password-field">
    </div>
    <button type="submit" class="mt-2 bg-green-600 text-white px-4 py-2 rounded">Generate Link</button>
</form>
<?php endif; ?>

<h3 class="font-semibold mb-2">Link yang Sudah Dibuat</h3>
<?php if (empty($sessions)): ?>
    <p class="text-gray-500">Belum ada link input.</p>
<?php else: ?>
    <ul class="list-disc ml-6 space-y-2">
        <?php foreach ($sessions as $s): ?>
            <li class="flex items-center gap-2">
                <a href="<?= BASE_URL ?>/input/<?= $s['unique_slug'] ?>" target="_blank" class="text-blue-600 underline">
                    <?= BASE_URL ?>/input/<?= $s['unique_slug'] ?>
                </a>
                (Target: <?= implode(', ', $s['targets']) ?>, Password: <?= $s['hashed_password'] === '__NO_PASSWORD__' ? 'Tanpa' : 'Ada' ?>)
				<a href="<?= BASE_URL ?>/admin/unlock-link/<?= $s['id'] ?>?round_id=<?= $round['id'] ?>" 
				   class="text-green-600 hover:underline text-sm ml-2"
				   onclick="return confirm('Buka kunci link ini? Peserta bisa menginput kembali.')">Buka Kunci</a>
                <a href="<?= BASE_URL ?>/admin/delete-link/<?= $s['id'] ?>?round_id=<?= $round['id'] ?>" 
                   class="text-red-600 hover:underline text-sm ml-2"
                   onclick="return confirm('Hapus link ini?')">Hapus</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<script>
document.querySelector('input[name="no_password"]').addEventListener('change', function() {
    document.getElementById('password-field').style.display = this.checked ? 'none' : 'inline-block';
});
</script>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>