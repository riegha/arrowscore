<?php ob_start(); ?>
<div class="max-w-md mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-xl font-bold mb-4">Buat Kompetisi Baru</h2>
    <form method="post">
        <?= CSRF::getTokenField() ?>
        <div class="mb-4">
            <label class="block mb-1">Nama Kompetisi</label>
            <input type="text" name="name" class="w-full border p-2 rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1">Tipe</label>
            <select name="type" class="w-full border p-2 rounded">
                <option value="single_event">Single Event</option>
                <option value="series">Series</option>
            </select>
        </div>
        <button type="submit" class="w-full bg-green-600 text-white p-2 rounded">Simpan</button>
    </form>
</div>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>