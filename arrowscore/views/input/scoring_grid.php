<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Input Skor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .shot-cell { display: flex; align-items: center; gap: 2px; }
        .btn-x { font-size: 10px; padding: 2px 6px; border-radius: 4px; cursor: pointer; white-space: nowrap; background: #fca5a5; color: #991b1b; }
        input[type=number] { -moz-appearance: textfield; }
    </style>
</head>
<body class="bg-gray-100 p-2" x-data="scoringGrid()">
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded p-3 mb-3 shadow text-sm">
            <div><strong><?= htmlspecialchars($competition['name']) ?></strong></div>
            <div>Babak: <?= htmlspecialchars($round['name']) ?></div>
        </div>

        <?php if ($isLocked): ?>
            <div class="bg-yellow-100 text-yellow-800 p-3 rounded mb-4">Data sudah terkunci.</div>
        <?php endif; ?>

        <?php foreach ($participants as $idx => $p): ?>
        <div class="bg-white rounded-lg shadow mb-4 p-3">
            <div class="mb-2">
                <h3 class="font-semibold text-base"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="text-xs text-gray-600">
                    Klub: <?= $p['club_name'] ?? 'Perorangan' ?><br>
                    Target: <?= htmlspecialchars($p['cushion_label'] . ' ' . $p['target_label']) ?> &nbsp;|&nbsp; Urutan: <?= $p['shooting_order'] ?>
                </p>
            </div>
            <div class="table-wrapper">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-1 py-1">R</th>
                            <?php for ($s=1; $s<=$shots_per_rambahan; $s++): ?>
                                <th class="px-1 py-1">S<?= $s ?></th>
                            <?php endfor; ?>
                            <th class="px-1 py-1">Tot</th>
                            <th class="px-1 py-1">X</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rambahans as $rambahan): 
                            $rambahanId = $rambahan['id'];
                        ?>
                        <tr x-data="rambahanRow(<?= $p['id'] ?>, <?= $rambahanId ?>, <?= $shots_per_rambahan ?>)" x-init="initRow()">
                            <td class="font-medium">R<?= $rambahan['number'] ?></td>
                            <?php for ($s=1; $s<=$shots_per_rambahan; $s++): 
                                $cellValue = '';
                                $cellIsX = false;
                                if (isset($existingShots[$p['id']][$rambahanId.'_'.$s])) {
                                    $cellValue = $existingShots[$p['id']][$rambahanId.'_'.$s]['value'];
                                    $cellIsX = $existingShots[$p['id']][$rambahanId.'_'.$s]['isX'];
                                }
                            ?>
                            <td class="p-1">
                                <div class="shot-cell">
                                    <?php if ($isLocked): ?>
                                        <span class="w-12 text-center"><?= $cellValue !== '' ? $cellValue : '-' ?></span>
                                    <?php else: ?>
                                        <input type="number" inputmode="numeric" min="0" max="<?= $maxScore ?>"
                                               class="w-12 border rounded p-1 text-center paste-target auto-advance"
                                               :value="getShotValue(<?= $s ?>)"
                                               @input="setShotValue(<?= $s ?>, $event.target.value); autoAdvance(<?= $s ?>, $event.target.value)"
                                               @focus="$event.target.select()"
                                               data-pid="<?= $p['id'] ?>"
                                               data-rid="<?= $rambahanId ?>"
                                               data-shot="<?= $s ?>">
                                        <button type="button" class="btn-x" @click="setShot(<?= $s ?>, <?= $maxScore ?>, true); autoAdvance(<?= $s ?>, <?= $maxScore ?>)">X</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endfor; ?>
                            <td class="text-center" x-text="total"></td>
                            <td class="text-center" x-text="xCount"></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!$isLocked): ?>
        <div class="mt-4 flex justify-center gap-2">
            <button @click="save()" class="bg-green-600 text-white px-4 py-3 rounded hover:bg-green-700 text-lg flex-1">
                💾 Simpan Sementara
            </button>
            <button @click="submitAll()" class="bg-blue-600 text-white px-4 py-3 rounded hover:bg-blue-700 text-lg flex-1">
                🔒 Submit & Kunci
            </button>
        </div>
        <?php endif; ?>
    </div>

    <script>
    const EXISTING = <?= json_encode($existingShots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const MAX_SCORE = <?= $maxScore ?>;
    const PARTICIPANT_IDS = <?= json_encode(array_column($participants, 'id')) ?>;
    const RAMBAHAN_IDS = <?= json_encode(array_column($rambahans, 'id')) ?>;
    const SHOTS_PER = <?= $shots_per_rambahan ?>;
    const MAX_DIGITS = String(MAX_SCORE).length;

    let globalScores = {};
    if (EXISTING) {
        for (let pid in EXISTING) {
            if (!globalScores[pid]) globalScores[pid] = {};
            for (let key in EXISTING[pid]) {
                globalScores[pid][key] = {
                    value: EXISTING[pid][key].value,
                    isX: EXISTING[pid][key].isX
                };
            }
        }
    }

    function scoringGrid() {
        return {
            async save() {
                await sendData(false);
                alert('Data tersimpan (belum dikunci).');
            },
            async submitAll() {
                if (!confirm('Kunci semua skor?')) return;
                await sendData(true);
                alert('Data tersimpan dan terkunci!');
                location.reload();
            }
        };
    }

    function rambahanRow(participantId, rambahanId, totalShots) {
        return {
            total: 0,
            xCount: 0,
            initRow() {
                this.computeTotal();
            },
            computeTotal() {
                let sum = 0, x = 0;
                for (let s = 1; s <= totalShots; s++) {
                    let key = rambahanId + '_' + s;
                    let cell = (globalScores[participantId] && globalScores[participantId][key]) || { value: '', isX: false };
                    let val = cell.value === '' ? 0 : Number(cell.value);
                    sum += val;
                    if (cell.isX) x++;
                }
                this.total = sum;
                this.xCount = x;
            },
            getShotValue(shotNum) {
                let key = rambahanId + '_' + shotNum;
                if (globalScores[participantId] && globalScores[participantId][key]) {
                    return globalScores[participantId][key].value;
                }
                return '';
            },
            setShotValue(shotNum, newValue) {
                if (!globalScores[participantId]) globalScores[participantId] = {};
                let key = rambahanId + '_' + shotNum;
                if (!globalScores[participantId][key]) {
                    globalScores[participantId][key] = { value: '', isX: false };
                }
                globalScores[participantId][key].value = newValue;
                this.computeTotal();
            },
            setShot(shotNum, value, isX) {
                if (!globalScores[participantId]) globalScores[participantId] = {};
                let key = rambahanId + '_' + shotNum;
                globalScores[participantId][key] = { value: value, isX: isX };
                this.computeTotal();
            },
            // Fungsi auto-advance yang dipanggil setelah input berubah
            autoAdvance(shotNum, value) {
                if (String(value).length >= MAX_DIGITS) {
                    this.$nextTick(() => {
                        const currentTd = this.$el.querySelector(`input[data-shot="${shotNum}"]`).closest('td');
                        if (currentTd) {
                            const nextTd = currentTd.nextElementSibling;
                            if (nextTd) {
                                const nextInput = nextTd.querySelector('input.auto-advance');
                                if (nextInput) {
                                    nextInput.focus();
                                    nextInput.select();
                                }
                            }
                        }
                    });
                }
            }
        };
    }

    // Paste multi‑baris (tetap seperti sebelumnya)
    document.addEventListener('DOMContentLoaded', function() {
        const pasteTargets = document.querySelectorAll('.paste-target');
        pasteTargets.forEach(input => {
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pid = this.dataset.pid;
                const startRid = this.dataset.rid;
                const startShot = parseInt(this.dataset.shot);
                if (!pid || !startRid || isNaN(startShot)) return;

                const clipboardData = e.clipboardData || window.clipboardData;
                if (!clipboardData) return;
                let pasteText = clipboardData.getData('text');
                if (!pasteText) return;

                let lines = pasteText.split(/\r?\n/).filter(line => line.trim() !== '');
                if (lines.length === 0) return;

                let allValues = [];
                for (let line of lines) {
                    let values = line.split(/[\s\t,]+/).filter(v => v !== '');
                    allValues = allValues.concat(values);
                }

                let currentRow = this.closest('tr');
                if (!currentRow) return;

                const tbody = currentRow.parentNode;
                const allRows = Array.from(tbody.querySelectorAll('tr'));

                let valueIndex = 0;
                let rowIndex = allRows.indexOf(currentRow);
                if (rowIndex === -1) return;

                while (valueIndex < allValues.length) {
                    let targetRow = allRows[rowIndex];
                    if (!targetRow) break;

                    let firstInput = targetRow.querySelector('input[data-rid]');
                    if (!firstInput) break;
                    let targetRid = firstInput.dataset.rid;
                    let targetPid = firstInput.dataset.pid;

                    let shotStart = (rowIndex === allRows.indexOf(currentRow)) ? startShot : 1;

                    for (let shot = shotStart; shot <= SHOTS_PER; shot++) {
                        if (valueIndex >= allValues.length) break;
                        let val = parseInt(allValues[valueIndex], 10);
                        if (!isNaN(val) && val >= 0 && val <= MAX_SCORE) {
                            if (!globalScores[targetPid]) globalScores[targetPid] = {};
                            let key = targetRid + '_' + shot;
                            globalScores[targetPid][key] = { value: val, isX: false };

                            let inputInRow = targetRow.querySelector(`input[data-shot="${shot}"]`);
                            if (inputInRow) {
                                inputInRow.value = val;
                                inputInRow.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }
                        valueIndex++;
                    }

                    rowIndex++;
                    if (rowIndex >= allRows.length) break;
                }
            });
        });
    });

    async function sendData(lock) {
        let payload = { scores: {} };
        for (let pid of PARTICIPANT_IDS) {
            payload.scores[pid] = {};
            for (let rid of RAMBAHAN_IDS) {
                payload.scores[pid][rid] = {};
                for (let s = 1; s <= SHOTS_PER; s++) {
                    let key = rid + '_' + s;
                    let cell = (globalScores[pid] && globalScores[pid][key]) || { value: '', isX: false };
                    if (lock) {
                        payload.scores[pid][rid][s] = {
                            value: cell.value === '' ? 0 : Number(cell.value),
                            isX: cell.isX || false
                        };
                    } else {
                        if (cell.value !== '' && cell.value !== null) {
                            payload.scores[pid][rid][s] = {
                                value: Number(cell.value),
                                isX: cell.isX || false
                            };
                        }
                    }
                }
            }
        }
        let url = lock ? '<?= BASE_URL ?>/input/<?= $slug ?>/submit' : '<?= BASE_URL ?>/input/<?= $slug ?>/save';
        let resp = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        let result = await resp.json();
        if (!result.success) {
            alert('Gagal: ' + (result.error || ''));
        }
    }

    // Heartbeat setiap 30 detik agar session tetap aktif
    setInterval(async () => {
        await fetch('<?= BASE_URL ?>/input/<?= $slug ?>/heartbeat');
    }, 30000);
    </script>
</body>
</html>