<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Input Skor (Terkunci)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-2">
    <div class="max-w-xl mx-auto">
        <div class="bg-white rounded p-3 mb-3 shadow text-sm">
            <div><strong><?= htmlspecialchars($competition['name']) ?></strong></div>
            <div>Babak: <?= htmlspecialchars($round['name']) ?></div>
        </div>

        <div class="bg-yellow-100 text-yellow-800 p-3 rounded mb-4">Data sudah terkunci.</div>

        <?php foreach ($participants as $idx => $p): ?>
        <?php $participantId = $p['id']; ?>
        <div class="bg-white rounded-lg shadow mb-4 p-3">
            <div class="mb-2">
                <h3 class="font-semibold text-base"><?= htmlspecialchars($p['name']) ?></h3>
                <p class="text-xs text-gray-600">
                    Klub: <?= $p['club_name'] ?? 'Perorangan' ?><br>
                    Target: <?= htmlspecialchars($p['cushion_label'] . ' ' . $p['target_label']) ?> &nbsp;|&nbsp; Urutan: <?= $p['shooting_order'] ?>
                </p>
            </div>
            <div class="table-wrapper" style="overflow-x: auto;">
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
                        <?php foreach ($rambahans as $rambahan): ?>
                        <?php 
                            $rambahanId = $rambahan['id'];
                            $total = 0;
                            $xCount = 0;
                            // hitung total dari existingShots
                            if (isset($existingShots[$participantId])) {
                                for ($s=1; $s<=$shots_per_rambahan; $s++) {
                                    $key = $rambahanId.'_'.$s;
                                    $value = isset($existingShots[$participantId][$key]) ? $existingShots[$participantId][$key]['value'] : 0;
                                    $isX = isset($existingShots[$participantId][$key]) ? $existingShots[$participantId][$key]['isX'] : false;
                                    $total += (int)$value;
                                    if ($isX) $xCount++;
                                }
                            }
                        ?>
                        <tr>
                            <td class="font-medium">R<?= $rambahan['number'] ?></td>
                            <?php for ($s=1; $s<=$shots_per_rambahan; $s++): 
                                $key = $rambahanId.'_'.$s;
                                $val = isset($existingShots[$participantId][$key]) ? $existingShots[$participantId][$key]['value'] : 0;
                                $isX = isset($existingShots[$participantId][$key]) ? $existingShots[$participantId][$key]['isX'] : false;
                            ?>
                            <td class="p-1 text-center">
                                <?= $val ?>
                                <?php if ($isX): ?><span class="text-red-600">X</span><?php endif; ?>
                            </td>
                            <?php endfor; ?>
                            <td class="text-center font-bold"><?= $total ?></td>
                            <td class="text-center"><?= $xCount ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>