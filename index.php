<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/header.php';

$totalProducts  = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalSerials   = (int)$pdo->query("SELECT COUNT(*) FROM serial_numbers")->fetchColumn();
$printedSerials = (int)$pdo->query("SELECT COUNT(*) FROM serial_numbers WHERE is_printed = 1")->fetchColumn();
$soldSerials    = (int)$pdo->query("SELECT COUNT(*) FROM serial_numbers WHERE is_sold = 1")->fetchColumn();
$inStockSerials = $totalSerials - $soldSerials;

// Filter parameters
$pFilter = $_GET['printed'] ?? 'ALL';
$sFilter = $_GET['sold'] ?? 'ALL';

$where = [];
$params = [];
if ($pFilter === '1' || $pFilter === '0') {
    $where[] = "s.is_printed = ?";
    $params[] = (int)$pFilter;
}
if ($sFilter === '1' || $sFilter === '0') {
    $where[] = "s.is_sold = ?";
    $params[] = (int)$sFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT s.id, s.serial_no, s.batch_code, s.is_printed, s.is_sold, s.created_at,
           p.product_name, p.product_type, p.model_name,
           sa.customer_name, sa.customer_mobile, sa.dealer_name
    FROM serial_numbers s
    JOIN products p ON s.product_id = p.id
    LEFT JOIN sales sa ON s.id = sa.serial_id
    $whereSql
    ORDER BY s.id DESC LIMIT 150
");
$stmt->execute($params);
$serials = $stmt->fetchAll();
?>

<!-- KPI Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase text-slate-400">Total Serials</p>
            <h3 class="text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($totalSerials) ?></h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
            <i data-lucide="barcode" class="w-6 h-6"></i>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase text-slate-400">Labels Printed</p>
            <h3 class="text-2xl font-extrabold text-blue-600 mt-1"><?= number_format($printedSerials) ?></h3>
            <p class="text-[11px] text-slate-400 mt-0.5">Not Printed: <?= number_format($totalSerials - $printedSerials) ?></p>
        </div>
        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
            <i data-lucide="printer" class="w-6 h-6"></i>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase text-slate-400">Units Sold</p>
            <h3 class="text-2xl font-extrabold text-emerald-600 mt-1"><?= number_format($soldSerials) ?></h3>
            <p class="text-[11px] text-slate-400 mt-0.5">In Stock: <?= number_format($inStockSerials) ?></p>
        </div>
        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <i data-lucide="shield-check" class="w-6 h-6"></i>
        </div>
    </div>

    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs font-bold uppercase text-slate-400">Product Models</p>
            <h3 class="text-2xl font-extrabold text-slate-900 mt-1"><?= number_format($totalProducts) ?></h3>
        </div>
        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
            <i data-lucide="package" class="w-6 h-6"></i>
        </div>
    </div>
</div>

<!-- Serial Inventory Table -->
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h3 class="text-base font-bold text-slate-900">Serial Number Inventory</h3>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <select name="printed" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold bg-white">
                <option value="ALL" <?= $pFilter === 'ALL' ? 'selected' : '' ?>>Print Status: All</option>
                <option value="1" <?= $pFilter === '1' ? 'selected' : '' ?>>Printed</option>
                <option value="0" <?= $pFilter === '0' ? 'selected' : '' ?>>Not Printed</option>
            </select>
            <select name="sold" onchange="this.form.submit()" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-semibold bg-white">
                <option value="ALL" <?= $sFilter === 'ALL' ? 'selected' : '' ?>>Sale Status: All</option>
                <option value="1" <?= $sFilter === '1' ? 'selected' : '' ?>>Sold</option>
                <option value="0" <?= $sFilter === '0' ? 'selected' : '' ?>>In Stock</option>
            </select>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-500 text-xs uppercase">
                    <th class="py-3 px-5 font-semibold">Serial Number</th>
                    <th class="py-3 px-5 font-semibold">Product</th>
                    <th class="py-3 px-5 font-semibold">Model</th>
                    <th class="py-3 px-5 font-semibold">Label Printed</th>
                    <th class="py-3 px-5 font-semibold">Sale Status</th>
                    <th class="py-3 px-5 font-semibold">Customer / Dealer</th>
                    <th class="py-3 px-5 font-semibold text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (empty($serials)): ?>
                    <tr><td colspan="7" class="py-8 text-center text-slate-400">No serial records found.</td></tr>
                <?php else: foreach ($serials as $r): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-5 mono font-bold text-xs text-slate-900"><?= htmlspecialchars($r['serial_no']) ?></td>
                        <td class="py-3 px-5 text-xs font-semibold text-slate-700"><?= htmlspecialchars($r['product_name']) ?> (<?= htmlspecialchars($r['product_type']) ?>)</td>
                        <td class="py-3 px-5"><span class="mono text-xs font-bold px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded"><?= htmlspecialchars($r['model_name']) ?></span></td>
                        <td class="py-3 px-5">
                            <?php if ($r['is_printed']): ?>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700">Printed</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-500">Not Printed</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-5">
                            <?php if ($r['is_sold']): ?>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700">Sold</span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-5">
                            <?php if ($r['is_sold']): ?>
                                <div class="font-bold text-slate-800 text-xs"><?= htmlspecialchars($r['customer_name']) ?> (<?= htmlspecialchars($r['customer_mobile']) ?>)</div>
                                <div class="text-[11px] text-slate-500">Dealer: <?= htmlspecialchars($r['dealer_name']) ?></div>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-5 text-right">
                            <?php if (!$r['is_sold']): ?>
                                <a href="sales.php?serial_no=<?= urlencode($r['serial_no']) ?>" class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold">Sell Unit</a>
                            <?php else: ?>
                                <button onclick="inspectSerial('<?= htmlspecialchars($r['serial_no']) ?>')" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold">Details</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>