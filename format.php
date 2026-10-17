<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_format') {
    $prefix = strtoupper(trim($_POST['prefix']));
    $pattern = strtoupper(trim($_POST['pattern']));
    $pdo->prepare("UPDATE sn_formats SET prefix = ?, pattern = ? WHERE id = 1")->execute([$prefix, $pattern]);
    setFlash("Serial format updated.");
    header("Location: format.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $newPass = $_POST['new_password'] ?? '';
    if (strlen($newPass) >= 6) {
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")->execute([$hash, $_SESSION['admin_id']]);
        setFlash("Admin password updated successfully.");
    } else {
        setFlash("Password must be at least 6 characters.", "error");
    }
    header("Location: format.php");
    exit;
}

$pageTitle = 'Serial Format & Settings';
require_once __DIR__ . '/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Serial Format Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Serial Number Format</h3>
            <button type="button" onclick="resetDefaultFormat()" class="px-3 py-1.5 rounded-lg bg-slate-100 text-xs font-bold text-slate-700">Reset Default</button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="save_format">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Prefix</label>
                    <input type="text" name="prefix" id="fmt_prefix" value="<?= htmlspecialchars($formatConfig['prefix']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 mono font-bold text-sm uppercase">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Pattern</label>
                    <input type="text" name="pattern" id="fmt_pattern" value="<?= htmlspecialchars($formatConfig['pattern']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 mono font-bold text-sm text-indigo-700">
                </div>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach (['{PREFIX}','{SIZE}','{MM}','{YY}','{RAND5}','{MODEL}'] as $tk): ?>
                    <button type="button" onclick="document.getElementById('fmt_pattern').value+='<?= $tk ?>'" class="px-2.5 py-1 rounded bg-slate-100 text-xs mono font-bold"><?= $tk ?></button>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl">Save Format</button>
        </form>
    </div>

    <!-- Admin Security Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-5 h-fit">
        <h3 class="text-base font-bold text-slate-900">Change Admin Password</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="change_password">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">New Password *</label>
                <input type="password" name="new_password" required minlength="6" placeholder="Minimum 6 characters" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm rounded-xl">Update Password</button>
        </form>
    </div>
</div>

<script>
function resetDefaultFormat() {
    document.getElementById('fmt_prefix').value = 'SN';
    document.getElementById('fmt_pattern').value = '{PREFIX}{SIZE}{MM}{YY}{RAND5}';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>