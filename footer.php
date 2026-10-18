</div>
    </main>

    <!-- GLOBAL SERIAL & WARRANTY INSPECTOR MODAL -->
    <div id="detail_inspector_modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 animate-view">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <span class="text-[11px] font-bold uppercase text-indigo-600">Serial Inspector</span>
                    <h3 id="insp_serial_no" class="text-xl font-extrabold mono text-slate-900"></h3>
                </div>
                <button onclick="closeInspectorModal()" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div id="insp_body" class="space-y-4 text-sm"></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                <button onclick="closeInspectorModal()" class="px-4 py-2 rounded-xl bg-slate-100 text-xs font-bold text-slate-700">Close</button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let searchTimer = null;
        function handleGlobalSearch(query) {
            const clearBtn = document.getElementById('global_search_clear');
            const dropdown = document.getElementById('global_search_dropdown');
            clearBtn.classList.toggle('hidden', query.length === 0);

            if (query.trim().length < 2) {
                dropdown.classList.add('hidden');
                return;
            }

            clearTimeout(searchTimer);
            searchTimer = setTimeout(async () => {
                const res = await fetch(`api.php?action=global_search&q=${encodeURIComponent(query)}`);
                const items = await res.json();
                dropdown.classList.remove('hidden');

                if (items.length === 0) {
                    dropdown.innerHTML = `<div class="p-4 text-xs text-slate-400 text-center">No matching records found.</div>`;
                    return;
                }

                dropdown.innerHTML = items.map(item => `
                    <div onclick='showInspectorData(${JSON.stringify(item).replace(/'/g, "&apos;")})' class="p-3.5 hover:bg-slate-50 cursor-pointer flex items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="mono font-extrabold text-xs text-slate-900">${item.serial_no}</span>
                                <span class="mono text-[11px] font-bold px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded">${item.model_name}</span>
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                ${item.is_sold == 1 ? `Customer: <b>${item.customer_name}</b> (${item.customer_mobile}) •${item.customer_address || ''}` : `${item.product_name} (${item.product_type})`}
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold ${item.is_printed == 1 ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500'}">${item.is_printed == 1 ? 'Printed' : 'Not Printed'}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold ${item.is_sold == 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${item.is_sold == 1 ? 'Sold' : 'In Stock'}</span>
                        </div>
                    </div>
                `).join('');
            }, 200);
        }

        function clearGlobalSearch() {
            document.getElementById('global_search_input').value = '';
            document.getElementById('global_search_clear').classList.add('hidden');
            document.getElementById('global_search_dropdown').classList.add('hidden');
        }

        async function inspectSerial(sn) {
            const res = await fetch(`api.php?action=global_search&q=${encodeURIComponent(sn)}`);
            const items = await res.json();
            if (items.length > 0) showInspectorData(items[0]);
        }

        function showInspectorData(item) {
            clearGlobalSearch();
            document.getElementById('insp_serial_no').textContent = item.serial_no;
            const body = document.getElementById('insp_body');

            const printBadge = item.is_printed == 1
                ? `<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700">Printed</span>`
                : `<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">Not Printed</span>`;

            const soldBadge = item.is_sold == 1
                ? `<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700">Sold</span>`
                : `<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700">In Stock</span>`;

            const saleHtml = item.is_sold == 1 ? `
                <div class="p-4 rounded-xl bg-emerald-50/60 border border-emerald-200 space-y-2">
                    <div class="text-xs font-bold uppercase text-emerald-800">Customer & Warranty Record</div>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div><span class="text-slate-500">Customer:</span> <strong class="text-slate-900">${item.customer_name}</strong></div>
                        <div><span class="text-slate-500">Mobile:</span> <strong class="mono text-slate-900">${item.customer_mobile}</strong></div>
                        <div class="col-span-2"><span class="text-slate-500">Address:</span> <strong class="text-slate-900">${item.customer_address || '—'}</strong></div>
                        <div><span class="text-slate-500">Dealer:</span> <strong class="text-slate-900">${item.dealer_name}</strong></div>
                        <div><span class="text-slate-500">Sale Date:</span> <strong class="text-slate-900">${item.sale_date}</strong></div>
                        <div class="col-span-2"><span class="text-slate-500">Warranty Expiry:</span> <strong class="text-emerald-700">${item.warranty_expiry || 'N/A'} (${item.warranty_months} Months)</strong></div>
                    </div>
                </div>
            ` : `
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Unit is currently in stock.</span>
                    <a href="sales.php?serial_no=${encodeURIComponent(item.serial_no)}" class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold">Register Sale</a>
                </div>
            `;

            body.innerHTML = `
                <div class="grid grid-cols-2 gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <div><span class="text-slate-400 block">Product</span><strong class="text-slate-800">${item.product_name} (${item.product_type})</strong></div>
                    <div><span class="text-slate-400 block">Model Name</span><strong class="mono text-indigo-700">${item.model_name}</strong></div>
                    <div><span class="text-slate-400 block mb-1">Label Status</span>${printBadge}</div>
                    <div><span class="text-slate-400 block mb-1">Sale Status</span>${soldBadge}</div>
                </div>
                ${saleHtml}
            `;

            const modal = document.getElementById('detail_inspector_modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeInspectorModal() {
            const modal = document.getElementById('detail_inspector_modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>