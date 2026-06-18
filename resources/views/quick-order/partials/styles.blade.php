<style>
*,*::before,*::after { box-sizing:border-box }
body                 { margin:0; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; color:#202223; font-size:14px }
table                { border-collapse:collapse; width:100% }
th,td                { padding:8px 10px; text-align:left; border-bottom:1px solid #e4e5e7 }
th                   { font-size:11px; font-weight:600; color:#6d7175; text-transform:uppercase }
button               { padding:7px 14px; border-radius:5px; border:1px solid #c9cccf; background:#fff; cursor:pointer; font-size:13px; font-family:inherit }
.btn-primary         { background:#008060; border-color:#008060; color:#fff; font-weight:600 }
.btn-dark            { background:#333; border-color:#333; color:#fff; font-weight:600 }
.btn-danger          { border-color:#c52f21; color:#c52f21 }
.btn-danger:hover    { background:#c52f21; color:#fff }
.qb-header           { text-align:center; margin-bottom:24px }
.qb-header h1        { font-size:22px; color:#008060; margin:0 0 4px }
.qb-header p         { color:#6d7175; margin:0 }
.qb-main             { max-width:100%; margin:0 auto; padding:0 20px }
.qb-bar              { display:flex; gap:6px; flex-wrap:wrap; justify-content:center; margin-bottom:8px }
.qb-search           { width:100%; padding:9px 12px; border:1px solid #c9cccf; border-radius:5px; font-size:14px; margin-bottom:12px; font-family:inherit }
.qb-footer           { text-align:center; padding:20px 0; color:#8d9298; font-size:12px }
.qb-help             { text-align:left; max-width:600px; margin:0 auto 16px; padding:12px 16px; background:#f6f6f7; border-radius:6px; font-size:12px; color:#6d7175; line-height:1.6 }
.qb-help p           { margin:0 0 6px }
.qb-help p:last-child { margin:0 }
.qb-help strong      { color:#4a4f55 }
#qb-pagination       { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; margin-top:12px; padding-top:12px; border-top:1px solid #e4e5e7; font-size:13px; color:#6d7175 }
.qb-pg-actions       { display:flex; align-items:center; gap:6px }
.qb-pg-actions select,
.qb-pg-actions button { padding:4px 8px; font-size:12px }
#qb-progress         { padding:10px 16px; background:#008060; color:#fff; text-align:center; font-weight:600; border-radius:6px; margin-bottom:16px }
#qb-progress small   { opacity:.8; margin-left:8px }
</style>
