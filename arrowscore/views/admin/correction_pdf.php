<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Scoresheet - <?= htmlspecialchars($round['name']) ?></title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        th, td { border: 1px solid #000; padding: 4px; text-align: center; word-break: break-all; }
        th { background-color: #ddd; }
        h2, p { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <h2><?= htmlspecialchars($competition['name']) ?> - <?= htmlspecialchars($round['name']) ?></h2>
    <p>Kategori: <?= htmlspecialchars($category['name']) ?></p>
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <?php for ($r=1; $r<=$totalRambahan; $r++): ?>
                    <?php for ($s=1; $s<=$shotsPerRambahan; $s++): ?>
                        <th>R<?= $r ?>S<?= $s ?></th>
                    <?php endfor; ?>
                    <th>Total R<?= $r ?></th>
                <?php endfor; ?>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($participants as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <?php $grand = 0; foreach ($rambahans as $rambahan): 
                    $shots = $shotsData[$p['id']][$rambahan['id']] ?? [];
                    $shMap = []; foreach ($shots as $sh) { $shMap[$sh['shot_number']] = $sh; }
                    $ramTotal = 0;
                    for ($s=1; $s<=$shotsPerRambahan; $s++):
                        $val = isset($shMap[$s]) ? $shMap[$s]['score'] : '';
                        $ramTotal += (int)$val;
                ?>
                    <td><?= $val !== '' ? $val : '-' ?></td>
                <?php endfor; ?>
                <td><?= $ramTotal ?></td>
                <?php $grand += $ramTotal; endforeach; ?>
                <td><strong><?= $grand ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>