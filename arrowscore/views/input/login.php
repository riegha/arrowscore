<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Skor - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-6 rounded shadow w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Masukkan Password Input</h2>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= $error ?></div>
        <?php endif; ?>
        <form method="post">
            <?= $csrfTokenField ?? '' ?>
            <div class="mb-4">
                <label class="block mb-1">Password</label>
                <input type="password" name="password" class="w-full border p-2 rounded" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded">Masuk</button>
        </form>
    </div>
</body>
</html>