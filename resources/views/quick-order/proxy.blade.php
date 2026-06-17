{{-- Quick Order — App Proxy (embedded in store theme) --}}

@include('quick-order.partials.styles')

{{-- Progress banner --}}
<div id="qb-progress" style="display:none;background:var(--qb-green);color:#fff;padding:8px 20px;font-size:13px;font-weight:600;text-align:center;">
    <span id="qb-progress-text">Updating product catalog...</span>
    <span id="qb-progress-pct" style="margin-left:8px;opacity:.8;"></span>
</div>

<div class="qb-container">

    {{-- Header --}}
    <div class="qb-header">
        <h1>Quick Order</h1>
        <p>Bulk order your products in one click. Built for B2B wholesale.</p>
    </div>

    {{-- Action Bar: Search + CSV + Buttons --}}
    <div class="qb-card" style="padding:14px 20px;">
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="text" class="qb-search" id="qb-search"
                   placeholder="&#x1F50D; Search by name, SKU or tags..."
                   oninput="filterProducts()"
                   style="margin-bottom:0;flex:1;min-width:180px;">

            <button onclick="document.getElementById('qb-csv-input').click()"
                    class="qb-btn" style="background:var(--qb-gray-100);color:var(--qb-gray-600);padding:8px 14px;font-size:12px;white-space:nowrap;">
                &#x1F4E4; CSV
            </button>
            <a href="/apps/quick-order/sample-csv" download
               style="font-size:11px;color:var(--qb-green);text-decoration:none;white-space:nowrap;">
                &#x1F4E5; Sample
            </a>
            <input type="file" id="qb-csv-input" accept=".csv,.xlsx,.xls"
                   style="display:none;" onchange="handleCSV(this)">

            <span style="color:var(--qb-gray-200);margin:0 4px;">|</span>

            <button onclick="refreshCatalog()" class="qb-btn"
                    style="background:var(--qb-gray-100);color:var(--qb-gray-600);padding:8px 14px;font-size:12px;white-space:nowrap;"
                    title="Refresh product catalog from Shopify">
                &#x1F504; Refresh
            </button>

            <span style="color:var(--qb-gray-200);margin:0 4px;">|</span>

            <button onclick="smartCart('permalink')" class="qb-btn"
                    style="background:var(--qb-green);color:#fff;padding:8px 14px;font-size:12px;white-space:nowrap;"
                    title="Fast. Best for <300 products.">
                &#x26A1; Quick Add
            </button>
            <button onclick="smartCart('ajax')" class="qb-btn"
                    style="background:#4a4f55;color:#fff;padding:8px 14px;font-size:12px;white-space:nowrap;"
                    title="Reliable. Works for any count.">
                &#x1F6D2; Bulk Add
            </button>
            <button onclick="smartCart('draft')" class="qb-btn"
                    style="background:#b98900;color:#fff;padding:8px 14px;font-size:12px;white-space:nowrap;"
                    title="B2B. Creates draft order + sends invoice.">
                &#x1F4E7; Submit Order
            </button>

            <span style="color:var(--qb-gray-200);margin:0 4px;">|</span>

            <button onclick="clearTableQty()" class="qb-btn"
                    style="background:var(--qb-gray-100);color:var(--qb-gray-600);padding:8px 14px;font-size:12px;white-space:nowrap;"
                    title="Clear all quantities in the table">
                &#x274E; Clear
            </button>
            <button id="qb-clear-cart" class="qb-btn"
                    style="background:#d82c0d;color:#fff;padding:8px 14px;font-size:12px;white-space:nowrap;"
                    onclick="clearShopifyCart()" title="Empty your Shopify cart">
                &#x1F5D1; Clear Cart
            </button>
        </div>
        <div id="qb-csv-status" style="font-size:11px;color:var(--qb-green);display:none;margin-top:6px;"></div>
    </div>

    {{-- Product Table --}}
    <div class="qb-card">
        <table class="qb-table" id="qb-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Tags</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th style="width:100px;">Qty</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="qb-empty">Loading products&hellip;</td></tr>
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="qb-footer">
        <p>Powered by QuickB2B &mdash; Built for wholesale &amp; B2B ordering</p>
    </div>

</div>

@include('quick-order.partials.scripts')
