<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SwitchNex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8 space-y-6">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30">
                <i data-lucide="tv" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-slate-900">SwitchNex</h1>
                <p class="text-xs text-slate-500">Admin Control Panel</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Username</label>
                <input type="text" name="username" required autofocus placeholder="admin" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Password</label>
                <input type="password" name="password" required placeholder="••••••••" class="w-full px-4 py-3 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-indigo-500/25 transition">
                Sign In
            </button>
        </form>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>