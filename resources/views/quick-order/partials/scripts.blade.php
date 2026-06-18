{{-- Quick Order JavaScript — wrapped in IIFE, only 4 globals exposed --}}

<script>
(function() {
    var products = [];
    var cartItems = {};
    var currentPage = 1;
    var currentPerPage = 50;
    var currentQuery = '';
    var currentFilter = 'all';
    var totalProducts = 0;
    var totalPages = 1;

    // ─── HTML escape helper ──────────────────────────────────────

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ─── Load products from backend ───────────────────────────────

    async function loadProducts(query, page, perPage) {
        query = query || '';
        page = page || 1;
        perPage = perPage || currentPerPage;

        var url = '/apps/quick-order/api/products?page=' + page + '&per_page=' + perPage;
        if (query) url += '&q=' + encodeURIComponent(query);
        if (currentFilter && currentFilter !== 'all') url += '&filter=' + currentFilter;

        try {
            var resp = await fetch(url);
            var data = await resp.json();

            if (data.source === 'waiting') {
                var tbody = document.querySelector('#qb-table tbody');
                tbody.innerHTML = '<tr><td colspan="5">Loading product catalog...</td></tr>';
                pollCatalogStatus(function() { loadProducts(query, page, perPage); });
                return;
            }

            products = data.products || [];
            totalProducts = data.total || 0;
            totalPages = data.totalPages || 1;
            currentPage = page;
            currentPerPage = perPage;
            currentQuery = query;
            currentFilter = currentFilter || 'all';

            renderProducts();
            renderPagination();
        } catch (e) {
            document.querySelector('#qb-table tbody').innerHTML =
                '<tr><td colspan="5">Could not load products. Please try again.</td></tr>';
        }
    }

    // ─── Search (server-side) ─────────────────────────────────────

    var searchTimer;
    window.filterProducts = function() {
        clearTimeout(searchTimer);
        var q = document.getElementById('qb-search').value;
        var f = document.getElementById('qb-filter');
        currentFilter = f ? f.value : 'all';
        searchTimer = setTimeout(function() {
            currentQuery = q;
            loadProducts(q, 1, currentPerPage);
        }, 300);
    };

    // ─── Pagination ───────────────────────────────────────────────

    window.goToPage = function(page) {
        if (page < 1 || page > totalPages) return;
        // Loading state
        var btn = document.querySelector('.qb-pg-actions button[onclick*="goToPage(' + page + ')"]');
        if (btn) { btn.disabled = true; btn.textContent = '...'; }
        loadProducts(currentQuery, page, currentPerPage);
    };

    window.changePerPage = function(perPage) {
        perPage = parseInt(perPage);
        currentPerPage = perPage;
        loadProducts(currentQuery, 1, perPage);
    };

    function renderPagination() {
        var container = document.getElementById('qb-pagination');
        if (!container) return;

        var from = totalProducts === 0 ? 0 : (currentPage - 1) * currentPerPage + 1;
        var to = Math.min(currentPage * currentPerPage, totalProducts);

        var perPageOptions = [50, 100, 200, 500];
        var optionsHtml = perPageOptions.map(function(n) {
            return '<option value="' + n + '"' + (currentPerPage === n ? ' selected' : '') + '>' + n + ' per page</option>';
        }).join('');

        container.innerHTML =
            '<span class="qb-pg-info">Showing ' + from + '–' + to + ' of ' + totalProducts + '</span>' +
            '<span class="qb-pg-actions">' +
                '<select onchange="changePerPage(this.value)">' + optionsHtml + '</select>' +
                '<button onclick="goToPage(' + (currentPage - 1) + ')"' + (currentPage <= 1 ? ' disabled' : '') + '>Prev</button>' +
                '<span>Page ' + currentPage + ' of ' + totalPages + '</span>' +
                '<button onclick="goToPage(' + (currentPage + 1) + ')"' + (currentPage >= totalPages ? ' disabled' : '') + '>Next</button>' +
            '</span>';
    }

    // ─── Background catalog refresh progress ──────────────────────

    function pollCatalogStatus(onComplete) {
        var interval = setInterval(function() {
            fetch('/apps/quick-order/api/products/status')
                .then(function(r) { return r.json(); })
                .then(function(s) {
                    if (s.status === 'starting' || s.status === 'querying' || s.status === 'processing' || s.status === 'downloading') {
                        var bar = document.getElementById('qb-progress');
                        bar.removeAttribute('hidden');
                        document.getElementById('qb-progress-text').textContent = 'Updating product catalog...';
                        document.getElementById('qb-progress-pct').textContent = s.percent + '% (' + (s.records || '') + ' records)';
                    }
                    if (s.status === 'complete') {
                        clearInterval(interval);
                        document.getElementById('qb-progress').setAttribute('hidden', '');
                        if (onComplete) onComplete();
                    }
                    if (s.status === 'failed') {
                        clearInterval(interval);
                        document.getElementById('qb-progress').setAttribute('hidden', '');
                    }
                });
        }, 3000);
    }

    // ─── Render product table rows (grouped by product) ──────────

    function renderProducts() {
        var tbody = document.querySelector('#qb-table tbody');

        if (!products.length) {
            tbody.innerHTML = '<tr><td colspan="5">No products found.</td></tr>';
            return;
        }

        // Group variants by parent product id
        var groups = {};
        var groupOrder = [];
        products.forEach(function(p) {
            var pid = p.id || 'unknown';
            if (!groups[pid]) {
                groups[pid] = { product: p, variants: [] };
                groupOrder.push(pid);
            }
            groups[pid].variants.push(p);
        });

        var html = '';
        groupOrder.forEach(function(pid) {
            var g = groups[pid];
            var p = g.product;
            var variants = g.variants;

            var tagsHtml = '';
            if (p.tags && p.tags !== '{}' && p.tags !== '[]') {
                var tagList = Array.isArray(p.tags) ? p.tags : String(p.tags).split(',');
                tagList = tagList.filter(function(t) { return t && String(t).trim(); }).map(function(t) {
                    var tag = String(t).trim();
                    return tag.length > 15 ? tag.substring(0, 15) + '…' : tag;
                });
                if (tagList.length > 2) {
                    tagsHtml = tagList.slice(0, 2).join(', ') + ', …';
                } else {
                    tagsHtml = tagList.join(', ');
                }
            }
            var collectionsHtml = '';
            if (p.collections && Array.isArray(p.collections) && p.collections.length) {
                var colList = p.collections.filter(function(c) { return c; }).map(function(c) {
                    return c.length > 15 ? c.substring(0, 15) + '…' : c;
                });
                if (colList.length > 2) {
                    collectionsHtml = colList.slice(0, 2).join(', ') + ', …';
                } else {
                    collectionsHtml = colList.join(', ');
                }
            }

            // Product header row
            html += '<tr class="qb-product-row">' +
                '<td colspan="2"><strong>' + escapeHtml(p.title) + '</strong></td>' +
                '<td class="qb-col-price"></td>' +
                '<td class="qb-col-stock">' + variants.length + ' var</td>' +
                '<td></td>' +
                '</tr>';

            // Variant rows
            variants.forEach(function(v) {
                var qty = cartItems[v.variant_id] || '';
                var vLabel = (v.variant_title && v.variant_title !== 'Default Title')
                    ? v.variant_title
                    : 'Default';
                var oos = isOutOfStock(v);
                var disabledAttr = oos ? ' disabled' : '';

                html += '<tr class="qb-variant-row">' +
                    '<td><span class="qb-variant-label">' + escapeHtml(vLabel) + '</span>' +
                        (oos ? ' <em>OOS</em>' : '') + '</td>' +
                    '<td class="qb-col-sku">' + escapeHtml(v.sku || '—') + '</td>' +
                    '<td class="qb-col-price">$' + parseFloat(v.price).toFixed(2) + '</td>' +
                    '<td class="qb-col-stock">' + getStockLabel(v.inventory, v.inventory_tracked) + '</td>' +
                    '<td class="qb-col-qty"><input type="number" min="0" value="' + qty +
                        '" placeholder="0" data-id="' + escapeHtml(v.variant_id) +
                        '" onchange="updateCart(this)"' + disabledAttr + '></td>' +
                    '</tr>';
            });
        });

        tbody.innerHTML = html;
        updateCartCount();
    }

    function getStockLabel(inventory, tracked) {
        if (!tracked) {
            return '<span class="qb-stock-pill qb-stock-ok">∞</span>';
        }
        if (inventory > 10) {
            return '<span class="qb-stock-pill qb-stock-ok">' + inventory + '</span>';
        }
        if (inventory > 0) {
            return '<span class="qb-stock-pill qb-stock-low">' + inventory + '</span>';
        }
        return '<span class="qb-stock-pill qb-stock-oos">0</span>';
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
        if (!confirm('Remove all quantities you entered?')) return;
        cartItems = {};
        // Also hide CSV status
        var csvStatus = document.getElementById('qb-csv-status');
        if (csvStatus) { csvStatus.setAttribute('hidden', ''); }
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
        var csvStatus = document.getElementById('qb-csv-status');
        if (csvStatus) { csvStatus.setAttribute('hidden', ''); }
        setTimeout(function() { location.reload(); }, 500);
    };

    function updateCartCount() {
        var count = Object.keys(cartItems).length;
        var info = document.getElementById('qb-selected-info');
        if (info) {
            info.textContent = count > 0 ? count + ' product(s) selected' : 'All products included (qty=1)';
        }
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
        // Loading spinner — disable clicked button
        var btnMap = { draft: '#qb-btn-draft', ajax: '#qb-btn-ajax', permalink: '#qb-btn-permalink' };
        var clickedBtn = document.querySelector(btnMap[method]);
        if (clickedBtn) { clickedBtn.disabled = true; }

        try {
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
            var f = document.getElementById('qb-filter');
            var filterVal = f ? f.value : 'all';
            var addBody = { q: searchQ };
            if (filterVal !== 'all') addBody.filter = filterVal;
            var resp = await fetch('/apps/quick-order/api/add-all', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(addBody),
            });
            var data = await resp.json();
            items = (data.variants || []).map(function(v) {
                return { id: 'gid://shopify/ProductVariant/' + v, qty: 1 };
            });
        }

        if (!items.length) { alert('No products to add.'); return; }

        // ── OOS choice dialog (ALL methods) ──────────────────────
        var filterOos = true; // default: in-stock only
        var hasOos = items.some(function(item) {
            var p = products.find(function(prod) {
                return (prod.variant_id || '').indexOf(item.id) !== -1;
            });
            return p && isOutOfStock(p);
        });

        if (hasOos) {
            filterOos = confirm(
                'Some items are out of stock.\n\nAdd only in-stock items?'
            );
        }

        // ── Quick Add limit ──────────────────────────────────────
        var QUICK_ADD_LIMIT = 200;
        if (method === 'permalink' && items.length > QUICK_ADD_LIMIT) {
            alert(items.length + ' items is too many.\n\nUse "Bulk to Cart" or "Draft Order" instead.');
            return;
        }

        // ── Method 1: Permalink (Quick Add) ──
        if (method === 'permalink') {
            var params = items.map(function(i) { return (i.id.split('/').pop()) + ':' + i.qty; }).join(',');
            window.location.href = '/cart/' + params + '?storefront=true';
            return;
        }

        // ── Method 2: AJAX Cart (Bulk Add) ──
        if (method === 'ajax') {
            try {
                var ajaxItems = items.map(function(i) { return { id: i.id.split('/').pop(), qty: i.qty }; });
                await ajaxAddToCart(ajaxItems);
                window.location.href = '/cart';
            } catch (e) {
                alert('Could not add to cart.\n\nTry "Draft Order" instead — it always works.');
            }
            return;
        }

        // ── Method 3: Draft Order ──
        if (method === 'draft') {
            var email = prompt('Enter your email for invoice:', '');
            if (!email) return;

            // Basic email format check
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email (e.g., name@example.com).');
                return;
            }

            var drResp = await fetch('/apps/quick-order/api/draft-order', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: items, email: email, filter_oos: filterOos }),
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
            if (!drData.filter_oos && drData.filter_oos !== undefined) resultMsg = 'Includes backorder.\n' + resultMsg;
            if (drData.invoice_url) resultMsg += '\nInvoice sent to ' + email + '!';
            alert(resultMsg);
        }

        } finally {
            if (clickedBtn) { clickedBtn.disabled = false; }
        }
    };

    // ─── Poll draft order progress ────────────────────────────────

    function pollDraftOrderStatus() {
        var bar = document.getElementById('qb-progress');
        bar.removeAttribute('hidden');
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
                bar.setAttribute('hidden', '');
                alert(s.message);
            }
            if (s.status === 'failed') {
                clearInterval(interval);
                bar.setAttribute('hidden', '');
                alert('Failed: ' + (s.error || 'Unknown error'));
            }
        }, 5000);
    }

    // ─── Manual catalog refresh ──────────────────────────────────

    window.refreshCatalog = async function() {
        if (!confirm('Update product list from your store? Takes about a minute.')) return;

        var btn = document.getElementById('qb-btn-refresh');
        if (btn) { btn.disabled = true; btn.textContent = 'Refreshing...'; }

        // Hide table, show progress
        document.querySelector('#qb-table tbody').innerHTML =
            '<tr><td colspan="5">Starting catalog refresh...</td></tr>';

        var resp = await fetch('/apps/quick-order/api/products/refresh', { method: 'POST' });
        var data = await resp.json();

        if (data.status === 'already_running') {
            alert('Catalog refresh is already in progress (' + data.percent + '% done). Please wait.');
            pollCatalogStatus(function() { loadProducts(); if (btn) { btn.disabled = false; btn.textContent = 'Refresh'; } });
            return;
        }

        if (data.status === 'started') {
            pollCatalogStatus(function() {
                loadProducts();
                if (btn) { btn.disabled = false; btn.textContent = 'Refresh'; }
            });
        } else {
            if (btn) { btn.disabled = false; btn.textContent = 'Refresh'; }
        }
    };

    // ─── Select all / Deselect all visible ──────────────────────

    window.selectAllVisible = async function() {
        var q = document.getElementById('qb-search').value;
        var f = document.getElementById('qb-filter');
        var filter = f ? f.value : 'all';
        var isFiltered = filter !== 'all';

        var useFull = isFiltered || confirm('Select all ' + totalProducts + ' matching products?\n\nOK = All pages\nCancel = This page only');

        if (useFull) {
            var body = { q: q };
            if (isFiltered) body.filter = filter;
            var resp = await fetch('/apps/quick-order/api/add-all', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            var data = await resp.json();
            (data.variants || []).forEach(function(vid) {
                cartItems['gid://shopify/ProductVariant/' + vid] = 1;
            });
            updateCartCount();
            renderProducts(q);
            var msg = (data.count || 0) + ' product(s) selected';
            if (isFiltered) msg += ' (filter: ' + filter + ')';
            alert(msg);
        } else {
            var inputs = document.querySelectorAll('#qb-table tbody input[type=\"number\"]');
            var count = 0;
            inputs.forEach(function(input) {
                if (!input.disabled) { input.value = 1; cartItems[input.dataset.id] = 1; count++; }
            });
            updateCartCount();
            renderProducts(q);
            if (count) alert(count + ' product(s) selected on this page.');
            else alert('No products on this page.');
        }
    };

    // ─── CSV upload ───────────────────────────────────────────────

    window.handleCSV = function(input) {
        var file = input.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function(e) {
            var lines = e.target.result.split('\n').filter(function(l) { return l.trim(); });
            var cartBefore = Object.keys(cartItems).length;  // variants already selected before CSV

            lines.forEach(function(line) {
                // Skip comments and empty lines
                var trimmed = line.trim();
                if (!trimmed || trimmed.startsWith('#') || trimmed.startsWith('//')) return;

                var cols = line.split(/[,\t]/);
                var sku = (cols[0] || '').trim();
                var name = (cols[1] || '').trim();
                var qty = parseInt(cols[2]) || 1;
                var tag = (cols[3] || '').trim();

                // Skip header row
                if (sku === 'SKU' || sku === 'SKU_or_Product_Name') return;

                // ── Tag match: select ALL products with this tag ──
                if (tag) {
                    var tagLower = tag.toLowerCase();
                    products.forEach(function(p) {
                        if (p.tags && String(p.tags).toLowerCase().indexOf(tagLower) !== -1) {
                            cartItems[p.variant_id] = qty;
                        }
                    });
                    return;
                }

                var searchTerm = sku || name;
                if (!searchTerm) return;

                // 1. Exact SKU match → select that specific variant
                var bySku = products.find(function(p) {
                    return p.sku && p.sku === searchTerm;
                });
                if (bySku) {
                    cartItems[bySku.variant_id] = qty;
                    return;  // next CSV line
                }

                // 2. Product Name / Product ID / Variant ID match
                var searchLower = searchTerm.toLowerCase();
                var isGid = sku && sku.startsWith('gid://');
                var numId = isGid ? sku.split('/').pop() : (/^\d+$/.test(sku) ? sku : null);

                products.forEach(function(p) {
                    var matchesName = p.title && p.title.toLowerCase() === searchLower;
                    var matchesId = (isGid && (p.id === sku || p.variant_id === sku))
                                 || (numId && (p.id.endsWith('/' + numId) || p.variant_id.endsWith('/' + numId)));
                    var matchesName2 = name && p.title && p.title.toLowerCase() === name.toLowerCase();

                    if (matchesName || matchesId || matchesName2) {
                        cartItems[p.variant_id] = qty;
                    }
                });
            });

            // Calculate accurate counts from cartItems (no double-counting)
            var variantIds = Object.keys(cartItems);
            var count = variantIds.length;
            var totalItems = 0;
            variantIds.forEach(function(vid) { totalItems += cartItems[vid]; });

            var status = document.getElementById('qb-csv-status');
            status.removeAttribute('hidden');
            status.textContent = count + ' variant(s) selected | ' + totalItems + ' total items (qty included)';

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
            if (file && file.name.endsWith('.csv')) {
                handleCSV({ files: [file] });
            }
        });
    })();

    // ─── Kick off ─────────────────────────────────────────────────

    loadProducts();
})();
</script>
