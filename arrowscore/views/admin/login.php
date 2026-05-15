<?php ob_start(); ?>
<div class="max-w-md mx-auto mt-12 bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-4">Login Admin</h2>
    <?php if (isset($error)): ?>
        <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= $error ?></div>
    <?php endif; ?>
    <form method="post">
        <?= CSRF::getTokenField() ?>
        <div class="mb-4">
            <label class="block mb-1">Email</label>
            <input type="email" name="email" class="w-full border p-2 rounded" required>
        </div>
        <div class="mb-4">
            <label class="block mb-1">Password</label>
            <input type="password" name="password" class="w-full border p-2 rounded" required>
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700">Login</button>
    </form>
</div>
<?php $content = ob_get_clean(); require_once 'views/layouts/main.php'; ?>