<?php
$pageTitle = 'Print Labels (50mm × 25mm)';
require_once __DIR__ . '/header.php';

$selectedBatch =$_GET['batch'] ?? 'ALL';

$batches =$pdo->query("
    SELECT s.batch_code, COUNT(s.id) as count, p.model_name, MAX(s.created_at) as created_at
    FROM serial_numbers s
    JOIN products p ON s.product_id = p.id
    GROUP BY s.batch_code, p.model_name
    ORDER BY created_at DESC LIMIT 30
")->fetchAll();

$allSerials =$pdo->query("
    SELECT s.id, s.serial_no, s.batch_code, s.is_printed, s.is_sold,
           p.product_name, p.product_type, p.model_name
    FROM serial_numbers s
    JOIN products p ON s.product_id = p.id
    ORDER BY s.id DESC LIMIT 600
")->fetchAll();
?>

<div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
        <div class="flex items-center gap-2">
            <button onclick="selectAllVisibleLabels(true)" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold">Select All</button>
            <button onclick="selectAllVisibleLabels(false)" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-bold">Clear</button>
        </div>
        <button onclick="exportLabelsPDF()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow flex items-center gap-2">
            <i data-lucide="printer" class="w-4 h-4"></i>
            <span>Export 50×25mm PDF & Mark Printed (<span id="selected_count_badge">0</span>)</span>
        </button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div>
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Batch</label>
            <select id="filter_batch" onchange="applyPrintFilters()" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold bg-white">
                <option value="ALL">All Batches</option>
                <?php foreach ($batches as$b): ?>
                    <option value="<?= htmlspecialchars($b['batch_code']) ?>" <?= ($selectedBatch ===$b['batch_code']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['batch_code']) ?> (<?= htmlspecialchars($b['model_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Print Status</label>
            <select id="filter_print_status" onchange="applyPrintFilters()" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold bg-white">
                <option value="ALL">All</option>
                <option value="0">Not Printed Only</option>
                <option value="1">Printed Only</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">From Serial</label>
            <select id="filter_from_sn" onchange="applyPrintFilters()" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs mono font-semibold bg-white">
                <option value="">Start</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">To Serial</label>
            <select id="filter_to_sn" onchange="applyPrintFilters()" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs mono font-semibold bg-white">
                <option value="">End</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Search</label>
            <input type="text" id="filter_search" oninput="applyPrintFilters()" placeholder="Serial / Model..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs mono outline-none">
        </div>
    </div>
</div>

<div id="labels_grid_container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5"></div>

<script>
const SERIALS_DATA = <?= json_encode($allSerials) ?>;
let filteredSerials = [];
let selectedSerialIds = new Set();

function populateRangeDropdowns() {
    const fromSel = document.getElementById('filter_from_sn');
    const toSel = document.getElementById('filter_to_sn');
    const sorted = [...SERIALS_DATA].sort((a, b) => a.id - b.id);
    sorted.forEach(item => {
        fromSel.insertAdjacentHTML('beforeend', `<option value="${item.id}">${item.serial_no}</option>`);
        toSel.insertAdjacentHTML('beforeend', `<option value="${item.id}">${item.serial_no}</option>`);
    });
}

function applyPrintFilters() {
    const batchVal = document.getElementById('filter_batch').value;
    const printStatus = document.getElementById('filter_print_status').value;
    const fromId = parseInt(document.getElementById('filter_from_sn').value) || null;
    const toId = parseInt(document.getElementById('filter_to_sn').value) || null;
    const q = (document.getElementById('filter_search').value || '').trim().toUpperCase();

    filteredSerials = SERIALS_DATA.filter(item => {
        if (batchVal !== 'ALL' && item.batch_code !== batchVal) return false;
        if (printStatus !== 'ALL' && String(item.is_printed) !== printStatus) return false;
        if (fromId && item.id < Math.min(fromId, toId || Infinity)) return false;
        if (toId && item.id > Math.max(fromId || 0, toId)) return false;
        if (q && !item.serial_no.includes(q) && !item.model_name.includes(q)) return false;
        return true;
    });

    selectedSerialIds = new Set(filteredSerials.map(i => i.id));
    renderLabelCards();
}

function renderLabelCards() {
    const container = document.getElementById('labels_grid_container');
    container.innerHTML = '';

    if (filteredSerials.length === 0) {
        container.innerHTML = `<div class="col-span-full bg-white p-10 rounded-2xl border text-center text-slate-400 text-sm">No labels match the filter.</div>`;
        document.getElementById('selected_count_badge').textContent = '0';
        return;
    }

    filteredSerials.forEach(item => {
        const isSelected = selectedSerialIds.has(item.id);
        const card = document.createElement('div');
        card.className = `p-3.5 rounded-2xl bg-white border transition cursor-pointer flex flex-col items-center ${isSelected ? 'border-indigo-600 ring-2 ring-indigo-500/20' : 'border-slate-200 opacity-60'}`;
        card.onclick = () => {
            selectedSerialIds.has(item.id) ? selectedSerialIds.delete(item.id) : selectedSerialIds.add(item.id);
            renderLabelCards();
        };

        card.innerHTML = `
            <div class="w-full flex items-center justify-between mb-2 text-[11px]">
                <div class="flex items-center gap-1.5">
                    <span class="px-2 py-0.5 rounded font-bold ${item.is_printed == 1 ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-600'}">${item.is_printed == 1 ? 'Printed' : 'Not Printed'}</span>
                    <span class="px-2 py-0.5 rounded font-bold ${item.is_sold == 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${item.is_sold == 1 ? 'Sold' : 'In Stock'}</span>
                </div>
                <input type="checkbox" ${isSelected ? 'checked' : ''} class="w-4 h-4 accent-indigo-600 pointer-events-none">
            </div>
            <div class="w-full bg-white border border-slate-300 rounded p-2.5 flex flex-col justify-between items-center" style="aspect-ratio: 2/1;">
                <div class="w-full text-left pl-1 font-extrabold text-xs text-black uppercase leading-none">${item.model_name}</div>
                <svg id="bc_svg_${item.id}" class="w-full h-14 my-0.5"></svg>
                <div class="mono font-extrabold text-xs tracking-widest text-black leading-none">${item.serial_no}</div>
            </div>
        `;
        container.appendChild(card);
        JsBarcode(`#bc_svg_${item.id}`, item.serial_no, { format: "CODE128", displayValue: false, margin: 0, width: 1.8, height: 45 });
    });

    document.getElementById('selected_count_badge').textContent = selectedSerialIds.size;
}

function selectAllVisibleLabels(select) {
    selectedSerialIds = select ? new Set(filteredSerials.map(i => i.id)) : new Set();
    renderLabelCards();
}

async function exportLabelsPDF() {
    const itemsToPrint = filteredSerials.filter(i => selectedSerialIds.has(i.id));
    if (!itemsToPrint.length) return alert("Select at least one label.");

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: [50, 25] });
    const canvas = document.createElement('canvas');

    itemsToPrint.forEach((item, idx) => {
        if (idx > 0) doc.addPage([50, 25], 'landscape');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8.5);
        doc.text(String(item.model_name).toUpperCase(), 2.5, 4.2);

        JsBarcode(canvas, item.serial_no, { format: "CODE128", displayValue: false, margin: 0, width: 4, height: 120 });
        doc.addImage(canvas.toDataURL('image/png'), 'PNG', 2.5, 5.2, 45, 14.2);

        doc.setFontSize(9.5);
        doc.text(String(item.serial_no), 25, 23.3, { align: 'center' });
    });

    doc.save(`SwitchNex_Labels_${new Date().toISOString().slice(0,10)}.pdf`);

    const ids = itemsToPrint.map(i => i.id);
    await fetch('api.php?action=mark_printed', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids })
    });

    SERIALS_DATA.forEach(s => { if (ids.includes(s.id)) s.is_printed = 1; });
    renderLabelCards();
}

document.addEventListener('DOMContentLoaded', () => {
    populateRangeDropdowns();
    applyPrintFilters();
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>