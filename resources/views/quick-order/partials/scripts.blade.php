{{-- Quick Order JavaScript — wrapped in IIFE, only 4 globals exposed --}}

<script>
(function() {
    var products = [];
    var cartItems = {};

    // ─── Load products from backend API ────────────────────────────

    async function loadProducts() {
        try {
            var resp = await fetch('/apps/quick-order/api/products');
            var data = await resp.json();
            products = data.products || [];
            renderProducts();
        } catch (e) {
            document.querySelector('#qb-table tbody').innerHTML =
                '<tr><td colspan="5" class="qb-empty">&#x26A0;&#xFE0F; Could not load products. Please try again.</td></tr>';
        }
    }

    // ─── Render product table rows ────────────────────────────────

    function renderProducts(filter) {
        filter = filter || '';
        var tbody = document.querySelector('#qb-table tbody');
        var q = filter.toLowerCase();
        var filtered = products.filter(function(p) {
            return !q
                || p.title.toLowerCase().indexOf(q) !== -1
                || (p.sku || '').toLowerCase().indexOf(q) !== -1;
        });

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="qb-empty">No products found.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(function(p) {
            var qty = cartItems[p.variant_id] || '';
            var stockLabel = getStockLabel(p.inventory);

            return '<tr>' +
                '<td><strong>' + p.title + '</strong></td>' +
                '<td>' + (p.sku || '&mdash;') + '</td>' +
                '<td>$' + parseFloat(p.price).toFixed(2) + '</td>' +
                '<td>' + stockLabel + '</td>' +
                '<td><input type="number" class="qb-qty" min="0" value="' + qty +
                    '" placeholder="0" data-id="' + p.variant_id +
                    '" onchange="updateCart(this)"></td>' +
                '</tr>';
        }).join('');

        updateCartCount();
    }

    function getStockLabel(inventory) {
        if (inventory > 10) {
            return '<span class="qb-stock-in">' + inventory + ' in stock</span>';
        }
        if (inventory > 0) {
            return '<span class="qb-stock-low">Only ' + inventory + ' left</span>';
        }
        return '<span class="qb-stock-out">Out of stock</span>';
    }

    // ─── Cart operations ──────────────────────────────────────────

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

    window.selectAllProducts = function() {
        var btn = document.getElementById('qb-select-all');
        var isSelecting = btn.textContent.indexOf('Select') !== -1;

        var filtered = products.filter(function(p) {
            var q = (document.getElementById('qb-search').value || '').toLowerCase();
            return !q
                || p.title.toLowerCase().indexOf(q) !== -1
                || (p.sku || '').toLowerCase().indexOf(q) !== -1;
        });

        filtered.forEach(function(p) {
            if (isSelecting) {
                cartItems[p.variant_id] = 1;       // select all
            } else {
                delete cartItems[p.variant_id];     // deselect all
            }
        });

        renderProducts(document.getElementById('qb-search').value);

        // Toggle button
        btn.textContent = isSelecting ? '❎ Deselect All' : '✅ Select All';
    };

    function updateCartCount() {
        var count = Object.keys(cartItems).length;
        document.getElementById('qb-cart-count').textContent = count;
        document.getElementById('qb-add-all').disabled = count === 0;
    }

    // ─── Add all to cart ──────────────────────────────────────────

    window.addAllToCart = async function() {
        var btn = document.getElementById('qb-add-all');
        btn.disabled = true;
        btn.textContent = '&#x23F3; Adding to cart...';

        try {
            var resp = await fetch('/apps/quick-order/api/add-bulk', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: cartItems }),
            });

            if (resp.ok) {
                var data = await resp.json();
                if (data.redirect) window.location.href = data.redirect;
                else alert('&#x2705; Items added to cart!');
            } else {
                alert('&#x26A0;&#xFE0F; Could not add items. Please try again.');
            }
        } catch (e) {
            alert('&#x26A0;&#xFE0F; Network error. Please try again.');
        }

        btn.disabled = false;
        btn.innerHTML = '&#x1F6D2; Add All to Cart <span class="qb-cart-count" id="qb-cart-count">'
            + Object.keys(cartItems).length + '</span>';
    };

    // ─── CSV upload ───────────────────────────────────────────────

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
                var product = products.find(function(p) {
                    return p.sku === sku || p.title.toLowerCase() === sku.toLowerCase();
                });
                if (product) {
                    cartItems[product.variant_id] = qty;
                    count++;
                }
            });

            var status = document.getElementById('qb-csv-status');
            status.style.display = 'block';
            status.textContent = '&#x2705; ' + count + ' products matched from CSV';

            renderProducts(document.getElementById('qb-search').value);
        };
        reader.readAsText(file);
    };

    // ─── Drag & drop ──────────────────────────────────────────────

    (function() {
        var zone = document.getElementById('qb-csv-zone');
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            zone.style.borderColor = 'var(--qb-green)';
        });
        zone.addEventListener('dragleave', function() {
            zone.style.borderColor = '';
        });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.style.borderColor = '';
            handleCSV({ files: e.dataTransfer.files });
        });
    })();

    // ─── Kick off ─────────────────────────────────────────────────

    loadProducts();
})();
</script>
