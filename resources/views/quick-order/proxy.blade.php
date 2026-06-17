{{-- Quick Order — App Proxy (embedded in store theme) --}}

<style>
    .qb-container {
        --qb-green: #008060;
        --qb-gray-50: #f6f6f7;
        --qb-gray-100: #e4e5e7;
        --qb-gray-200: #c9cccf;
        --qb-gray-400: #8d9298;
        --qb-gray-600: #4a4f55;
        --qb-gray-800: #202223;
        --qb-radius: 8px;
        font-family: inherit;
        color: var(--qb-gray-800);
        max-width: 960px;
        margin: 0 auto;
        padding: 32px 20px;
        line-height: 1.5;
    }
    .qb-container *,
    .qb-container *::before,
    .qb-container *::after {
        box-sizing: border-box;
    }
    .qb-header {
        text-align: center;
        margin-bottom: 32px;
    }
    .qb-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: var(--qb-green);
        margin: 0 0 4px;
    }
    .qb-header p {
        font-size: 14px;
        color: var(--qb-gray-600);
        margin: 0;
    }
    .qb-card {
        background: #fff;
        border-radius: var(--qb-radius);
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 20px;
        margin-bottom: 20px;
    }
    .qb-card h2 {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 12px;
    }
    .qb-search {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--qb-gray-200);
        border-radius: 6px;
        font-size: 14px;
        margin-bottom: 16px;
        font-family: inherit;
    }
    .qb-search:focus {
        outline: none;
        border-color: var(--qb-green);
        box-shadow: 0 0 0 2px rgba(0,128,96,.15);
    }
    .qb-table {
        width: 100%;
        border-collapse: collapse;
    }
    .qb-table th {
        text-align: left;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 600;
        color: var(--qb-gray-600);
        border-bottom: 1px solid var(--qb-gray-100);
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .qb-table td {
        padding: 10px 12px;
        font-size: 14px;
        border-bottom: 1px solid var(--qb-gray-50);
    }
    .qb-table tr:hover {
        background: var(--qb-gray-50);
    }
    .qb-qty {
        width: 70px;
        padding: 6px 8px;
        border: 1px solid var(--qb-gray-200);
        border-radius: 4px;
        text-align: center;
        font-size: 14px;
        font-family: inherit;
    }
    .qb-qty:focus {
        outline: none;
        border-color: var(--qb-green);
    }
    .qb-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all .15s;
        font-family: inherit;
    }
    .qb-btn-primary {
        background: var(--qb-green);
        color: #fff;
    }
    .qb-btn-primary:hover {
        background: #006e52;
    }
    .qb-btn-primary:disabled {
        opacity: .5;
        cursor: not-allowed;
    }
    .qb-footer {
        text-align: center;
        padding: 20px;
    }
    .qb-footer p {
        font-size: 13px;
        color: var(--qb-gray-400);
        margin: 0;
    }
    .qb-empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--qb-gray-400);
    }
    .qb-stock-low {
        color: #b98900;
        font-weight: 500;
    }
    .qb-stock-out {
        color: #d82c0d;
    }
    .qb-stock-in {
        color: #008060;
    }
    .qb-csv-zone {
        border: 2px dashed var(--qb-gray-200);
        border-radius: var(--qb-radius);
        padding: 24px;
        text-align: center;
        color: var(--qb-gray-400);
        cursor: pointer;
        transition: all .15s;
        margin-bottom: 16px;
    }
    .qb-csv-zone:hover {
        border-color: var(--qb-green);
        color: var(--qb-green);
    }
    .qb-cart-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--qb-green);
        color: #fff;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 700;
        width: 22px;
        height: 22px;
        margin-left: 6px;
    }
</style>

<div class="qb-container">
    <div class="qb-header">
        <h1>&#x26A1; Quick Order</h1>
        <p>Type quantities and add everything to cart in one click &mdash; no more clicking &ldquo;Add to Cart&rdquo; hundreds of times.</p>
    </div>

    {{-- Search --}}
    <div class="qb-card">
        <input type="text" class="qb-search" id="qb-search" placeholder="&#x1F50D; Search products by name or SKU..." oninput="filterProducts()">
    </div>

    {{-- CSV Upload --}}
    <div class="qb-card">
        <div class="qb-csv-zone" id="qb-csv-zone" onclick="document.getElementById('qb-csv-input').click()">
            <p>&#x1F4E4; <strong>Drag &amp; drop</strong> a CSV or Excel file here, or click to browse</p>
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
                <tr><td colspan="5" class="qb-empty">Loading products&hellip;</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Add All to Cart --}}
    <div class="qb-card" style="text-align:center;">
        <button class="qb-btn qb-btn-primary" id="qb-add-all" disabled onclick="addAllToCart()">
            &#x1F6D2; Add All to Cart
            <span class="qb-cart-count" id="qb-cart-count">0</span>
        </button>
    </div>

    <div class="qb-footer">
        <p>Powered by QuickB2B &mdash; Built for wholesale &amp; B2B ordering</p>
    </div>
</div>

<script>
(function() {
    // ─── Product Data (fetched from backend) ───
    var products = [];
    var cartItems = {};

    async function loadProducts() {
        try {
            var resp = await fetch('/apps/quick-order/api/products?shop=' + encodeURIComponent('{{ $shopDomain }}'));
            var data = await resp.json();
            products = data.products || [];
            renderProducts();
        } catch (e) {
            document.querySelector('#qb-table tbody').innerHTML =
                '<tr><td colspan="5" class="qb-empty">&#x26A0;&#xFE0F; Could not load products. Please try again.</td></tr>';
        }
    }

    function renderProducts(filter) {
        filter = filter || '';
        var tbody = document.querySelector('#qb-table tbody');
        var q = filter.toLowerCase();
        var filtered = products.filter(function(p) {
            return !q || p.title.toLowerCase().indexOf(q) !== -1 || (p.sku || '').toLowerCase().indexOf(q) !== -1;
        });

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="qb-empty">No products found.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(function(p) {
            var qty = cartItems[p.id] || '';
            var stockLabel;
            if (p.inventory > 10) {
                stockLabel = '<span class="qb-stock-in">' + p.inventory + ' in stock</span>';
            } else if (p.inventory > 0) {
                stockLabel = '<span class="qb-stock-low">Only ' + p.inventory + ' left</span>';
            } else {
                stockLabel = '<span class="qb-stock-out">Out of stock</span>';
            }

            return '<tr>' +
                '<td><strong>' + p.title + '</strong></td>' +
                '<td>' + (p.sku || '&mdash;') + '</td>' +
                '<td>$' + parseFloat(p.price).toFixed(2) + '</td>' +
                '<td>' + stockLabel + '</td>' +
                '<td><input type="number" class="qb-qty" min="0" value="' + qty + '" placeholder="0" data-id="' + p.id + '" onchange="updateCart(this)"></td>' +
                '</tr>';
        }).join('');

        updateCartCount();
    }

    window.filterProducts = function() {
        renderProducts(document.getElementById('qb-search').value);
    };

    window.updateCart = function(input) {
        var id = input.dataset.id;
        var qty = parseInt(input.value) || 0;
        if (qty > 0) cartItems[id] = qty;
        else delete cartItems[id];
        updateCartCount();
    };

    function updateCartCount() {
        var count = Object.keys(cartItems).length;
        document.getElementById('qb-cart-count').textContent = count;
        document.getElementById('qb-add-all').disabled = count === 0;
    }

    window.addAllToCart = async function() {
        var btn = document.getElementById('qb-add-all');
        btn.disabled = true;
        btn.textContent = '&#x23F3; Adding to cart...';

        try {
            var resp = await fetch('/apps/quick-order/api/add-bulk', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    shop: '{{ $shopDomain }}',
                    items: cartItems,
                }),
            });

            if (resp.ok) {
                var data = await resp.json();
                if (data.redirect) window.location.href = data.redirect;
                else alert('&#x2705; Items added to cart! Proceed to checkout.');
            } else {
                alert('&#x26A0;&#xFE0F; Could not add items. Please try again.');
            }
        } catch (e) {
            alert('&#x26A0;&#xFE0F; Network error. Please try again.');
        }

        btn.disabled = false;
        btn.innerHTML = '&#x1F6D2; Add All to Cart <span class="qb-cart-count" id="qb-cart-count">' + Object.keys(cartItems).length + '</span>';
    };

    window.handleCSV = function(input) {
        var file = input.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            var lines = e.target.result.split('\n').filter(function(l) { return l.trim(); });
            var count = 0;
            lines.forEach(function(line) {
                var cols = line.split(/[,\t]/);
                var sku = (cols[0] || '').trim();
                var qty = parseInt(cols[1]) || 1;
                var product = products.find(function(p) { return p.sku === sku || p.title.toLowerCase() === sku.toLowerCase(); });
                if (product) {
                    cartItems[product.id] = qty;
                    count++;
                }
            });
            document.getElementById('qb-csv-status').style.display = 'block';
            document.getElementById('qb-csv-status').textContent = '&#x2705; ' + count + ' products matched from CSV';
            renderProducts(document.getElementById('qb-search').value);
        };
        reader.readAsText(file);
    };

    // Drag & drop
    (function() {
        var zone = document.getElementById('qb-csv-zone');
        zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.style.borderColor = 'var(--qb-green)'; });
        zone.addEventListener('dragleave', function() { zone.style.borderColor = ''; });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.style.borderColor = '';
            handleCSV({ files: e.dataTransfer.files });
        });
    })();

    // Load on page ready
    loadProducts();
})();
</script>
