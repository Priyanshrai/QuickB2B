{{-- Quick Order CSS — scoped under .qb-container to not conflict with store theme --}}

<style>
    .qb-container {
        --qb-green: #008060;
        --qb-gray-50: #f6f6f7;
        --qb-gray-100: #e4e5e7;
        --qb-gray-200: #c9cccf;
        --qb-gray-400: #8d9298;
        --qb-gray-600: #4a4f55;
        --qb-gray-800: #202223;
        --qb-radius: 8px;
        font-family: inherit;
        color: var(--qb-gray-800);
        max-width: 960px;
        margin: 0 auto;
        padding: 32px 20px;
        line-height: 1.5;
    }
    .qb-container *,
    .qb-container *::before,
    .qb-container *::after {
        box-sizing: border-box;
    }

    /* ── Header ── */
    .qb-header {
        text-align: center;
        margin-bottom: 32px;
    }
    .qb-header h1 {
        font-size: 24px;
        font-weight: 700;
        color: var(--qb-green);
        margin: 0 0 4px;
    }
    .qb-header p {
        font-size: 14px;
        color: var(--qb-gray-600);
        margin: 0;
    }

    /* ── Card ── */
    .qb-card {
        background: #fff;
        border-radius: var(--qb-radius);
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 20px;
        margin-bottom: 20px;
    }
    .qb-card h2 {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 12px;
    }

    /* ── Search ── */
    .qb-search {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--qb-gray-200);
        border-radius: 6px;
        font-size: 14px;
        margin-bottom: 16px;
        font-family: inherit;
    }
    .qb-search:focus {
        outline: none;
        border-color: var(--qb-green);
        box-shadow: 0 0 0 2px rgba(0,128,96,.15);
    }

    /* ── Table ── */
    .qb-table {
        width: 100%;
        border-collapse: collapse;
    }
    .qb-table tr {
        content-visibility: auto;
        contain-intrinsic-size: auto 48px;
    }
    .qb-table th {
        text-align: left;
        padding: 10px 12px;
        font-size: 12px;
        font-weight: 600;
        color: var(--qb-gray-600);
        border-bottom: 1px solid var(--qb-gray-100);
        text-transform: uppercase;
        letter-spacing: .5px;
    }
    .qb-table td {
        padding: 10px 12px;
        font-size: 14px;
        border-bottom: 1px solid var(--qb-gray-50);
    }
    .qb-table tr:hover {
        background: var(--qb-gray-50);
    }
    .qb-empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--qb-gray-400);
    }

    /* ── Quantity Input ── */
    .qb-qty {
        width: 70px;
        padding: 6px 8px;
        border: 1px solid var(--qb-gray-200);
        border-radius: 4px;
        text-align: center;
        font-size: 14px;
        font-family: inherit;
    }
    .qb-qty:focus {
        outline: none;
        border-color: var(--qb-green);
    }

    /* ── Buttons ── */
    .qb-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        transition: all .15s;
        font-family: inherit;
    }
    .qb-btn-primary {
        background: var(--qb-green);
        color: #fff;
    }
    .qb-btn-primary:hover {
        background: #006e52;
    }
    .qb-btn-primary:disabled {
        opacity: .5;
        cursor: not-allowed;
    }

    /* ── Stock Badges ── */
    .qb-stock-low  { color: #b98900; font-weight: 500; }
    .qb-stock-out  { color: #d82c0d; }
    .qb-stock-in   { color: #008060; }

    /* ── Cart Count Badge ── */
    .qb-cart-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--qb-green);
        color: #fff;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 700;
        width: 22px;
        height: 22px;
        margin-left: 6px;
    }

    /* ── CSV Upload Zone ── */
    .qb-csv-zone {
        border: 2px dashed var(--qb-gray-200);
        border-radius: var(--qb-radius);
        padding: 24px;
        text-align: center;
        color: var(--qb-gray-400);
        cursor: pointer;
        transition: all .15s;
        margin-bottom: 16px;
    }
    .qb-csv-zone:hover {
        border-color: var(--qb-green);
        color: var(--qb-green);
    }

    /* ── Footer ── */
    .qb-footer {
        text-align: center;
        padding: 20px;
    }
    .qb-footer p {
        font-size: 13px;
        color: var(--qb-gray-400);
        margin: 0;
    }
</style>
