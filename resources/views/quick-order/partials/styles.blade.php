<style>
/* Scoped under .qb-app — no theme conflicts */
.qb-app             { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; color:#202223; font-size:14px }
.qb-app td          { font-size:13px }
.qb-app *,
.qb-app *::before,
.qb-app *::after    { box-sizing:border-box }
.qb-app th,
.qb-app td          { padding:6px 8px; text-align:left }
.qb-app th          { font-size:11px; font-weight:600; color:#6d7175; text-transform:uppercase }

/* Card + Table */
.qb-card            { background:#fff; border-radius:6px; border:1px solid #e4e5e7; padding:16px 20px; margin-bottom:16px; overflow-x:auto }
#qb-table           { border-collapse:collapse; font-size:13px; width:100% }
#qb-table thead th  { border-bottom:1px solid #d5d8db }
#qb-table tbody tr:hover { background:#f9fafb }
.qb-col-price       { text-align:right; width:85px }
.qb-col-sku         { width:100px; word-break:break-all; font-size:12px }
.qb-col-price       { text-align:right; width:85px }
.qb-col-stock       { text-align:center; width:60px }
.qb-stock-pill      { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; line-height:1.2; white-space:nowrap }
.qb-stock-ok        { background:#e8f5e9; color:#2e7d32 }
.qb-stock-low       { background:#fff3e0; color:#e65100 }
.qb-stock-oos       { background:#ffebee; color:#c62828 }
.qb-col-qty         { text-align:center; width:80px; padding:4px 4px !important }
.qb-col-qty input   { width:64px; padding:4px 2px; text-align:center; border:1px solid #c9cccf; border-radius:3px; font-size:13px; font-family:inherit; box-sizing:border-box }
.qb-col-qty input:focus { outline:none; border-color:#008060 }

/* Grouped rows */
.qb-product-row td  { background:#f6f6f7; font-weight:600; padding-top:10px; padding-bottom:6px; border:none !important }
.qb-variant-row td  { padding-top:4px; padding-bottom:4px; border-bottom:1px solid #f0f0f0 }
.qb-variant-label   { color:#4a4f55 }

/* Buttons */
.qb-app button      { padding:6px 13px; border-radius:4px; border:1px solid #c9cccf; background:#fff; color:#202223; cursor:pointer; font-size:12px; font-family:inherit; font-weight:500; line-height:1.4; transition:background .15s,border-color .15s,color .15s }
.qb-app button:hover { background:#f6f6f7; border-color:#b5b9bd }
.btn-primary        { background:#008060; border-color:#008060; color:#fff; font-weight:600 }
.btn-primary:hover  { background:#006e52; border-color:#006e52 }
.btn-upload         { background:#e8f5e9; border-color:#2e7d32; color:#2e7d32; font-weight:600 }
.btn-upload:hover   { background:#c8e6c9; border-color:#1b5e20 }
.btn-dark           { background:#333; border-color:#333; color:#fff; font-weight:600 }
.btn-dark:hover     { background:#1a1a1a; border-color:#1a1a1a }
.btn-danger         { border-color:#c52f21; color:#c52f21 }
.btn-danger:hover   { background:#f6f6f7 }

/* Layout */
.qb-header          { text-align:center; margin-bottom:24px }
.qb-header p        { color:#6d7175; margin:0 }
.qb-main            { max-width:960px; margin:0 auto; padding:0 20px }
.qb-bar             { display:flex; gap:6px; flex-wrap:wrap; justify-content:center; margin-bottom:8px; align-items:center }
.qb-sep             { width:1px; height:18px; background:#d5d8db; margin:0 4px }
.qb-search-row      { display:flex; gap:8px; margin-bottom:12px }
.qb-filter          { padding:9px 8px; border:1px solid #c9cccf; border-radius:5px; font-size:13px; font-family:inherit; background:#fff; min-width:100px; flex-shrink:0 }
.qb-search          { width:100%; padding:9px 12px; border:1px solid #c9cccf; border-radius:5px; font-size:14px; font-family:inherit; box-sizing:border-box }

/* Footer */
.qb-footer          { text-align:center; padding:20px 0; color:#8d9298; font-size:12px }
.qb-help            { text-align:left; max-width:600px; margin:0 auto 16px; padding:12px 16px; background:#f6f6f7; border-radius:6px; font-size:12px; color:#6d7175; line-height:1.6 }
.qb-help p          { margin:0 0 6px }
.qb-help p:last-child { margin:0 }
.qb-help strong     { color:#4a4f55 }

/* Pagination */
#qb-pagination      { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-top:12px; padding-top:12px; border-top:1px solid #e4e5e7; font-size:12px; color:#6d7175 }
.qb-pg-actions      { display:flex; align-items:center; gap:6px }
.qb-pg-actions select,
.qb-pg-actions button { padding:3px 6px; font-size:12px }

/* Progress bar */
#qb-progress        { padding:10px 16px; background:#008060; color:#fff; text-align:center; font-weight:600; border-radius:6px; margin-bottom:16px }
#qb-progress small  { opacity:.8; margin-left:8px }

/* Mobile */
@media (max-width:480px) {
  .qb-app           { font-size:13px }
  .qb-header h1     { font-size:18px }
  .qb-header p      { font-size:13px }
  .qb-main          { padding:0 12px }
  .qb-app button    { padding:5px 10px; font-size:11px }
  .qb-search        { padding:7px 10px; font-size:13px }
  .qb-filter        { padding:7px 6px; font-size:12px; min-width:80px }
  .qb-help          { margin:0 0 16px; padding:10px 12px; font-size:11px }
  #qb-pagination    { justify-content:center; gap:6px; font-size:11px }
  .qb-pg-actions    { gap:4px }
  .qb-pg-actions select,
  .qb-pg-actions button { font-size:11px }
  .qb-product-row td { border:none !important }
  .qb-variant-row td { border-bottom:1px solid #f0f0f0 }
}
</style>

