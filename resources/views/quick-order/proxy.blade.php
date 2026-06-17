{{-- Quick Order — App Proxy (embedded in store theme) --}}

@include('quick-order.partials.styles')

{{-- Progress banner (hidden by default, shown during background catalog refresh) --}}
<div id="qb-progress" style="display:none;background:var(--qb-green);color:#fff;padding:8px 20px;font-size:13px;font-weight:600;text-align:center;">
    <span id="qb-progress-text">🔄 Updating product catalog...</span>
    <span id="qb-progress-pct" style="margin-left:8px;opacity:.8;"></span>
</div>

<div class="qb-container">

    {{-- Header --}}
    <div class="qb-header">
        <h1>&#x26A1; Quick Order</h1>
        <p>Type quantities and add everything to cart in one click &mdash; no more clicking &ldquo;Add to Cart&rdquo; hundreds of times.</p>
    </div>

    {{-- Search --}}
    <div class="qb-card">
        <input type="text" class="qb-search" id="qb-search"
               placeholder="&#x1F50D; Search products by name or SKU..."
               oninput="filterProducts()">
    </div>

    {{-- CSV Upload --}}
    <div class="qb-card">
        <div class="qb-csv-zone" id="qb-csv-zone"
             onclick="document.getElementById('qb-csv-input').click()">
            <p>&#x1F4E4; <strong>Drag &amp; drop</strong> a CSV or Excel file here, or click to browse</p>
            <p style="font-size:12px;margin-top:4px;">First column: SKU or Product Name. Second column: Quantity.</p>
        </div>
        <input type="file" id="qb-csv-input" accept=".csv,.xlsx,.xls"
               style="display:none;" onchange="handleCSV(this)">
        <div id="qb-csv-status" style="font-size:13px;color:var(--qb-green);display:none;"></div>
    </div>

    {{-- Product Table --}}
    <div class="qb-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h2 style="margin:0;">Products</h2>
            <div style="display:flex;gap:8px;">
                <button onclick="smartCart('permalink')" class="qb-btn" style="background:var(--qb-green);color:#fff;padding:6px 14px;font-size:12px;" title="Fast — adds via URL. Best for <300 products.">
                    &#x26A1; Quick Add
                </button>
                <button onclick="smartCart('ajax')" class="qb-btn" style="background:#4a4f55;color:#fff;padding:6px 14px;font-size:12px;" title="Reliable — adds via AJAX. Works for any count.">
                    &#x1F6D2; Bulk Add
                </button>
                <button onclick="smartCart('draft')" class="qb-btn" style="background:#b98900;color:#fff;padding:6px 14px;font-size:12px;" title="B2B — creates draft order + sends invoice.">
                    &#x1F4E7; Submit Order
                </button>
                <button id="qb-clear-cart" class="qb-btn" style="background:#d82c0d;color:#fff;padding:6px 14px;font-size:12px;" onclick="clearShopifyCart()">
                    &#x1F5D1;
                </button>
            </div>
        </div>
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
        <button class="qb-btn qb-btn-primary" id="qb-add-all" disabled
                onclick="addSelectedToCart()">
            &#x1F6D2; Add All to Cart
            <span class="qb-cart-count" id="qb-cart-count">0</span>
        </button>
    </div>

    {{-- Footer --}}
    <div class="qb-footer">
        <p>Powered by QuickB2B &mdash; Built for wholesale &amp; B2B ordering</p>
    </div>

</div>

@include('quick-order.partials.scripts')
