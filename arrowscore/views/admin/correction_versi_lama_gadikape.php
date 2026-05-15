<?php ob_start(); ?>
<h2 class="text-2xl font-bold mb-4">Koreksi Skor - <?= htmlspecialchars($round['name']) ?></h2>
<?php if (!$editing): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <?php foreach ($participants as $p): ?>
            <a href="?participant_id=<?= $p['id'] ?>" class="bg-white p-3 rounded shadow hover:bg-gray-50">
                <?= htmlspecialchars($p['name']) ?> (<?= $p['club_name'] ?? '-' ?>)
            </a>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <a href="<?= BASE_URL ?>/admin/correction/<?= $round_id ?>" class="text-blue-600 mb-2 inline-block">&larr; Kembali</a>
    <form method="post">
	<?= CSRF::getTokenField() ?>
        <input type="hidden" name="participant_id" value="<?= $editing['participant_id'] ?>">
        <div class="bg-white p-4 rounded shadow">
            <?php foreach ($editing['rambahans'] as $rambahan): ?>
                <div class="mb-4 border-b pb-2">
                    <h4 class="font-medium">Rambahan <?= $rambahan['number'] ?></h4>
                    <input type="hidden" name="rambahan_id" value="<?= $rambahan['id'] ?>">
                    <div class="flex space-x-2 mt-1">
                        <?php
                        $shotData = $editing['shots'][$rambahan['id']] ?? [];
                        $shotMap = [];
                        foreach ($shotData as $sh) { $shotMap[$sh['shot_number']] = $sh; }
                        for ($s = 1; $s <= $round['shots_per_rambahan']; $s++):
                            $shot = $shotMap[$s] ?? ['score' => 0, 'is_x' => 0];
                        ?>
                        <div class="flex items-center space-x-1">
                            <span>S<?= $s ?></span>
                            <input type="number" name="score[<?= $s ?>]" value="<?= $shot['score'] ?>" min="0" max="10" class="w-16 border p-1 rounded text-center">
                            <label class="text-sm"><input type="checkbox" name="is_x[<?= $s ?>]" <?= $shot['is_x'] ? 'checked' : '' ?>> X</label>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Koreksi</button>
        </div>
    </form>
<?php endif; ?>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>