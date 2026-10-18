<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_sale') {
    $serial_no        = strtoupper(trim($_POST['serial_no']));
    $customer_name    = trim($_POST['customer_name']);
    $customer_mobile  = trim($_POST['customer_mobile']);
    $customer_address = trim($_POST['customer_address'] ?? '');
    $dealer_name      = trim($_POST['dealer_name']);
    $sale_date        = $_POST['sale_date'];
    $warranty_months  = (int)$_POST['warranty_months'];

    $stmt = $pdo->prepare("SELECT id, is_sold FROM serial_numbers WHERE serial_no = ?");
    $stmt->execute([$serial_no]);
    $snRow = $stmt->fetch();

    if (!$snRow) {
        setFlash("Serial number $serial_no does not exist.", "error");
    } elseif ($snRow['is_sold'] == 1) {
        setFlash("Serial number $serial_no is already registered as sold.", "error");
    } else {
        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare("INSERT INTO sales (serial_id, customer_name, customer_mobile, customer_address, dealer_name, sale_date, warranty_months) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([$snRow['id'], $customer_name, $customer_mobile, $customer_address, $dealer_name, $sale_date, $warranty_months]);

            $upd = $pdo->prepare("UPDATE serial_numbers SET is_sold = 1, sold_at = NOW() WHERE id = ?");
            $upd->execute([$snRow['id']]);

            $pdo->commit();
            setFlash("Sale registered for $serial_no.");
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlash("Error saving sale record.", "error");
        }
    }
    header("Location: sales.php");
    exit;
}

if (isset($_GET['delete_sale'])) {
    $sale_id = (int)$_GET['delete_sale'];
    $stmt = $pdo->prepare("SELECT serial_id FROM sales WHERE id = ?");
    $stmt->execute([$sale_id]);
    $row = $stmt->fetch();
    if ($row) {
        $pdo->prepare("DELETE FROM sales WHERE id = ?")->execute([$sale_id]);
        $pdo->prepare("UPDATE serial_numbers SET is_sold = 0, sold_at = NULL WHERE id = ?")->execute([$row['serial_id']]);
        setFlash("Sale record removed and unit reverted to In Stock.");
    }
    header("Location: sales.php");
    exit;
}

$pageTitle = 'Sales & Warranty';
require_once __DIR__ . '/header.php';

$prefillSerial = $_GET['serial_no'] ?? '';
$unsoldSerials = $pdo->query("
    SELECT s.serial_no, p.product_name, p.model_name
    FROM serial_numbers s
    JOIN products p ON s.product_id = p.id
    WHERE s.is_sold = 0
    ORDER BY s.id DESC LIMIT 500
")->fetchAll();

$salesRecords = $pdo->query("
    SELECT sa.*, s.serial_no, p.product_name, p.product_type, p.model_name,
           DATE_ADD(sa.sale_date, INTERVAL sa.warranty_months MONTH) AS warranty_expiry
    FROM sales sa
    JOIN serial_numbers s ON sa.serial_id = s.id
    JOIN products p ON s.product_id = p.id
    ORDER BY sa.id DESC LIMIT 200
")->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Register Sale Form -->
    <div class="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm h-fit">
        <h3 class="text-base font-bold text-slate-900 mb-4">Register Product Sale</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="register_sale">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Serial Number (Unsold) *</label>
                <input type="text" name="serial_no" value="<?= htmlspecialchars($prefillSerial) ?>" list="unsold_serials_list" required placeholder="Select or scan serial..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm mono uppercase outline-none focus:ring-2 focus:ring-indigo-500">
                <datalist id="unsold_serials_list">
                    <?php foreach ($unsoldSerials as $u): ?>
                        <option value="<?= htmlspecialchars($u['serial_no']) ?>"><?= htmlspecialchars($u['model_name']) ?> — <?= htmlspecialchars($u['product_name']) ?></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Customer Name *</label>
                <input type="text" name="customer_name" required placeholder="Full Name" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Mobile Number *</label>
                <input type="tel" name="customer_mobile" required placeholder="10-digit mobile" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm mono outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Customer Address *</label>
                <textarea name="customer_address" rows="2" required placeholder="House No, Street, City, Pin Code" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Dealer Name *</label>
                <input type="text" name="dealer_name" required placeholder="Selling Dealer / Store" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Date of Sale *</label>
                    <input type="date" name="sale_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Warranty *</label>
                    <select name="warranty_months" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold bg-white">
                        <option value="12">12 Months (1 Yr)</option>
                        <option value="24">24 Months (2 Yrs)</option>
                        <option value="36">36 Months (3 Yrs)</option>
                        <option value="6">6 Months</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl shadow transition">Register Sale & Activate Warranty</button>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Registered Sales & Warranty</h3>
            <span class="text-xs font-bold text-emerald-600"><?= count($salesRecords) ?> Sold</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <th class="py-3 px-4 font-semibold">Serial & Model</th>
                        <th class="py-3 px-4 font-semibold">Customer & Address</th>
                        <th class="py-3 px-4 font-semibold">Dealer</th>
                        <th class="py-3 px-4 font-semibold">Sale Date</th>
                        <th class="py-3 px-4 font-semibold">Warranty Status</th>
                        <th class="py-3 px-4 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php if (empty($salesRecords)): ?>
                        <tr><td colspan="6" class="py-8 text-center text-slate-400">No sales registered yet.</td></tr>
                    <?php else: foreach ($salesRecords as $sr):
                        $isExpired = strtotime($sr['warranty_expiry']) < time();
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-4">
                                <div class="mono font-bold text-xs text-slate-900"><?= htmlspecialchars($sr['serial_no']) ?></div>
                                <div class="text-xs text-slate-500"><?= htmlspecialchars($sr['model_name']) ?> (<?= htmlspecialchars($sr['product_type']) ?>)</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800"><?= htmlspecialchars($sr['customer_name']) ?></div>
                                <div class="mono text-xs text-indigo-600"><?= htmlspecialchars($sr['customer_mobile']) ?></div>
                                <?php if (!empty($sr['customer_address'])): ?>
                                    <div class="text-xs text-slate-500 mt-0.5 max-w-xs truncate" title="<?= htmlspecialchars($sr['customer_address']) ?>">
                                        <?= htmlspecialchars($sr['customer_address']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-slate-700"><?= htmlspecialchars($sr['dealer_name']) ?></td>
                            <td class="py-3.5 px-4 text-xs text-slate-600"><?= htmlspecialchars($sr['sale_date']) ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $isExpired ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' ?>">
                                    <?= $isExpired ? 'Expired' : 'Active' ?> (Exp: <?= htmlspecialchars($sr['warranty_expiry']) ?>)
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="?delete_sale=<?= $sr['id'] ?>" onclick="return confirm('Remove sale record and mark unit as In Stock?')" class="text-xs font-bold text-red-600 hover:text-red-800">Revert</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>