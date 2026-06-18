@extends('shopify-app::layouts.default')

@php
    $host = request('host');
    $page = \App\Models\QuickOrderPage::where('user_id', Auth::id())->first();

    // Auto-sync from Shopify if DB empty (reinstall case)
    if (!$page) {
        try {
            $shopifyPage = \App\Services\ShopifyGraphQL::fetchQuickOrderPage(Auth::user());
            if ($shopifyPage) {
                $page = \App\Models\QuickOrderPage::create([
                    'user_id'        => Auth::id(),
                    'shopify_page_id'=> $shopifyPage['id'],
                    'title'          => $shopifyPage['title'],
                    'handle'         => $shopifyPage['handle'],
                    'is_published'   => $shopifyPage['isPublished'] ?? true,
                    'menu_linked'    => false,
                    'page_url'       => 'https://' . Auth::user()->getDomain()->toNative() . '/pages/' . $shopifyPage['handle'],
                ]);
            }
        } catch (\Throwable $e) {}
    }
@endphp

@section('content')
    <ui-title-bar title="QuickB2B"></ui-title-bar>
    @include('components.nav-menu')

    <s-page heading="Dashboard">
        <s-stack gap="large-200">

            {{-- Flash Messages --}}
            @if (session('success'))
                <s-banner tone="success" heading="Success" dismissible>{{ session('success') }}</s-banner>
            @endif
            @if (session('error'))
                <s-banner tone="critical" heading="Error" dismissible>{{ session('error') }}</s-banner>
            @endif

            {{-- ───── Quick Order Page Card ───── --}}
            <s-section heading="Quick Order Page">
                @if ($page)
                    <s-box padding="large-200" background="base" border="base" borderRadius="large-100">
                        <s-stack gap="large-100">
                            <s-stack direction="inline" gap="base" justifyContent="space-between" alignItems="center">
                                <s-stack direction="inline" gap="small" alignItems="center">
                                    <s-badge tone="success" size="large">Live</s-badge>
                                    <span id="qb-title-display" style="font-weight:700;font-size:16px;cursor:pointer;border-bottom:1px dashed #8d9298;padding:2px 4px;border-radius:3px;" onclick="startEditTitle()" title="Click to rename">
                                        {{ $page->title }} <span style="font-size:12px;color:#8d9298;">✏️</span>
                                    </span>
                                    <span id="qb-title-edit" style="display:none;">
                                        <input type="text" id="qb-title-input" value="{{ $page->title }}" style="padding:6px 10px;border:1px solid #c9cccf;border-radius:4px;font-size:14px;font-family:inherit;width:200px;">
                                        <button onclick="saveInlineTitle()" style="margin-left:6px;padding:6px 14px;background:#008060;color:#fff;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Save</button>
                                        <button onclick="cancelEditTitle()" style="padding:6px 14px;background:#e4e5e7;color:#4a4f55;border:none;border-radius:4px;cursor:pointer;font-size:12px;">Cancel</button>
                                    </span>
                                </s-stack>
                                <s-text tone="subdued" variant="bodySm">Created {{ $page->created_at->diffForHumans() }}</s-text>
                            </s-stack>

                            <s-stack direction="inline" gap="base" style="flex-wrap:wrap;">
                                <s-text variant="bodySm">
                                    <a href="{{ $page->page_url }}" target="_blank" rel="noopener" style="color:var(--p-color-text-interactive);text-decoration:none;">🔗 {{ $page->page_url }}</a>
                                </s-text>
                                <s-badge tone="info">Published</s-badge>
                                @if ($page->menu_linked)
                                    <s-badge tone="success">Linked in navigation</s-badge>
                                @else
                                    <s-badge tone="caution">Not in menu</s-badge>
                                @endif
                            </s-stack>

                            <s-stack direction="inline" gap="base" style="flex-wrap:wrap;">
                                <form method="POST" action="{{ route('page.sync') }}" style="display:inline;">
                                    @csrf @sessionToken
                                    <input type="hidden" name="host" value="{{ $host }}">
                                    <s-button type="submit" variant="secondary">🔄 Refresh</s-button>
                                </form>
                                @if (!$page->menu_linked)
                                    <form method="POST" action="{{ route('page.link-menu') }}" style="display:inline;">
                                        @csrf @sessionToken
                                        <input type="hidden" name="host" value="{{ $host }}">
                                        <s-button type="submit" variant="secondary">🔗 Link to Menu</s-button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('page.unlink-menu') }}" style="display:inline;">
                                        @csrf @sessionToken
                                        <input type="hidden" name="host" value="{{ $host }}">
                                        <s-button type="submit" variant="secondary" tone="critical">🔓 Unlink from Menu</s-button>
                                    </form>
                                @endif
                                <s-button variant="primary" tone="critical" onclick="shopify.modal.show('delete-page-modal')">🗑️ Delete Page</s-button>
                            </s-stack>
                        </s-stack>
                    </s-box>

                    {{-- ⚠️ Uninstall Warning --}}
                    <s-banner tone="warning" icon="circle-alert" style="margin-top:12px;">
                        <strong>Before uninstalling this app:</strong> Use the <strong>🗑️ Delete Page</strong> button above to remove the Quick Order page and navigation menu link from your store. Uninstalling the app will NOT automatically remove them.
                    </s-banner>
                @else
                    <s-banner tone="info" heading="No Page Yet">
                        Create a Quick Order page to let wholesale customers place bulk orders instantly.
                    </s-banner>
                    <s-stack direction="inline" gap="base" style="margin-top:16px;">
                        <form method="POST" action="{{ route('setup.create-page') }}">
                            @csrf @sessionToken
                            <input type="hidden" name="host" value="{{ $host }}">
                            <s-button type="submit" variant="primary">📄 Create Page + Add to Menu</s-button>
                        </form>
                        <form method="POST" action="{{ route('page.sync') }}">
                            @csrf @sessionToken
                            <input type="hidden" name="host" value="{{ $host }}">
                            <s-button type="submit" variant="secondary">🔄 Find Existing Page</s-button>
                        </form>
                    </s-stack>
                @endif
            </s-section>

            {{-- ───── Feature Overview ───── --}}
            <s-section heading="What QuickB2B Does">
                <s-paragraph tone="subdued" style="margin-bottom:16px;">
                    A <strong>spreadsheet-like ordering experience</strong> for wholesale &amp; B2B customers. Type quantities, upload CSV, reorder from history — all in one place.
                </s-paragraph>

                <s-stack gap="none">
                    <s-box padding="large-100" borderColor="base" borderWidth="none none small none">
                        <s-stack direction="inline" gap="large-100" alignItems="start">
                            <s-badge tone="info" size="large" icon="chart-bar">Bulk Order Table</s-badge>
                            <s-paragraph tone="subdued">Searchable product list with quantity inputs. One "Add All to Cart" button instead of clicking hundreds of times.</s-paragraph>
                        </s-stack>
                    </s-box>
                    <s-box padding="large-100" borderColor="base" borderWidth="none none small none">
                        <s-stack direction="inline" gap="large-100" alignItems="start">
                            <s-badge tone="info" size="large" icon="upload">CSV Upload</s-badge>
                            <s-paragraph tone="subdued">Drag and drop an Excel file. Products are matched automatically and added to cart — no manual entry.</s-paragraph>
                        </s-stack>
                    </s-box>
                    <s-box padding="large-100" borderColor="base" borderWidth="none none small none">
                        <s-stack direction="inline" gap="large-100" alignItems="start">
                            <s-badge tone="info" size="large" icon="refresh">Reorder from History</s-badge>
                            <s-paragraph tone="subdued">One-click reorder from past purchases. Last week's order back in cart instantly.</s-paragraph>
                        </s-stack>
                    </s-box>
                    <s-box padding="large-100" borderColor="base" borderWidth="none none small none">
                        <s-stack direction="inline" gap="large-100" alignItems="start">
                            <s-badge tone="info" size="large" icon="dollar-sign">Customer Pricing</s-badge>
                            <s-paragraph tone="subdued">Different prices for different customers. VIP wholesale sees $5, retail sees $10 — automatically applied.</s-paragraph>
                        </s-stack>
                    </s-box>
                    <s-box padding="large-100">
                        <s-stack direction="inline" gap="large-100" alignItems="start">
                            <s-badge tone="info" size="large" icon="package">Stock Visibility</s-badge>
                            <s-paragraph tone="subdued">Inventory levels shown inline. Customers see "Only 5 left!" before they order — no surprises.</s-paragraph>
                        </s-stack>
                    </s-box>
                </s-stack>
            </s-section>

        </s-stack>
    </s-page>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- Modals (only rendered when page exists) --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @if ($page)

        {{-- Delete Confirmation Modal --}}
        <ui-modal id="delete-page-modal">
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px;">
                <s-text tone="subdued">
                    This will permanently delete the <strong>{{ $page->title }}</strong> page
                    from your store and remove it from the navigation menu.
                </s-text>
                <s-banner tone="warning">This action cannot be undone. The page and its menu link will be removed.</s-banner>
                <s-stack direction="inline" distribution="trailing" gap="base">
                    <s-button variant="secondary" id="delete-cancel">Cancel</s-button>
                    <s-button variant="primary" tone="critical" id="delete-confirm">🗑️ Delete</s-button>
                </s-stack>
                <script>
                    document.getElementById('delete-confirm').addEventListener('click', function() {
                        this.setAttribute('loading', '');
                        var f = document.getElementById('delete-page-form');
                        var b = new URLSearchParams();
                        f.querySelectorAll('input[type=hidden]').forEach(function(e) { if (e.name && e.value) b.append(e.name, e.value); });
                        fetch(f.getAttribute('action'), { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:b.toString(), redirect:'follow' })
                            .then(function(r) { r.ok ? location.href = r.url : location.reload(); }).catch(function() { location.reload(); });
                    });
                    document.getElementById('delete-cancel').addEventListener('click', function() { shopify.modal.hide('delete-page-modal'); });
                </script>
            </div>
            <ui-title-bar title="Delete Quick Order Page?"></ui-title-bar>
        </ui-modal>



        {{-- Delete form (hidden, outside modal) --}}
        <form id="delete-page-form" method="POST" action="{{ route('page.delete') }}" style="display:none;">
            @csrf
            @sessionToken
            <input type="hidden" name="host" value="{{ $host }}">
        </form>

    @endif

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- Shared JS --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<script>
    // ── Inline Edit Title ──────────────────────────────
    function startEditTitle() {
        document.getElementById('qb-title-display').style.display = 'none';
        document.getElementById('qb-title-edit').style.display = 'inline';
        document.getElementById('qb-title-input').focus();
    }
    function cancelEditTitle() {
        document.getElementById('qb-title-edit').style.display = 'none';
        document.getElementById('qb-title-display').style.display = 'inline';
    }
    function saveInlineTitle() {
        var input = document.getElementById('qb-title-input');
        var newTitle = input.value.trim();
        if (!newTitle) return;
        var form = document.getElementById('edit-title-form');
        document.getElementById('edit-title-value').value = newTitle;
        var data = new URLSearchParams(new FormData(form));
        fetch(form.getAttribute('action'), { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:data.toString(), redirect:'follow' })
            .then(function(r) { r.ok ? location.href = r.url : location.reload(); })
            .catch(function() { location.reload(); });
    }

    // Loading state on all form submits
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form:not(#delete-page-form):not(#edit-title-form)').forEach(function (f) {
            f.addEventListener('submit', function () {
                f.querySelectorAll('s-button[type="submit"]').forEach(function (b) {
                    b.setAttribute('loading', '');
                });
            });
        });
    });
</script>

{{-- Hidden form for inline title edit (outside modal, always in DOM) --}}
<form id="edit-title-form" method="POST" action="{{ route('page.update-title') }}" style="display:none;">
    @csrf
    @sessionToken
    <input type="hidden" name="host" value="{{ $host }}">
    <input type="hidden" name="title" id="edit-title-value">
</form>

@endsection

