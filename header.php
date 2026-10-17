<?php
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$formatConfig = $pdo->query("SELECT * FROM sn_formats WHERE id = 1")->fetch();

function navClass($file, $current) {
    return $file === $current
        ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/30'
        : 'text-slate-300 hover:bg-slate-800';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — SwitchNex</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .mono { font-family: 'JetBrains Mono', monospace; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .animate-view { animation: fadeIn 0.2s ease-out forwards; }
    </style>
</head>
<body class="min-h-screen flex flex-col lg:flex-row text-slate-800">

    <!-- SIDEBAR -->
    <aside class="w-full lg:w-64 bg-slate-900 text-white flex flex-col justify-between shrink-0 z-20">
        <div>
            <div class="p-5 flex items-center gap-3 border-b border-slate-800">
                <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center shadow-lg">
                    <i data-lucide="tv" class="w-5 h-5 text-white"></i>
                </div>
                <div>
                    <h1 class="text-lg font-extrabold tracking-tight"><a href="index.php">SwitchNex</a></h1>
                    <p class="text-[11px] text-slate-400">Inventory & Warranty</p>
                </div>
            </div>

            <nav class="p-3 space-y-1.5">
                <a href="index.php" class="<?= navClass('index.php', $currentPage) ?> w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="<?= navClass('products.php', $currentPage) ?> w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition">
                    <i data-lucide="package" class="w-4 h-4"></i>
                    <span>Products</span>
                </a>
                <a href="generator.php" class="<?= navClass('generator.php', $currentPage) ?> w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition">
                    <i data-lucide="cpu" class="w-4 h-4"></i>
                    <span>Generate Serials</span>
                </a>
                <a href="print.php" class="<?= navClass('print.php', $currentPage) ?> w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition">
                    <i data-lucide="printer" class="w-4 h-4"></i>
                    <span>Print Labels</span>
                </a>
                <a href="sales.php" class="<?= navClass('sales.php', $currentPage) ?> w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    <span>Sales & Warranty</span>
                </a>
                <a href="format.php" class="<?= navClass('format.php', $currentPage) ?> w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition">
                    <i data-lucide="sliders" class="w-4 h-4"></i>
                    <span>Serial Format</span>
                </a>
                <a href="backup.php" class="<?= navClass('backup.php', $currentPage) ?> w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition">
                    <i data-lucide="database-backup" class="w-4 h-4"></i>
                    <span>Backup & Restore</span>
                </a>
            </nav>
        </div>

        <div class="p-3 m-3 rounded-xl bg-slate-800/90 border border-slate-700/70 flex items-center justify-between">
            <div class="truncate">
                <div class="text-xs font-bold text-white truncate"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></div>
                <div class="text-[11px] text-emerald-400">Online</div>
            </div>
            <a href="logout.php" title="Logout" class="p-2 rounded-lg bg-slate-700/60 hover:bg-red-600 text-slate-300 hover:text-white transition">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col min-w-0">
        <!-- TOP HEADER WITH GLOBAL SEARCH -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-4 sticky top-0 z-10">
            <h2 class="text-xl font-extrabold text-slate-900"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>

            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                <input type="text" id="global_search_input" oninput="handleGlobalSearch(this.value)" placeholder="Search Serial No, Customer, Mobile, Dealer..." class="w-full pl-10 pr-8 py-2 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                <button onclick="clearGlobalSearch()" id="global_search_clear" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
                <div id="global_search_dropdown" class="hidden absolute left-0 right-0 mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200 max-h-96 overflow-y-auto z-50 divide-y divide-slate-100"></div>
            </div>
        </header>

        <div class="p-6 lg:p-8 space-y-6 flex-1 overflow-y-auto animate-view">
            <?php if (!empty($_SESSION['flash_msg'])): ?>
                <div class="p-4 rounded-xl border flex items-center justify-between <?= ($_SESSION['flash_type'] ?? 'success') === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800' ?>">
                    <div class="flex items-center gap-2.5 text-sm font-semibold">
                        <i data-lucide="<?= ($_SESSION['flash_type'] ?? 'success') === 'error' ? 'alert-circle' : 'check-circle-2' ?>" class="w-5 h-5"></i>
                        <span><?= htmlspecialchars($_SESSION['flash_msg']) ?></span>
                    </div>
                </div>
                <?php unset($_SESSION['flash_msg'], $_SESSION['flash_type']); ?>
            <?php endif; ?>