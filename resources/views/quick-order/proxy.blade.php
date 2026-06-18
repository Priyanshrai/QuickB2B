{{-- Quick Order — Minimal custom CSS --}}

@include('quick-order.partials.styles')

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
        <button onclick="selectAllVisible()">Select All</button>
        <button onclick="clearTableQty()">Deselect</button>
        <button onclick="refreshCatalog()">Refresh</button>
    </div>

    <small id="qb-csv-status" hidden></small>

    <input type="search" id="qb-search" class="qb-search" placeholder="Search by name, SKU or tags..." oninput="filterProducts()">

    <table id="qb-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Tags</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="6">Loading products...</td></tr>
        </tbody>
    </table>

    <div id="qb-pagination"></div>

    <div class="qb-bar" style="margin-top:12px">
        <button id="qb-btn-draft" onclick="smartCart('draft')" class="btn-primary">Draft Order</button>
        <button id="qb-btn-ajax" onclick="smartCart('ajax')" class="btn-dark">Bulk to Cart</button>
        <button id="qb-btn-permalink" onclick="smartCart('permalink')">Add to Cart</button>
        <button id="qb-clear-cart" onclick="clearShopifyCart()" class="btn-danger">Clear Shopify Cart</button>
    </div>

</main>

<footer class="qb-footer">
    <div class="qb-help">
        <p><strong>Tip:</strong> If no quantities are entered, all visible products are included (qty = 1). Select specific products by entering quantities in the Qty column.</p>
        <p><strong>Draft Order</strong> — Works always. Creates a draft order and emails you an invoice link.</p>
        <p><strong>Bulk to Cart</strong> — Uses AJAX cart API. May not work on all stores. Adds products in batches of 50.</p>
        <p><strong>Add to Cart</strong> — Fast permalink method. Limited to 200 items. Best for smaller orders.</p>
    </div>
    <small>Powered by QuickB2B — Built for wholesale &amp; B2B ordering</small>
</footer>

@include('quick-order.partials.scripts')
