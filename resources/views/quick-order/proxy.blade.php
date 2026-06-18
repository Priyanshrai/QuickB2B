{{-- Quick Order — Custom CSS, 0 CDN --}}

@include('quick-order.partials.styles')

<div class="qb-app">

<input type="file" id="qb-csv-input" accept=".csv" hidden onchange="handleCSV(this)">

<div id="qb-progress" hidden>
    <span id="qb-progress-text">Updating product catalog...</span>
    <small id="qb-progress-pct"></small>
</div>

<header class="qb-header">
    <p>Bulk order your products in one click.</p>
</header>

<main class="qb-main">

    <div class="qb-bar">
        <button class="btn-upload" onclick="document.getElementById('qb-csv-input').click()">📄 Upload CSV</button>
        <button onclick="window.open('/apps/quick-order/sample-csv')">📥 Sample</button>
        <span class="qb-sep"></span>
        <button onclick="selectAllVisible()">☑️ Select All</button>
        <button onclick="clearTableQty()">✖️ Deselect</button>
        <button id="qb-btn-refresh" onclick="refreshCatalog()">🔄 Refresh</button>
    </div>

    <small id="qb-csv-status" hidden></small>

    <div class="qb-search-row">
        <select id="qb-filter" class="qb-filter" onchange="filterProducts()">
            <option value="all">🔍 All</option>
            <option value="title">📦 Product Name</option>
            <option value="sku">🔢 SKU</option>
            <option value="tag">🏷️ Tag</option>
            <option value="collection">📁 Collection</option>
        </select>
        <input type="search" id="qb-search" class="qb-search" placeholder="Type to search..." oninput="filterProducts()">
    </div>

    <div class="qb-card">
    <table id="qb-table">
        <thead>
            <tr>
                <th>Product / Variant</th>
                <th style="width:80px">SKU</th>
                <th class="qb-col-price">Price</th>
                <th class="qb-col-stock">Stock</th>
                <th class="qb-col-qty">Qty</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="5">Loading products...</td></tr>
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
        <p><strong>📄 CSV Columns:</strong> Col1 (SKU/Name/ID), Col2 (Name), Col3 (Qty), Col4 (Tag)</p>
        <p>🔹 <strong>SKU</strong> = 1 variant &nbsp;|&nbsp; 🔹 <strong>Name</strong> = ALL variants &nbsp;|&nbsp; 🔹 <strong>Product/Variant GID</strong> = specific/ALL</p>
        <p>🔹 <strong>Tag</strong> = all products with that tag &nbsp;|&nbsp; Col 2+3+4 are optional, Qty defaults to 1</p>
        <p style="margin-top:8px"><strong>Draft Order</strong> — Works always. Creates draft order + emails invoice link.</p>
        <p><strong>Bulk to Cart</strong> — Uses AJAX cart API. May not work on all stores.</p>
        <p><strong>Add to Cart</strong> — Fast permalink. Limited to 200 items.</p>
    </div>
    <small>Powered by QuickB2B — Built for Everyone With Love</small>
</footer>

</div>{{-- .qb-app --}}

@include('quick-order.partials.scripts')

</div>
