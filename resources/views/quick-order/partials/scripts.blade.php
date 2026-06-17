{{-- Quick Order JavaScript — wrapped in IIFE, only 4 globals exposed --}}

<script>
(function() {
    var products = [];
    var cartItems = {};
    var currentPage = 1;
    var currentQuery = '';
    var hasMore = false;
    var isLoadingMore = false;

    // ─── Load products from backend ───────────────────────────────

    async function loadProducts(query, page) {
        query = query || '';
        page = page || 1;

        var url = '/apps/quick-order/api/products?page=' + page + '&per_page=100';
        if (query) url += '&q=' + encodeURIComponent(query);

        try {
            var resp = await fetch(url);
            var data = await resp.json();

            if (data.source === 'waiting') {
                document.querySelector('#qb-table tbody').innerHTML =
                    '<tr><td colspan="5" class="qb-empty">&#x23F3; Loading product catalog&hellip;</td></tr>';
                pollCatalogStatus(function() { loadProducts(query, page); });
                return;
            }

            if (page === 1) {
                products = data.products || [];
            } else {
                products = products.concat(data.products || []);
            }

            hasMore = data.hasMore || false;
            currentPage = page;
            currentQuery = query;
            renderProducts();
        } catch (e) {
            document.querySelector('#qb-table tbody').innerHTML =
                '<tr><td colspan="5" class="qb-empty">&#x26A0;&#xFE0F; Could not load products. Please try again.</td></tr>';
        }
    }

    // ─── Search (server-side) ─────────────────────────────────────

    var searchTimer;
    window.filterProducts = function() {
        clearTimeout(searchTimer);
        var q = document.getElementById('qb-search').value;
        searchTimer = setTimeout(function() {
            currentQuery = q;
            loadProducts(q, 1);
        }, 300); // debounce 300ms
    };

    // ─── Infinite scroll (load next page) ─────────────────────────

    window.onscroll = function() {
        if (isLoadingMore || !hasMore) return;
        var scrollBottom = window.innerHeight + window.scrollY;
        var docHeight = document.documentElement.scrollHeight;
        if (scrollBottom >= docHeight - 400) {
            isLoadingMore = true;
            loadProducts(currentQuery, currentPage + 1).then(function() {
                isLoadingMore = false;
            });
        }
    };

    // ─── Background catalog refresh progress ──────────────────────

    function pollCatalogStatus(onComplete) {
        var interval = setInterval(function() {
            fetch('/apps/quick-order/api/products/status')
                .then(function(r) { return r.json(); })
                .then(function(s) {
                    if (s.status === 'starting' || s.status === 'querying' || s.status === 'processing' || s.status === 'downloading') {
                        var bar = document.getElementById('qb-progress');
                        bar.style.display = 'block';
                        document.getElementById('qb-progress-text').textContent = '&#x1F504; Updating product catalog...';
                        document.getElementById('qb-progress-pct').textContent = s.percent + '% (' + (s.records || '') + ' records)';
                    }
                    if (s.status === 'complete') {
                        clearInterval(interval);
                        document.getElementById('qb-progress').style.display = 'none';
                        if (onComplete) onComplete();
                    }
                    if (s.status === 'failed') {
                        clearInterval(interval);
                        document.getElementById('qb-progress').style.display = 'none';
                    }
                });
        }, 3000);
    }

    // ─── Render product table rows ────────────────────────────────

    function renderProducts() {
        var tbody = document.querySelector('#qb-table tbody');

        if (!products.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="qb-empty">No products found.</td></tr>';
            return;
        }

        // Rebuild from scratch (replace, not append) for search/filter
        if (currentPage === 1) {
            tbody.innerHTML = products.map(function(p) {
                return buildRow(p);
            }).join('');
        } else {
            // Append new page rows
            tbody.innerHTML += products.slice(-100).map(function(p) {
                return buildRow(p);
            }).join('');
        }

        updateCartCount();
    }

    function buildRow(p) {
        var qty = cartItems[p.variant_id] || '';
        var productLabel = p.title;
        if (p.variant_title && p.variant_title !== 'Default Title') {
            productLabel += ' &mdash; ' + p.variant_title;
        }
        var isOOS = p.inventory <= 0;
        var disabledAttr = isOOS ? ' disabled title="Out of stock"' : '';

        return '<tr class="' + (isOOS ? 'qb-row-oos' : '') + '">' +
            '<td><strong>' + productLabel + '</strong>' + (isOOS ? ' <span style="color:#d82c0d;font-size:11px;">(OOS)</span>' : '') + '</td>' +
            '<td>' + (p.sku || '&mdash;') + '</td>' +
            '<td>$' + parseFloat(p.price).toFixed(2) + '</td>' +
            '<td>' + getStockLabel(p.inventory) + '</td>' +
            '<td><input type="number" class="qb-qty" min="0" value="' + qty +
                '" placeholder="0" data-id="' + p.variant_id +
                '" onchange="updateCart(this)"' + disabledAttr + '></td>' +
            '</tr>';
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

    window.updateCart = function(input) {
        var id = input.dataset.id;
        var qty = parseInt(input.value) || 0;
        if (qty > 0) cartItems[id] = qty;
        else delete cartItems[id];
        updateCartCount();
    };


    window.clearShopifyCart = async function() {
        var btn = document.getElementById('qb-clear-cart');
        var original = btn.textContent;
        btn.textContent = '\u23F3 Clearing...';
        btn.disabled = true;

        try {
            await fetch('/cart/clear.js', { method: 'POST' });
        } catch (e) { /* ignore */ }

        // Clear local & reload so cart UI updates
        cartItems = {};
        setTimeout(function() { location.reload(); }, 500);
    };

    function updateCartCount() {
        var count = Object.keys(cartItems).length;
        document.getElementById('qb-cart-count').textContent = count;
        document.getElementById('qb-add-all').disabled = count === 0;
    }

    // ─── Add all to cart ──────────────────────────────────────────

    // ─── Helper: Add items to cart via AJAX ───────────────────────

    async function ajaxAddToCart(items) {
        // items = [{id: '123', qty: 5}, {id: '456', qty: 3}]
        var batchSize = 50;
        for (var i = 0; i < items.length; i += batchSize) {
            var batch = items.slice(i, i + batchSize);
            var formData = new FormData();
            batch.forEach(function(item) {
                formData.append('updates[' + item.id + ']', item.qty);
            });
            await fetch('/cart/update.js', { method: 'POST', body: formData });
        }
    }

    // ─── Smart Cart: permalink / AJAX / Draft Order ──────────────

    window.smartCart = async function(method) {
        var q = document.getElementById('qb-search').value;

        var resp = await fetch('/apps/quick-order/api/add-all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ q: q }),
        });
        var data = await resp.json();
        var variants = data.variants || [];
        var count = variants.length;

        if (!count) { alert('No products to add.'); return; }

        // Check for out-of-stock items
        var oosProducts = products.filter(function(p) {
            return variants.indexOf(p.variant_id) !== -1 && p.inventory <= 0;
        });
        var oosCount = oosProducts.length;

        if (oosCount > 0) {
            var oosNames = oosProducts.slice(0, 5).map(function(p) { return p.title; }).join(', ');
            if (oosCount > 5) oosNames += '... and ' + (oosCount - 5) + ' more';
            if (!confirm('\u26A0\uFE0F ' + oosCount + ' item(s) out of stock:\n\n' + oosNames + '\n\nContinue anyway?')) {
                return;
            }
        }

        // ── Method 1: Cart Permalink (fast, <300 products) ──
        if (method === 'permalink') {
            var params = variants.map(function(v) { return v + ':1'; }).join(',');
            window.location.href = '/cart/' + params + '?storefront=true';
            return;
        }

        // ── Method 2: AJAX Cart (batched, <2000 products) ──
        if (method === 'ajax') {
            var items = variants.map(function(v) { return { id: v, qty: 1 }; });
            await ajaxAddToCart(items);
            window.location.href = '/cart';
            return;
        }

        // ── Method 3: Draft Order (unlimited, B2B invoice) ──
        if (method === 'draft') {
            var email = prompt('\u2709\uFE0F Enter your email to receive the invoice:', '');
            if (!email) { alert('Email is required for invoice.'); return; }

            var lineItems = variants.map(function(v) {
                return { id: 'gid://shopify/ProductVariant/' + v, qty: 1 };
            });

            var drResp = await fetch('/apps/quick-order/api/draft-order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: lineItems, email: email }),
            });
            var drData = await drResp.json();

            if (drData.invoice_url) {
                alert('\u2705 Invoice sent to ' + email + '!\n\nOrder: ' + drData.draft_order + '\n\nCheck your email for the payment link.');
            } else {
                alert('\u26A0\uFE0F Order created but invoice could not be sent.\nOrder: ' + drData.draft_order);
            }
            return;
        }
    };

    // ─── Bottom: Add selected quantities via AJAX Cart ────────────

    window.addSelectedToCart = async function() {
        var btn = document.getElementById('qb-add-all');
        var original = btn.innerHTML;

        if (!Object.keys(cartItems).length) return;
        btn.disabled = true;
        btn.innerHTML = '\u23F3 Adding...';

        try {
            var items = Object.keys(cartItems).map(function(vid) {
                return { id: vid.split('/').pop(), qty: cartItems[vid] };
            });
            await ajaxAddToCart(items);
            window.location.href = '/cart';
        } catch (e) {
            alert('\u26A0\uFE0F Could not add items. Please try again.');
            btn.disabled = false;
            btn.innerHTML = original;
        }
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
