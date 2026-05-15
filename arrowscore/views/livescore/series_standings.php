<?php ob_start(); ?>
<div class="max-w-4xl mx-auto mt-6">
    <h2 class="text-2xl font-bold mb-4">Klasemen Series - <?= htmlspecialchars($comp['name']) ?></h2>

    <!-- Dropdown kategori -->
    <?php if (!empty($categories)): ?>
    <form method="get" class="mb-4">
        <label class="mr-2 font-medium">Kategori:</label>
        <select name="category_id" class="border p-2 rounded" onchange="this.form.submit()">
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $category_id ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <?php endif; ?>

    <?php if (!empty($individualStandings)): ?>
        <h3 class="text-xl font-semibold mb-2">Individu - <?= htmlspecialchars($selectedCategory['name']) ?></h3>
        <table class="w-full bg-white shadow rounded mb-6">
            <thead>
                <tr>
                    <th class="p-2 border">#</th>
                    <th class="p-2 border">Nama</th>
                    <th class="p-2 border">Klub</th>
                    <th class="p-2 border">Poin</th>
                    <th class="p-2 border">Total Skor</th>
                    <th class="p-2 border">Detail</th>
                </tr>
            </thead>
            <tbody>
                <?php $rank = 1; foreach ($individualStandings as $d): ?>
                    <tr>
                        <td class="p-2 border text-center"><?= $rank++ ?></td>
                        <td class="p-2 border"><?= htmlspecialchars($d['name']) ?></td>
                        <td class="p-2 border"><?= htmlspecialchars($d['club']) ?></td>
                        <td class="p-2 border text-center font-bold"><?= $d['points'] ?></td>
                        <td class="p-2 border text-center"><?= $d['total_score'] ?></td>
                        <td class="p-2 border text-center">
                            <?php if (!empty($d['rounds'])): ?>
                                <?php foreach ($d['rounds'] as $round_id => $rdetail): ?>
                                    <a href="<?= BASE_URL ?>/live-score/<?= $comp['public_link_slug'] ?>?round_id=<?= $round_id ?>"
                                       class="text-blue-600 hover:underline text-xs"
                                       target="_blank">
                                       <?= htmlspecialchars($rdetail['round_name']) ?><br>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-gray-500">Belum ada data untuk kategori ini.</p>
    <?php endif; ?>

    <h3 class="text-xl font-semibold mb-2">Klub (Total Seluruh Kategori)</h3>
    <?php if (!empty($clubStandings)): ?>
    <table class="w-full bg-white shadow rounded">
        <thead>
            <tr>
                <th class="p-2 border">#</th>
                <th class="p-2 border">Klub</th>
                <th class="p-2 border">Total Poin</th>
            </tr>
        </thead>
        <tbody>
            <?php $rank = 1; foreach ($clubStandings as $club => $pts): ?>
                <tr>
                    <td class="p-2 border text-center"><?= $rank++ ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($club) ?></td>
                    <td class="p-2 border text-center font-bold"><?= $pts ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p class="text-gray-500">Belum ada data klub.</p>
    <?php endif; ?>

    <div class="mt-4">
        <a href="<?= BASE_URL ?>/live-score/<?= $comp['public_link_slug'] ?>" class="text-blue-600">&larr; Kembali</a>
    </div>
</div>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>