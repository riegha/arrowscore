<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alokasi Target - <?= htmlspecialchars($round['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print { .no-print { display: none; } body { background: white; } }
    </style>
</head>
<body class="bg-gray-100 p-4">
    <div class="max-w-7xl mx-auto">
        <div class="no-print mb-4 flex justify-between items-center">
            <a href="<?= BASE_URL ?>" class="text-blue-600 hover:underline">&larr; Kembali ke Beranda</a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                🖨️ Cetak
            </button>
        </div>

			<h2 class="text-2xl font-bold text-center mb-2">Alokasi Target</h2>
			<p class="text-center text-lg mb-4">
				<?= htmlspecialchars($competition['name']) ?> &raquo; 
				<?= htmlspecialchars($category['name']) ?> &raquo; 
				<?= htmlspecialchars($round['name']) ?>
			</p>

        <?php if (empty($cushions)): ?>
            <p class="text-center text-gray-500">Belum ada target dialokasikan.</p>
        <?php else: ?>
            <?php foreach ($cushions as $cushion): ?>
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-2 p-2 bg-blue-200 rounded"><?= htmlspecialchars($cushion['label']) ?></h3>
                
                <!-- Alokasi Target -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                    <?php foreach ($cushion['targets'] as $tid => $target):
                        for ($order = 1; $order <= $shootingOrders; $order++):
                            $slot = $assignMap[$tid][$order] ?? null;
                        ?>
                        <div class="border bg-white rounded p-3 shadow">
                            <h4 class="font-semibold text-sm mb-2"><?= htmlspecialchars($target['label']) ?> - Urutan <?= $order ?></h4>
                            <p class="font-medium">
                                <?= $slot ? htmlspecialchars($slot['participant_name']) : '<span class="text-gray-400">Kosong</span>' ?>
                            </p>
                        </div>
                        <?php endfor;
                    endforeach; ?>
                </div>

                <!-- Juara Bantalan -->
                <?php if ($hasCushionChampion && !empty($cushionChampions[$cushion['id']])): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                    <h4 class="font-semibold text-sm mb-2">🏆 Juara Bantalan</h4>
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th class="py-1 px-2 text-left">Peringkat</th>
                                <th class="py-1 px-2 text-left">Nama</th>
                                <th class="py-1 px-2 text-left">Klub</th>
                                <th class="py-1 px-2 text-left">Total Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($cushionChampions[$cushion['id']] as $champ): ?>
                            <tr>
                                <td class="py-1 px-2"><?= $rank++ ?></td>
                                <td class="py-1 px-2"><?= htmlspecialchars($champ['name']) ?></td>
                                <td class="py-1 px-2"><?= htmlspecialchars($champ['club_name'] ?? '-') ?></td>
                                <td class="py-1 px-2 font-bold"><?= $champ['total_score'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>