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
        var oos = isOutOfStock(p);
        var disabledAttr = oos ? ' disabled title="Out of stock"' : '';

        return '<tr class="' + (oos ? 'qb-row-oos' : '') + '">' +
            '<td><strong>' + productLabel + '</strong>' + (oos ? ' <span style="color:#d82c0d;font-size:11px;">(OOS)</span>' : '') + '</td>' +
            '<td>' + (p.sku || '&mdash;') + '</td>' +
            '<td>$' + parseFloat(p.price).toFixed(2) + '</td>' +
            '<td>' + getStockLabel(p.inventory, p.inventory_tracked) + '</td>' +
            '<td><input type="number" class="qb-qty" min="0" value="' + qty +
                '" placeholder="0" data-id="' + p.variant_id +
                '" onchange="updateCart(this)"' + disabledAttr + '></td>' +
            '</tr>';
    }

    function getStockLabel(inventory, tracked) {
        // tracked=false or '' means untracked (overselling allowed) → Unlimited
        if (!tracked) {
            return '<span class="qb-stock-in">Unlimited</span>';
        }
        if (inventory > 10) {
            return '<span class="qb-stock-in">' + inventory + ' in stock</span>';
        }
        if (inventory > 0) {
            return '<span class="qb-stock-low">Only ' + inventory + ' left</span>';
        }
        return '<span class="qb-stock-out">Out of stock</span>';
    }

    function isOutOfStock(p) {
        // Untracked (CONTINUE policy) = never OOS
        if (!p.inventory_tracked) return false;
        // Tracked (DENY policy) + inventory 0 = OOS
        return (p.inventory || 0) <= 0;
    }

    // ─── Cart operations ──────────────────────────────────────────

    window.updateCart = function(input) {
        var id = input.dataset.id;
        var qty = parseInt(input.value) || 0;
        if (qty > 0) cartItems[id] = qty;
        else delete cartItems[id];
        updateCartCount();
    };


    window.clearTableQty = function() {
        cartItems = {};
        renderProducts();
    };

    window.clearShopifyCart = async function() {
        var btn = document.getElementById('qb-clear-cart');
        var original = btn.textContent;
        btn.textContent = 'Clearing...';
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
        var el = document.getElementById('qb-cart-count');
        if (el) el.textContent = count;
        var btn = document.getElementById('qb-add-all');
        if (btn) btn.disabled = count === 0;
    }

    // ─── Add all to cart ──────────────────────────────────────────

    // ─── Helper: Add items to cart via AJAX ───────────────────────

    async function ajaxAddToCart(items) {
        var batchSize = 50;
        for (var i = 0; i < items.length; i += batchSize) {
            var batch = items.slice(i, i + batchSize);
            var formData = new FormData();
            batch.forEach(function(item) {
                formData.append('updates[' + item.id + ']', item.qty);
            });
            var resp = await fetch('/cart/update.js', { method: 'POST', body: formData });
            if (!resp.ok) {
                throw new Error('Cart API failed with status ' + resp.status);
            }
            if (i + batchSize < items.length) {
                await new Promise(function(r) { setTimeout(r, 1000); });
            }
        }
    }

    // ─── Smart Cart: permalink / AJAX / Draft Order ──────────────

    window.smartCart = async function(method) {
        var q = document.getElementById('qb-search').value;
        var useSelected = Object.keys(cartItems).length > 0;

        // Build line items from table qty OR all products
        var items = []; // [{id: 'gid://...Variant/123', qty: 5}, ...]

        if (useSelected) {
            items = Object.keys(cartItems).map(function(vid) {
                return { id: vid, qty: cartItems[vid] };
            });
        } else {
            // For draft orders, always fetch ALL products (ignore search)
            // For permalink/ajax, respect search filter
            var searchQ = (method === 'draft') ? '' : q;
            var resp = await fetch('/apps/quick-order/api/add-all', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ q: searchQ }),
            });
            var data = await resp.json();
            items = (data.variants || []).map(function(v) {
                return { id: 'gid://shopify/ProductVariant/' + v, qty: 1 };
            });
        }

        if (!items.length) { alert('No products to add.'); return; }

        // ── OOS filter (client-side best-effort, draft skips — server is authoritative) ──
        if (method !== 'draft') {
            var oosCount = 0;
            items = items.filter(function(item) {
                var p = products.find(function(prod) {
                    return (prod.variant_id || '').indexOf(item.id) !== -1;
                });
                if (!p) return true;
                if (isOutOfStock(p)) { oosCount++; return false; }
                return true;
            });

            if (oosCount > 0) {
                alert(oosCount + ' out-of-stock item(s) were skipped.\nOnly in-stock & unlimited products are included.');
            }

            if (!items.length) {
                alert('All items were out of stock or unavailable. Nothing to order.');
                return;
            }
        }

        // ── Method 1: Permalink ──
        if (method === 'permalink') {
            var params = items.map(function(i) { return (i.id.split('/').pop()) + ':' + i.qty; }).join(',');
            window.location.href = '/cart/' + params + '?storefront=true';
            return;
        }

        // ── Method 2: AJAX Cart ──
        if (method === 'ajax') {
            try {
                var ajaxItems = items.map(function(i) { return { id: i.id.split('/').pop(), qty: i.qty }; });
                await ajaxAddToCart(ajaxItems);
                window.location.href = '/cart';
            } catch (e) {
                alert('Cart API failed (' + e.message + ').\n\nTry Submit Order instead - it works on any store and creates an invoice.');
            }
            return;
        }

        // ── Method 3: Draft Order ──
        if (method === 'draft') {
            var email = prompt('✉️ Enter your email for invoice:', '');
            if (!email) return;

            var drResp = await fetch('/apps/quick-order/api/draft-order', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: items, email: email }),
            });
            var drData = await drResp.json();

            // Server rejected (all OOS, etc.)
            if (drData.error) {
                alert(drData.error + (drData.oos_skipped ? ' (' + drData.oos_skipped + ' OOS skipped)' : ''));
                return;
            }

            // Large order → background job
            if (drData.queued) {
                var msg = drData.message +
                    '\n\nThis may take a few minutes for ' + drData.orders + ' draft orders.' +
                    '\nYou will receive ' + drData.orders + ' invoice email(s) at ' + email + '.' +
                    '\n\nWe will notify you when done.';
                alert(msg);
                pollDraftOrderStatus();
                return;
            }

            // Small order → instant
            var resultMsg = 'Order: ' + drData.draft_order;
            if (drData.oos_skipped) resultMsg = drData.oos_skipped + ' OOS skipped.\n' + resultMsg;
            if (drData.invoice_url) resultMsg += '\nInvoice sent to ' + email + '!';
            alert(resultMsg);
        }
    };

    // ─── Poll draft order progress ────────────────────────────────

    function pollDraftOrderStatus() {
        var bar = document.getElementById('qb-progress');
        bar.style.display = 'block';
        document.getElementById('qb-progress-text').textContent = 'Processing draft orders...';

        var interval = setInterval(async function() {
            var resp = await fetch('/apps/quick-order/api/draft-order/status');
            var s = await resp.json();

            if (s.status === 'processing') {
                document.getElementById('qb-progress-pct').textContent =
                    s.orders_done + '/' + s.orders_total + ' orders';
            }
            if (s.status === 'complete') {
                clearInterval(interval);
                bar.style.display = 'none';
                alert(s.message);
            }
            if (s.status === 'failed') {
                clearInterval(interval);
                bar.style.display = 'none';
                alert('Failed: ' + (s.error || 'Unknown error'));
            }
        }, 5000);
    }

    // ─── Manual catalog refresh ──────────────────────────────────

    window.refreshCatalog = async function() {
        if (!confirm('Refresh product catalog from Shopify? This may take 1-2 minutes.')) return;

        // Hide table, show progress
        document.querySelector('#qb-table tbody').innerHTML =
            '<tr><td colspan="5" class="qb-empty">&#x23F3; Starting catalog refresh&hellip;</td></tr>';

        var resp = await fetch('/apps/quick-order/api/products/refresh', { method: 'POST' });
        var data = await resp.json();

        if (data.status === 'started') {
            pollCatalogStatus(function() {
                loadProducts();
            });
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
                var name = (cols[1] || '').trim();
                var qty = parseInt(cols[2]) || 1;

                // Skip header row
                if (sku === 'SKU' || sku === 'SKU_or_Product_Name') return;

                // Match by SKU first, then by product name
                var product = products.find(function(p) {
                    return (sku && p.sku === sku) ||
                           (sku && p.title.toLowerCase() === sku.toLowerCase()) ||
                           (name && p.title.toLowerCase() === name.toLowerCase());
                });
                if (product) {
                    cartItems[product.variant_id] = qty;
                    count++;
                }
            });

            var status = document.getElementById('qb-csv-status');
            status.style.display = 'block';
            status.textContent = count + ' products matched from CSV';

            renderProducts(document.getElementById('qb-search').value);
        };
        reader.readAsText(file);
    };

    // ─── Drag & drop (on whole page) ─────────────────────────────

    (function() {
        document.addEventListener('dragover', function(e) { e.preventDefault(); });
        document.addEventListener('drop', function(e) {
            e.preventDefault();
            var file = e.dataTransfer.files[0];
            if (file && (file.name.endsWith('.csv') || file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
                handleCSV({ files: [file] });
            }
        });
    })();

    // ─── Kick off ─────────────────────────────────────────────────

    loadProducts();
})();
</script>
