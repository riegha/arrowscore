<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Score - <?= htmlspecialchars($comp['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white p-4">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold mb-2 text-center"><?= htmlspecialchars($comp['name']) ?></h2>
			<p class="text-center text-sm mb-4">
				Kategori: <?= htmlspecialchars($category['name']) ?> &nbsp;|&nbsp; 
				Babak: <?= htmlspecialchars($round['name']) ?>
			</p>
        <div class="bg-gray-800 rounded-lg p-4 shadow">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-gray-600">
                        <th class="py-2 px-2">Peringkat</th>
                        <th class="py-2 px-2">Nama</th>
                        <th class="py-2 px-2">Klub</th>
                        <th class="py-2 px-2">Total</th>
                        <th class="py-2 px-2">X</th>
                    </tr>
                </thead>
                <tbody id="scoreBody">
                    <?php $rank = 1; foreach ($scores as $s): ?>
                    <tr class="border-b border-gray-700">
                        <td class="py-2 px-2"><?= $rank++ ?></td>
                        <td class="py-2 px-2"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="py-2 px-2"><?= $s['club_name'] ?? '-' ?></td>
                        <td class="py-2 px-2 font-bold"><?= $s['total_score'] ?></td>
                        <td class="py-2 px-2"><?= $s['x_count'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        setInterval(async () => {
            const resp = await fetch('<?= BASE_URL ?>/live-score/<?= $comp['public_link_slug'] ?>?round_id=<?= $round['id'] ?>&format=json');
            const data = await resp.json();
            let html = '';
            let rank = 1;
            data.forEach(s => {
                html += `<tr class="border-b border-gray-700">
                    <td class="py-2 px-2">${rank++}</td>
                    <td class="py-2 px-2">${s.name}</td>
                    <td class="py-2 px-2">${s.club_name ?? '-'}</td>
                    <td class="py-2 px-2 font-bold">${s.total_score}</td>
                    <td class="py-2 px-2">${s.x_count}</td>
                </tr>`;
            });
            document.getElementById('scoreBody').innerHTML = html;
        }, 5000);
    </script>
</body>
</html>