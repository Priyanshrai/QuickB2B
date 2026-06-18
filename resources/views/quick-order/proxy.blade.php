{{-- Quick Order — Custom CSS, 0 CDN --}}

@include('quick-order.partials.styles')

<div class="qb-app">

<input type="file" id="qb-csv-input" accept=".csv" hidden onchange="handleCSV(this)">

<div id="qb-progress" hidden>
    <span id="qb-progress-text">Updating product catalog...</span>
    <small id="qb-progress-pct"></small>
</div>

<header class="qb-header">
    <h1>Quick Order</h1>
    <p>Bulk order your products in one click. Built for B2B wholesale.</p>
</header>

<main class="qb-main">

    <div class="qb-bar">
        <button onclick="document.getElementById('qb-csv-input').click()">CSV</button>
        <button onclick="window.open('/apps/quick-order/sample-csv')">Sample</button>
        <span class="qb-sep"></span>
        <button onclick="selectAllVisible()">Select All</button>
        <button onclick="clearTableQty()">Deselect</button>
        <button onclick="refreshCatalog()">Refresh</button>
    </div>

    <small id="qb-csv-status" hidden></small>

    <input type="search" id="qb-search" class="qb-search" placeholder="Search by name, SKU or tags..." oninput="filterProducts()">

    <div class="qb-card">
    <table id="qb-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Tags</th>
                <th class="qb-col-price">Price</th>
                <th class="qb-col-stock">Stock</th>
                <th class="qb-col-qty">Qty</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="6">Loading products...</td></tr>
        </tbody>
    </table>

    <div id="qb-pagination"></div>
    </div>

    <div class="qb-bar" style="margin-top:12px">
        <button id="qb-btn-draft" onclick="smartCart('draft')" class="btn-primary">Draft Order</button>
        <button id="qb-btn-ajax" onclick="smartCart('ajax')" class="btn-dark">Bulk to Cart</button>
        <button id="qb-btn-permalink" onclick="smartCart('permalink')">Add to Cart</button>
        <button id="qb-clear-cart" onclick="clearShopifyCart()" class="btn-danger">Clear Shopify Cart</button>
    </div>
    <p id="qb-selected-info" style="text-align:center;font-size:12px;color:#6d7175;margin-top:4px">All products included (qty=1)</p>

</main>

<footer class="qb-footer">
    <div class="qb-help">
        <p><strong>Tip:</strong> If no quantities entered, all products in catalog are included (qty=1).</p>
        <p><strong>Draft Order</strong> — Works always. Creates draft order + emails invoice link.</p>
        <p><strong>Bulk to Cart</strong> — Uses AJAX cart API. May not work on all stores.</p>
        <p><strong>Add to Cart</strong> — Fast permalink. Limited to 200 items.</p>
    </div>
    <small>Powered by QuickB2B — Built for wholesale &amp; B2B ordering</small>
</footer>

</div>{{-- .qb-app --}}

@include('quick-order.partials.scripts')

</div>
