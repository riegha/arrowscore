<?php ob_start(); ?>
<h2 class="text-2xl font-bold mb-4">Koreksi Skor - <?= htmlspecialchars($round['name']) ?></h2>
<div class="mb-4 flex gap-2">
    <a href="<?= BASE_URL ?>/admin/correction/<?= $round_id ?>/pdf" target="_blank" class="bg-red-600 text-white px-3 py-2 rounded">Ekspor PDF</a>
    <button type="submit" form="correction-form" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Perubahan</button>
    <a href="<?= BASE_URL ?>/admin/rounds/<?= $competition['id'] ?>" class="bg-gray-500 text-white px-3 py-2 rounded">Kembali</a>
</div>

<form id="correction-form" method="post" action="<?= BASE_URL ?>/admin/correction/<?= $round_id ?>">
    <?= CSRF::getTokenField() ?>
    <div class="overflow-x-auto">
        <table class="bg-white shadow rounded text-xs">
            <thead>
                <tr>
                    <th class="p-2 border sticky left-0 bg-white z-10">Nama Peserta</th>
                    <?php for ($r=1; $r<=$totalRambahan; $r++): ?>
                        <?php for ($s=1; $s<=$shotsPerRambahan; $s++): ?>
                            <th class="p-2 border">R<?= $r ?>S<?= $s ?></th>
                        <?php endfor; ?>
                        <th class="p-2 border bg-gray-100">Total R<?= $r ?></th>
                    <?php endfor; ?>
                    <th class="p-2 border bg-gray-200 font-bold">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($participants as $p): ?>
                <tr>
                    <td class="p-2 border font-medium sticky left-0 bg-white z-10"><?= htmlspecialchars($p['name']) ?></td>
                    <?php $grandTotal = 0; ?>
                    <?php foreach ($rambahans as $rambahan): 
                        $rambahanTotal = 0;
                        $shots = $shotsData[$p['id']][$rambahan['id']] ?? [];
                        $shotsByNumber = [];
                        foreach ($shots as $shot) {
                            $shotsByNumber[$shot['shot_number']] = $shot;
                        }
                    ?>
                        <?php for ($s=1; $s<=$shotsPerRambahan; $s++): 
                            $shot = $shotsByNumber[$s] ?? ['score' => '', 'is_x' => 0];
                            $value = $shot['score'] ?? '';
                            $isX = !empty($shot['is_x']);
                            $rambahanTotal += (int)$value;
                        ?>
                        <td class="p-1 border">
                            <div class="flex items-center gap-1">
                                <input type="number" min="0" max="<?= $competition['max_score_per_shot'] ?? 10 ?>"
                                       name="score[<?= $rambahan['id'] ?>][<?= $p['id'] ?>][<?= $s ?>]"
                                       value="<?= $value ?>" class="w-8 border p-1 text-center">
                                <label class="text-xs">
                                    <input type="checkbox" 
                                           name="is_x[<?= $rambahan['id'] ?>][<?= $p['id'] ?>][<?= $s ?>]"
                                           <?= $isX ? 'checked' : '' ?>
                                           value="1"> X
                                </label>
                            </div>
                        </td>
                        <?php endfor; ?>
                        <td class="p-2 border text-center bg-gray-100">
                            <?= $rambahanTotal ?>
                        </td>
                        <?php $grandTotal += $rambahanTotal; ?>
                    <?php endforeach; ?>
                    <td class="p-2 border text-center font-bold bg-gray-200">
                        <?= $grandTotal ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<script>
// Hitung total per rambahan dan total keseluruhan secara real-time (opsional)
document.querySelectorAll('input[type=number]').forEach(input => {
    input.addEventListener('input', function() {
        const row = this.closest('tr');
        let grandTotal = 0;
        // Cari setiap rambahan (blok setelah nama)
        const rambahanBlocks = row.querySelectorAll('td.bg-gray-100');
        rambahanBlocks.forEach(block => {
            let rambahanTotal = 0;
            const inputs = block.parentElement.querySelectorAll('input[type=number]');
            inputs.forEach(inp => {
                rambahanTotal += parseInt(inp.value) || 0;
            });
            block.textContent = rambahanTotal;
            grandTotal += rambahanTotal;
        });
        // Update grand total (kolom terakhir)
        row.querySelector('td.bg-gray-200').textContent = grandTotal;
    });
});
</script>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>