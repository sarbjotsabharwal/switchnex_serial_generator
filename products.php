<?php
require_once __DIR__ . '/auth.php';

// Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_product') {
    $p_name = trim($_POST['product_name']);
    $p_type = trim($_POST['product_type']);
    $m_name = strtoupper(trim($_POST['model_name']));
    preg_match('/(\d+)/', $p_type, $m);
    $size_code = trim($_POST['size_code']) !== '' ? trim($_POST['size_code']) : ($m[1] ?? '00');

    try {
        $stmt = $pdo->prepare("INSERT INTO products (product_name, product_type, size_code, model_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$p_name, $p_type, $size_code, $m_name]);
        setFlash("Product added successfully.");
    } catch (PDOException $e) {
        setFlash("Product linkage already exists.", "error");
    }
    header("Location: products.php");
    exit;
}

// Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_product') {
    $id = (int)$_POST['product_id'];
    $p_name = trim($_POST['product_name']);
    $p_type = trim($_POST['product_type']);
    $size_code = trim($_POST['size_code']);
    $m_name = strtoupper(trim($_POST['model_name']));

    try {
        $stmt = $pdo->prepare("UPDATE products SET product_name = ?, product_type = ?, size_code = ?, model_name = ? WHERE id = ?");
        $stmt->execute([$p_name, $p_type, $size_code, $m_name, $id]);
        setFlash("Product updated successfully.");
    } catch (PDOException $e) {
        setFlash("Update failed: duplicate model linkage.", "error");
    }
    header("Location: products.php");
    exit;
}

// Delete Product
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([(int)$_GET['delete']]);
    setFlash("Product deleted.");
    header("Location: products.php");
    exit;
}

$pageTitle = 'Products';
require_once __DIR__ . '/header.php';
$products = $pdo->query("SELECT * FROM products ORDER BY product_name ASC, size_code ASC")->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Add Form -->
    <div class="lg:col-span-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm h-fit">
        <h3 class="text-base font-bold text-slate-900 mb-4">Add Product Model</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_product">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Product Name *</label>
                <input type="text" name="product_name" required placeholder="LED TV" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Type / Size *</label>
                    <input type="text" name="product_type" oninput="autoExtractSizeCode(this.value, 'add_size_code')" required placeholder="43 Inch" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Code *</label>
                    <input type="text" name="size_code" id="add_size_code" required placeholder="43" maxlength="4" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm mono font-bold text-indigo-600 bg-slate-50 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Model Name *</label>
                <input type="text" name="model_name" required placeholder="SN4300" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm mono uppercase outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition">Save Product</button>
        </form>
    </div>

    <!-- Products Table -->
    <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Product Models</h3>
            <span class="text-xs font-bold text-slate-500"><?= count($products) ?> Models</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase">
                        <th class="py-3 px-5 font-semibold">Product Name</th>
                        <th class="py-3 px-5 font-semibold">Type</th>
                        <th class="py-3 px-5 font-semibold">Size Code</th>
                        <th class="py-3 px-5 font-semibold">Model Name</th>
                        <th class="py-3 px-5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    <?php foreach ($products as $p): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-5 font-bold text-slate-800"><?= htmlspecialchars($p['product_name']) ?></td>
                            <td class="py-3.5 px-5 text-slate-600"><?= htmlspecialchars($p['product_type']) ?></td>
                            <td class="py-3.5 px-5 mono font-bold text-xs"><?= htmlspecialchars($p['size_code']) ?></td>
                            <td class="py-3.5 px-5"><span class="mono text-xs font-bold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-md"><?= htmlspecialchars($p['model_name']) ?></span></td>
                            <td class="py-3.5 px-5 text-right space-x-3">
                                <button onclick='openEditModal(<?= json_encode($p) ?>)' class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Edit</button>
                                <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this product?')" class="text-xs font-bold text-red-600 hover:text-red-800">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="edit_product_modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 animate-view">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900">Edit Product</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" name="product_id" id="edit_product_id">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Product Name *</label>
                <input type="text" name="product_name" id="edit_product_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Type / Size *</label>
                    <input type="text" name="product_type" id="edit_product_type" oninput="autoExtractSizeCode(this.value, 'edit_size_code')" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Code *</label>
                    <input type="text" name="size_code" id="edit_size_code" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm mono font-bold text-indigo-600">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Model Name *</label>
                <input type="text" name="model_name" id="edit_model_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm mono uppercase">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-700">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold">Update Product</button>
            </div>
        </form>
    </div>
</div>

<script>
function autoExtractSizeCode(val, targetId) {
    const m = val.match(/(\d+)/);
    if (m) document.getElementById(targetId).value = m[1];
}
function openEditModal(p) {
    document.getElementById('edit_product_id').value = p.id;
    document.getElementById('edit_product_name').value = p.product_name;
    document.getElementById('edit_product_type').value = p.product_type;
    document.getElementById('edit_size_code').value = p.size_code;
    document.getElementById('edit_model_name').value = p.model_name;
    const modal = document.getElementById('edit_product_modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeEditModal() {
    const modal = document.getElementById('edit_product_modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>