<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate_serials') {
    $product_id = (int)$_POST['product_id'];
    $qty = max(1, min(1000, (int)$_POST['qty']));
    
    $stmt =$pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product =$stmt->fetch();
    $format =$pdo->query("SELECT * FROM sn_formats WHERE id = 1")->fetch();
    
    if ($product && $format) {$batch_code = 'BATCH-' . date('Ymd-His');
        $inserted = 0;
        $attempts = 0;
        $checkStmt =$pdo->prepare("SELECT COUNT(*) FROM serial_numbers WHERE serial_no = ?");
        $insertStmt =$pdo->prepare("INSERT INTO serial_numbers (batch_code, product_id, serial_no) VALUES (?, ?, ?)");
        
        $pdo->beginTransaction();
        try {
            while ($inserted <$qty && $attempts < ($qty * 25)) {
                $attempts++;$sn = generateSerialFromPattern($format['pattern'],$format['prefix'], $product['size_code'],$product['model_name']);
                $checkStmt->execute([$sn]);
                if ($checkStmt->fetchColumn() == 0) {$insertStmt->execute([$batch_code,$product_id, $sn]);$inserted++;
                }
            }
            $pdo->commit();
            setFlash("$inserted serial numbers generated for {$product['model_name']} ($batch_code).");
            header("Location: print.php?batch=" . urlencode($batch_code));
            exit;
        } catch (Exception $e) {$pdo->rollBack();
            setFlash("Generation failed.", "error");
        }
    }
}

$pageTitle = 'Generate Serials';
require_once __DIR__ . '/header.php';
$products =$pdo->query("SELECT * FROM products ORDER BY product_name ASC, size_code ASC, model_name ASC")->fetchAll();
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <h3 class="text-base font-bold text-slate-900 mb-5">Generate Serial Numbers</h3>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="generate_serials">

            <!-- 1. Product Name -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Product Name *</label>
                <select id="gen_product_name" onchange="onProductNameChange()" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold bg-white outline-none focus:ring-2 focus:ring-indigo-500"></select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- 2. Product Type (Grouped uniquely per Product Name) -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Product Type / Size *</label>
                    <select id="gen_product_type" onchange="onProductTypeChange()" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold bg-white outline-none focus:ring-2 focus:ring-indigo-500"></select>
                </div>

                <!-- 3. Linked Model Name Dropdown (Supports multiple models per type) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold uppercase text-slate-600">Model Name *</label>
                        <span id="model_count_badge" class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700"></span>
                    </div>
                    <select name="product_id" id="gen_model_select" onchange="onModelChange()" required class="w-full px-3.5 py-2.5 rounded-xl border border-indigo-300 bg-indigo-50/40 text-indigo-800 mono font-bold text-sm outline-none focus:ring-2 focus:ring-indigo-500"></select>
                </div>
            </div>

            <!-- 4. Quantity -->
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Quantity *</label>
                <div class="flex items-center gap-2">
                    <input type="number" name="qty" id="gen_qty" value="10" min="1" max="1000" required class="w-36 px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-extrabold outline-none focus:ring-2 focus:ring-indigo-500">
                    <button type="button" onclick="document.getElementById('gen_qty').value=10" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold">10</button>
                    <button type="button" onclick="document.getElementById('gen_qty').value=25" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold">25</button>
                    <button type="button" onclick="document.getElementById('gen_qty').value=50" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold">50</button>
                    <button type="button" onclick="document.getElementById('gen_qty').value=100" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold">100</button>
                </div>
            </div>

            <button type="submit" class="w-full py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow transition">
                Generate Serials & Open Print Studio
            </button>
        </form>
    </div>

    <!-- Live Label Preview -->
    <div class="lg:col-span-5 bg-slate-900 text-white p-6 rounded-2xl shadow-lg flex flex-col items-center">
        <div class="w-full flex justify-between items-center mb-4">
            <span class="text-xs font-bold uppercase text-slate-400">Label Preview (50mm × 25mm)</span>
            <span class="mono text-xs text-amber-400 font-bold" id="live_sn_sample"></span>
        </div>
        <div class="w-[300px] h-[150px] bg-white rounded-lg p-3 flex flex-col justify-between items-center text-black shadow-md">
            <div class="w-full text-left pl-1 font-extrabold text-sm uppercase leading-none" id="preview_label_model">SN3200</div>
            <svg id="generator_preview_barcode" class="w-full h-20"></svg>
            <div class="mono font-extrabold text-sm tracking-widest leading-none" id="preview_label_sn"></div>
        </div>
    </div>
</div>

<script>
const PRODUCTS_DATA = <?= json_encode($products) ?>;
const ACTIVE_FORMAT = <?= json_encode($formatConfig) ?>;
const CURRENT_MM = "<?= date('m') ?>";
const CURRENT_YY = "<?= date('y') ?>";
const CURRENT_YYYY = "<?= date('Y') ?>";

// Step 1: Populate unique Product Names (e.g., LED TV, Washing Machine)
function initGeneratorDropdowns() {
    const names = [...new Set(PRODUCTS_DATA.map(p => p.product_name))];
    const sel = document.getElementById('gen_product_name');
    sel.innerHTML = names.map(n => `<option value="${n}">${n}</option>`).join('');
    if (names.length) onProductNameChange();
}

// Step 2: Populate unique Product Types for selected Product Name (e.g., 32 Inch, 43 Inch)
function onProductNameChange() {
    const selectedName = document.getElementById('gen_product_name').value;
    const matchingProducts = PRODUCTS_DATA.filter(p => p.product_name === selectedName);
    const uniqueTypes = [...new Set(matchingProducts.map(p => p.product_type))];

    const typeSel = document.getElementById('gen_product_type');
    typeSel.innerHTML = uniqueTypes.map(t => `<option value="${t}">${t}</option>`).join('');
    if (uniqueTypes.length) onProductTypeChange();
}

// Step 3: Populate all linked Models for selected Product Name + Product Type
function onProductTypeChange() {
    const selectedName = document.getElementById('gen_product_name').value;
    const selectedType = document.getElementById('gen_product_type').value;

    const matchingModels = PRODUCTS_DATA.filter(
        p => p.product_name === selectedName && p.product_type === selectedType
    );

    const modelSel = document.getElementById('gen_model_select');
    modelSel.innerHTML = matchingModels.map(p => `<option value="${p.id}">${p.model_name}</option>`).join('');

    const badge = document.getElementById('model_count_badge');
    badge.textContent = matchingModels.length > 1 ? `${matchingModels.length} Models Available` : '1 Linked Model';

    if (matchingModels.length) onModelChange();
}

// Step 4: Update preview when specific Model is chosen
function onModelChange() {
    const productId = document.getElementById('gen_model_select').value;
    const product = PRODUCTS_DATA.find(x => String(x.id) === String(productId));
    if (product) {
        updateLiveGeneratorPreview(product);
    }
}

function buildPreviewSerial(pattern, prefix, sizeCode, modelName) {
    return pattern
        .replace(/\{PREFIX\}/g, prefix)
        .replace(/\{SIZE\}/g, sizeCode)
        .replace(/\{MODEL\}/g, modelName)
        .replace(/\{MM\}/g, CURRENT_MM)
        .replace(/\{YY\}/g, CURRENT_YY)
        .replace(/\{YYYY\}/g, CURRENT_YYYY)
        .replace(/\{RAND(\d+)\}/g, (_, l) => String(Math.floor(Math.pow(10, l - 1) + Math.random() * 9 * Math.pow(10, l - 1))));
}

function updateLiveGeneratorPreview(p) {
    if (!p) p = PRODUCTS_DATA[0] || { size_code: '32', model_name: 'SN3200' };
    const sn = buildPreviewSerial(ACTIVE_FORMAT.pattern, ACTIVE_FORMAT.prefix, p.size_code, p.model_name);
    document.getElementById('live_sn_sample').textContent = sn;
    document.getElementById('preview_label_model').textContent = p.model_name;
    document.getElementById('preview_label_sn').textContent = sn;
    JsBarcode("#generator_preview_barcode", sn, { format: "CODE128", displayValue: false, margin: 0, width: 2, height: 55 });
}

document.addEventListener('DOMContentLoaded', initGeneratorDropdowns);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>