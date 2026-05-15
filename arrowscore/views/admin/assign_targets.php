<?php ob_start(); ?>
<h2 class="text-2xl font-bold mb-4">Alokasi Peserta ke Target - <?= htmlspecialchars($round['name']) ?></h2>

<div class="flex gap-2 mb-4">
    <button type="button" id="random-assign-btn" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
        🎲 Acak Peserta
    </button>
    <button type="button" id="clear-assign-btn" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
        ❌ Kosongkan Semua
    </button>
    <span id="assign-status" class="text-sm text-gray-600 self-center"></span>
    <a href="<?= BASE_URL ?>/alokasi/<?= $round_id ?>" target="_blank" class="bg-teal-600 text-white px-4 py-2 rounded hover:bg-teal-700">
        👀 Lihat Tampilan Peserta
    </a>
</div>

<form method="post" action="<?= BASE_URL ?>/admin/assign-targets/<?= $round_id ?>/save" class="bg-white p-4 rounded shadow" id="assign-form">
    <?= CSRF::getTokenField() ?>
    <?php foreach ($cushions as $cid => $cushion): ?>
    <div class="mb-6">
        <h3 class="text-lg font-bold mb-2 p-2 bg-blue-100 rounded"><?= htmlspecialchars($cushion['label']) ?></h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($cushion['targets'] as $tid => $target): 
                $slot1 = $assignMap[$tid][1] ?? null;
                $slot2 = $assignMap[$tid][2] ?? null;
            ?>
            <div class="border p-3 rounded">
                <h4 class="font-medium text-sm mb-1"><?= htmlspecialchars($target['label']) ?></h4>
				<?php for ($order = 1; $order <= $shootingOrders; $order++): 
					$slot = $assignMap[$tid][$order] ?? null;
				?>
					<label class="block mb-1 text-xs">Urutan <?= $order ?>:</label>
					<select name="assignments[<?= $tid ?>][<?= $order ?>]" class="w-full border p-1 rounded text-sm target-select" data-target-id="<?= $tid ?>" data-order="<?= $order ?>">
						<option value="">-- Kosong --</option>
						<?php foreach ($participants as $p): ?>
							<option value="<?= $p['id'] ?>" <?= ($slot && $slot['participant_id'] == $p['id']) ? 'selected' : '' ?>>
								<?= htmlspecialchars($p['name']) ?> (<?= $p['club_name'] ?? '-' ?>)
							</option>
						<?php endforeach; ?>
					</select>
				<?php endfor; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <div class="mt-4 flex gap-2">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan Alokasi</button>
        <a href="<?= BASE_URL ?>/admin/rounds/<?= $competition['id'] ?>" class="bg-gray-500 text-white px-4 py-2 rounded">Kembali</a>
    </div>
</form>

<!-- Daftar peserta yang belum dialokasikan -->
<div class="mt-6">
    <h3 class="text-lg font-semibold mb-2">Peserta Belum Dialokasikan</h3>
    <div id="unassigned-list" class="bg-white p-4 rounded shadow flex flex-wrap gap-2"></div>
</div>

<!-- Tombol Tambah Alokasi Target (Sesi Berikutnya) -->
<?php
$maxSlots = 0;
foreach ($cushions as $c) $maxSlots += count($c['targets']) * 2;
$totalParticipants = count($participants);
$assignedCount = count($assignments);
$unassignedCount = $totalParticipants - $assignedCount;
?>
<?php if ($unassignedCount > 0): ?>
<div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded">
    <p class="text-yellow-800 mb-2">
        <strong>Perhatian:</strong> Terdapat <?= $unassignedCount ?> peserta yang belum mendapatkan slot. Slot maksimal babak ini adalah <?= $maxSlots ?>.
    </p>
    <a href="<?= BASE_URL ?>/admin/add-session-targets/<?= $round_id ?>" 
       class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
       Tambah Alokasi Target (Sesi Berikutnya)
    </a>
</div>
<?php endif; ?>

<script>
const allParticipantsRaw = <?= json_encode($participants) ?>;
const allParticipants = allParticipantsRaw.map(p => ({
    id: p.id.toString(),
    name: p.name + ' (' + (p.club_name ?? 'Perorangan') + ')',
    gender: p.gender
}));

document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('.target-select');
    const unassignedDiv = document.getElementById('unassigned-list');
    const statusSpan = document.getElementById('assign-status');
    const totalSlots = selects.length;

    function getSelectedIds() {
        const ids = [];
        selects.forEach(s => { if (s.value) ids.push(s.value); });
        return ids;
    }

    function updateUnassignedList() {
        const selectedIds = getSelectedIds();
        const unassigned = allParticipants.filter(p => !selectedIds.includes(p.id));
        unassignedDiv.innerHTML = '';
        if (unassigned.length === 0) {
            unassignedDiv.innerHTML = '<span class="text-green-600">Semua peserta sudah dialokasikan.</span>';
        } else {
            unassigned.forEach(p => {
                const span = document.createElement('span');
                span.className = 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm';
                span.textContent = p.name;
                unassignedDiv.appendChild(span);
            });
        }
        const filled = selectedIds.filter(id => id !== '').length;
        statusSpan.textContent = `Terisi: ${filled} dari ${totalSlots} slot. Sisa peserta: ${unassigned.length}.`;
    }

    function updateOptions() {
        const selectedIds = getSelectedIds();
        selects.forEach(select => {
            const currentValue = select.value;
            const options = select.querySelectorAll('option');
            options.forEach(opt => {
                const pid = opt.value;
                if (pid && selectedIds.includes(pid) && pid !== currentValue) {
                    opt.style.display = 'none';
                } else {
                    opt.style.display = '';
                }
            });
        });
        updateUnassignedList();
    }

    updateOptions();
    selects.forEach(select => select.addEventListener('change', updateOptions));

    document.getElementById('assign-form').addEventListener('submit', function(e) {
        const selectedIds = [];
        const duplicateIds = [];
        selects.forEach(select => {
            const val = select.value;
            if (val && val !== '') {
                if (selectedIds.includes(val)) duplicateIds.push(val);
                else selectedIds.push(val);
            }
        });
        if (duplicateIds.length > 0) {
            e.preventDefault();
            alert('Terdapat peserta yang sama di beberapa target. Harap periksa kembali alokasi.');
        }
    });

    // TOMBOL ACAK PESERTA (LAKI‑LAKI DULU, DARI DEPAN; PEREMPUAN DARI BELAKANG)
    document.getElementById('random-assign-btn').addEventListener('click', function() {
        const orderMap = {};
        selects.forEach(select => {
            const order = parseInt(select.dataset.order);
            if (!orderMap[order]) orderMap[order] = [];
            orderMap[order].push(select);
        });

        selects.forEach(s => s.value = '');

        let males   = allParticipants.filter(p => p.gender === 'laki-laki');
        let females = allParticipants.filter(p => p.gender === 'perempuan');

        males.sort(() => Math.random() - 0.5);
        females.sort(() => Math.random() - 0.5);

        const orders = Object.keys(orderMap).sort((a,b) => a-b);
        let maleIdx = 0;
        let femaleIdx = 0;

        orders.forEach(order => {
            const selectsInOrder = orderMap[order];
            const totalSlots = selectsInOrder.length;
            const maleCount = Math.min(males.length - maleIdx, totalSlots);
            const femaleCount = Math.min(females.length - femaleIdx, totalSlots - maleCount);

            for (let i = 0; i < maleCount; i++) {
                if (maleIdx < males.length) {
                    selectsInOrder[i].value = males[maleIdx].id;
                    maleIdx++;
                }
            }
            for (let i = 0; i < femaleCount; i++) {
                const slotIndex = totalSlots - 1 - i;
                if (femaleIdx < females.length) {
                    selectsInOrder[slotIndex].value = females[femaleIdx].id;
                    femaleIdx++;
                }
            }
        });

        updateOptions();
    });

    document.getElementById('clear-assign-btn').addEventListener('click', function() {
        selects.forEach(s => s.value = '');
        updateOptions();
    });
});
</script>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>