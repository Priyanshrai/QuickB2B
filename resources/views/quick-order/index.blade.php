<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quick Order — {{ $shopDomain }}</title>
    <style>
        :root {
            --qb-green: #008060;
            --qb-gray-50: #f6f6f7;
            --qb-gray-100: #e4e5e7;
            --qb-gray-200: #c9cccf;
            --qb-gray-400: #8d9298;
            --qb-gray-600: #4a4f55;
            --qb-gray-800: #202223;
            --qb-radius: 8px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--qb-gray-50); color: var(--qb-gray-800); min-height: 100vh; }
        .qb-container { max-width: 960px; margin: 0 auto; padding: 32px 20px; }
        .qb-header { text-align: center; margin-bottom: 32px; }
        .qb-header h1 { font-size: 24px; font-weight: 700; color: var(--qb-green); }
        .qb-header p { font-size: 14px; color: var(--qb-gray-600); margin-top: 4px; }
        .qb-card { background: #fff; border-radius: var(--qb-radius); box-shadow: 0 1px 3px rgba(0,0,0,.06); padding: 20px; margin-bottom: 20px; }
        .qb-card h2 { font-size: 16px; font-weight: 600; margin-bottom: 12px; }
        .qb-search { width: 100%; padding: 10px 14px; border: 1px solid var(--qb-gray-200); border-radius: 6px; font-size: 14px; margin-bottom: 16px; }
        .qb-search:focus { outline: none; border-color: var(--qb-green); box-shadow: 0 0 0 2px rgba(0,128,96,.15); }
        .qb-table { width: 100%; border-collapse: collapse; }
        .qb-table th { text-align: left; padding: 10px 12px; font-size: 12px; font-weight: 600; color: var(--qb-gray-600); border-bottom: 1px solid var(--qb-gray-100); text-transform: uppercase; letter-spacing: .5px; }
        .qb-table td { padding: 10px 12px; font-size: 14px; border-bottom: 1px solid var(--qb-gray-50); }
        .qb-table tr:hover { background: var(--qb-gray-50); }
        .qb-qty { width: 70px; padding: 6px 8px; border: 1px solid var(--qb-gray-200); border-radius: 4px; text-align: center; font-size: 14px; }
        .qb-qty:focus { outline: none; border-color: var(--qb-green); }
        .qb-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all .15s; }
        .qb-btn-primary { background: var(--qb-green); color: #fff; }
        .qb-btn-primary:hover { background: #006e52; }
        .qb-btn-primary:disabled { opacity: .5; cursor: not-allowed; }
        .qb-footer { text-align: center; padding: 20px; }
        .qb-footer p { font-size: 13px; color: var(--qb-gray-400); }
        .qb-empty { text-align: center; padding: 40px 20px; color: var(--qb-gray-400); }
        .qb-stock-low { color: #b98900; font-weight: 500; }
        .qb-stock-out { color: #d82c0d; }
        .qb-stock-in { color: #008060; }
        .qb-csv-zone { border: 2px dashed var(--qb-gray-200); border-radius: var(--qb-radius); padding: 24px; text-align: center; color: var(--qb-gray-400); cursor: pointer; transition: all .15s; margin-bottom: 16px; }
        .qb-csv-zone:hover { border-color: var(--qb-green); color: var(--qb-green); }
        .qb-cart-count { display: inline-flex; align-items: center; justify-content: center; background: var(--qb-green); color: #fff; border-radius: 99px; font-size: 12px; font-weight: 700; width: 22px; height: 22px; margin-left: 6px; }
    </style>
</head>
<body>
    <div class="qb-container">
        <div class="qb-header">
            <h1>⚡ Quick Order</h1>
            <p>Type quantities and add everything to cart in one click — no more clicking "Add to Cart" hundreds of times.</p>
        </div>

        {{-- Search --}}
        <div class="qb-card">
            <input type="text" class="qb-search" id="qb-search" placeholder="🔍 Search products by name or SKU..." oninput="filterProducts()">
        </div>

        {{-- CSV Upload --}}
        <div class="qb-card">
            <div class="qb-csv-zone" id="qb-csv-zone" onclick="document.getElementById('qb-csv-input').click()">
                <p>📤 <strong>Drag & drop</strong> a CSV or Excel file here, or click to browse</p>
                <p style="font-size:12px;margin-top:4px;">First column: SKU or Product Name. Second column: Quantity.</p>
            </div>
            <input type="file" id="qb-csv-input" accept=".csv,.xlsx,.xls" style="display:none;" onchange="handleCSV(this)">
            <div id="qb-csv-status" style="font-size:13px;color:var(--qb-green);display:none;"></div>
        </div>

        {{-- Product Table --}}
        <div class="qb-card">
            <h2>Products</h2>
            <table class="qb-table" id="qb-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th style="width:100px;">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="5" class="qb-empty">Loading products...</td></tr>
                </tbody>
            </table>
        </div>

        {{-- Add All to Cart --}}
        <div class="qb-card" style="text-align:center;">
            <button class="qb-btn qb-btn-primary" id="qb-add-all" disabled onclick="addAllToCart()">
                🛒 Add All to Cart
                <span class="qb-cart-count" id="qb-cart-count">0</span>
            </button>
        </div>

        <div class="qb-footer">
            <p>Powered by QuickB2B — Built for wholesale & B2B ordering</p>
        </div>
    </div>

    <script>
        // ─── Product Data (fetched from backend) ───
        let products = [];
        let cartItems = {};

        async function loadProducts() {
            try {
                const resp = await fetch('/apps/quick-order/api/products?shop=' + encodeURIComponent('{{ $shopDomain }}'), {
                    headers: { 'ngrok-skip-browser-warning': 'true' }
                });
                const data = await resp.json();
                products = data.products || [];
                renderProducts();
            } catch (e) {
                document.querySelector('#qb-table tbody').innerHTML =
                    '<tr><td colspan="5" class="qb-empty">⚠️ Could not load products. Please try again.</td></tr>';
            }
        }

        function renderProducts(filter = '') {
            const tbody = document.querySelector('#qb-table tbody');
            const q = filter.toLowerCase();
            const filtered = products.filter(p =>
                !q || p.title.toLowerCase().includes(q) || (p.sku || '').toLowerCase().includes(q)
            );

            if (!filtered.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="qb-empty">No products found.</td></tr>';
                return;
            }

            tbody.innerHTML = filtered.map(p => {
                const qty = cartItems[p.id] || '';
                const stockLabel = p.inventory > 10
                    ? `<span class="qb-stock-in">${p.inventory} in stock</span>`
                    : p.inventory > 0
                        ? `<span class="qb-stock-low">Only ${p.inventory} left</span>`
                        : `<span class="qb-stock-out">Out of stock</span>`;

                return `<tr>
                    <td><strong>${p.title}</strong></td>
                    <td>${p.sku || '—'}</td>
                    <td>\$${parseFloat(p.price).toFixed(2)}</td>
                    <td>${stockLabel}</td>
                    <td><input type="number" class="qb-qty" min="0" value="${qty}" placeholder="0" data-id="${p.id}" onchange="updateCart(this)"></td>
                </tr>`;
            }).join('');

            updateCartCount();
        }

        function filterProducts() {
            renderProducts(document.getElementById('qb-search').value);
        }

        function updateCart(input) {
            const id = input.dataset.id;
            const qty = parseInt(input.value) || 0;
            if (qty > 0) cartItems[id] = qty;
            else delete cartItems[id];
            updateCartCount();
        }

        function updateCartCount() {
            const count = Object.keys(cartItems).length;
            document.getElementById('qb-cart-count').textContent = count;
            document.getElementById('qb-add-all').disabled = count === 0;
        }

        async function addAllToCart() {
            const btn = document.getElementById('qb-add-all');
            btn.disabled = true;
            btn.textContent = '⏳ Adding to cart...';

            try {
                const resp = await fetch('/apps/quick-order/api/add-bulk', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'ngrok-skip-browser-warning': 'true'
                    },
                    body: JSON.stringify({
                        shop: '{{ $shopDomain }}',
                        items: cartItems,
                    }),
                });

                if (resp.ok) {
                    const data = await resp.json();
                    if (data.redirect) window.location.href = data.redirect;
                    else alert('✅ Items added to cart! Proceed to checkout.');
                } else {
                    alert('⚠️ Could not add items. Please try again.');
                }
            } catch (e) {
                alert('⚠️ Network error. Please try again.');
            }

            btn.disabled = false;
            btn.textContent = '🛒 Add All to Cart';
        }

        // ─── CSV Upload ───
        function handleCSV(input) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                const lines = e.target.result.split('\n').filter(l => l.trim());
                let count = 0;
                lines.forEach(line => {
                    const cols = line.split(/[,\t]/);
                    const sku = (cols[0] || '').trim();
                    const qty = parseInt(cols[1]) || 1;
                    const product = products.find(p => p.sku === sku || p.title.toLowerCase() === sku.toLowerCase());
                    if (product) {
                        cartItems[product.id] = qty;
                        count++;
                    }
                });
                document.getElementById('qb-csv-status').style.display = 'block';
                document.getElementById('qb-csv-status').textContent = `✅ ${count} products matched from CSV`;
                renderProducts(document.getElementById('qb-search').value);
            };
            reader.readAsText(file);
        }

        // Drag & drop
        const zone = document.getElementById('qb-csv-zone');
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor = 'var(--qb-green)'; });
        zone.addEventListener('dragleave', () => { zone.style.borderColor = 'var(--qb-gray-200)'; });
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.style.borderColor = 'var(--qb-gray-200)';
            handleCSV({ files: e.dataTransfer.files });
        });

        // Load on page ready
        loadProducts();
    </script>
</body>
</html>
