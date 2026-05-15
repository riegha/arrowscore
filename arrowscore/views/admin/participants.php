<?php ob_start(); ?>
<h2 class="text-2xl font-bold mb-4">Manajemen Peserta - <?= htmlspecialchars($competition['name']) ?></h2>

<div class="flex gap-2 mb-4">
    <a href="<?= BASE_URL ?>/admin/participants/<?= $competition['id'] ?>/add" class="bg-blue-600 text-white px-3 py-2 rounded">+ Tambah Manual</a>
    <a href="<?= BASE_URL ?>/admin/participants/<?= $competition['id'] ?>/import" class="bg-green-600 text-white px-3 py-2 rounded">Import CSV</a>
    <a href="<?= BASE_URL ?>/admin/dashboard" class="bg-gray-500 text-white px-3 py-2 rounded">Kembali</a>
</div>

<form id="batch-delete-form" method="post" action="<?= BASE_URL ?>/admin/participants/<?= $competition['id'] ?>/delete-batch">
    <?= CSRF::getTokenField() ?>
    <div class="mb-3">
        <button type="submit" class="bg-red-600 text-white px-3 py-2 rounded" onclick="return confirm('Hapus peserta terpilih dari kompetisi ini?')">
            Hapus Peserta Terpilih
        </button>
    </div>

    <table class="w-full bg-white shadow rounded">
        <thead>
            <tr>
                <th class="p-2 border"><input type="checkbox" id="check-all"></th>
                <th class="p-2 border">Nama</th>
                <th class="p-2 border">Klub</th>
                <th class="p-2 border">Jenis Kelamin</th>
                <th class="p-2 border">Tanggal Lahir</th>
                <th class="p-2 border">Alamat</th>
                <th class="p-2 border">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($participants)): ?>
                <tr><td colspan="7" class="p-4 text-center text-gray-500">Belum ada peserta terdaftar di kompetisi ini.</td></tr>
            <?php else: ?>
                <?php foreach ($participants as $p): ?>
                <tr>
                    <td class="p-2 border text-center">
                        <input type="checkbox" name="participant_ids[]" value="<?= $p['id'] ?>" class="participant-checkbox">
                    </td>
                    <td class="p-2 border"><?= htmlspecialchars($p['name']) ?></td>
                    <td class="p-2 border"><?= $p['club_name'] ?? '-' ?></td>
                    <td class="p-2 border"><?= !empty($p['gender']) ? htmlspecialchars($p['gender']) : '-' ?></td>
                    <td class="p-2 border"><?= $p['birth_date'] ?></td>
                    <td class="p-2 border"><?= htmlspecialchars($p['address'] ?? '') ?></td>
                    <td class="p-2 border">
                        <a href="<?= BASE_URL ?>/admin/participants/edit/<?= $p['id'] ?>?competition_id=<?= $competition['id'] ?>" class="text-blue-600 hover:underline mr-2">Edit</a>
                        <a href="<?= BASE_URL ?>/admin/participants/<?= $competition['id'] ?>/delete/<?= $p['id'] ?>" class="text-red-600 hover:underline" onclick="return confirm('Hapus peserta ini dari kompetisi?')">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</form>

<script>
    document.getElementById('check-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.participant-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>