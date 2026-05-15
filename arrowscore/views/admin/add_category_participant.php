<?php ob_start(); ?>
<h2 class="text-xl font-bold mb-4">Tambah Peserta ke Kategori: <?= htmlspecialchars($category['name']) ?></h2>

<form method="post" class="bg-white p-4 rounded shadow space-y-3 max-w-lg">
<?= CSRF::getTokenField() ?>
    <div>
        <label class="block mb-1">Pilih Peserta Existing (opsional)</label>
        <select name="existing_participant" class="w-full border p-2 rounded">
            <option value="">-- Buat Baru --</option>
            <?php foreach ($allParticipants as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['club_name'] ?? '-' ?>)</option>
            <?php endforeach; ?>
        </select>
        <p class="text-sm text-gray-500 mt-1">Biarkan kosong jika ingin menambahkan peserta baru.</p>
    </div>

    <hr class="my-3">

    <p class="text-sm text-gray-500">Atau isi data peserta baru di bawah:</p>
    <div>
        <label class="block mb-1">Nama Lengkap</label>
        <input type="text" name="name" class="w-full border p-2 rounded" placeholder="Nama peserta">
    </div>
    <div>
        <label class="block mb-1">Klub</label>
        <input type="text" name="club_name" class="w-full border p-2 rounded" placeholder="Ketik nama klub (kosongkan jika perorangan)" list="club-list">
        <datalist id="club-list">
            <?php foreach ($clubs as $club): ?>
                <option value="<?= htmlspecialchars($club['name']) ?>">
            <?php endforeach; ?>
        </datalist>
        <p class="text-sm text-gray-500 mt-1">Pilih dari daftar atau ketik nama klub baru.</p>
    </div>
    <div>
        <label class="block mb-1">Tanggal Lahir</label>
        <input type="date" name="birth_date" class="w-full border p-2 rounded">
    </div>
    <div>
        <label class="block mb-1">Alamat</label>
        <textarea name="address" class="w-full border p-2 rounded" rows="2" placeholder="Alamat (opsional)"></textarea>
    </div>
    <div>
        <label class="block mb-1">Jenis Kelamin</label>
        <select name="gender" class="w-full border p-2 rounded">
            <option value="">-- Pilih --</option>
            <option value="laki-laki">Laki‑laki</option>
            <option value="perempuan">Perempuan</option>
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
        <a href="<?= BASE_URL ?>/cp/<?= $category['id'] ?>" class="bg-gray-300 text-gray-700 px-4 py-2 rounded">Batal</a>
    </div>
</form>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>