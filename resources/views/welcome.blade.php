@extends('shopify-app::layouts.default')

@php
    $host = request('host');
    $page = \App\Models\QuickOrderPage::where('user_id', Auth::id())->first();

    // Auto-sync from Shopify if DB empty (reinstall case)
    if (!$page) {
        try {
            $shopifyPage = \App\Services\ShopifyGraphQL::fetchPageByHandle(Auth::user(), 'quick-order');
            if ($shopifyPage) {
                $page = \App\Models\QuickOrderPage::create([
                    'user_id'        => Auth::id(),
                    'shopify_page_id'=> $shopifyPage['id'],
                    'title'          => $shopifyPage['title'],
                    'handle'         => $shopifyPage['handle'],
                    'is_published'   => $shopifyPage['isPublished'] ?? true,
                    'menu_linked'    => false,
                    'page_url'       => Auth::user()->getDomain()->toNative() . '/pages/' . $shopifyPage['handle'],
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
                                    <s-text fontWeight="bold">{{ $page->title }}</s-text>
                                </s-stack>
                                <s-text tone="subdued" variant="bodySm">Created {{ $page->created_at->diffForHumans() }}</s-text>
                            </s-stack>

                            <s-stack direction="inline" gap="base" style="flex-wrap:wrap;">
                                <s-text variant="bodySm">
                                    <a href="https://{{ $page->page_url }}" target="_blank" rel="noopener" style="color:var(--p-color-text-interactive);text-decoration:none;">🔗 {{ $page->page_url }}</a>
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
                                <s-button variant="secondary" onclick="shopify.modal.show('edit-title-modal')">✏️ Edit Title</s-button>
                                @if (!$page->menu_linked)
                                    <form method="POST" action="{{ route('page.link-menu') }}" style="display:inline;">
                                        @csrf @sessionToken
                                        <input type="hidden" name="host" value="{{ $host }}">
                                        <s-button type="submit" variant="secondary">🔗 Link to Menu</s-button>
                                    </form>
                                @endif
                                <s-button variant="primary" tone="critical" onclick="shopify.modal.show('delete-page-modal')">🗑️ Delete Page</s-button>
                            </s-stack>
                        </s-stack>
                    </s-box>
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

        {{-- Edit Title Modal --}}
        <ui-modal id="edit-title-modal">
            <div style="padding:20px 24px;display:flex;flex-direction:column;gap:16px;">
                <s-text tone="subdued">Change the display name of your Quick Order page.</s-text>
                <form id="edit-title-form" method="POST" action="{{ route('page.update-title') }}" onsubmit="return false;">
                    @csrf
                    @sessionToken
                    <input type="hidden" name="host" value="{{ $host }}">
                    <s-text-field label="Page Title" name="title" value="{{ $page->title }}" required></s-text-field>
                </form>
                <s-stack direction="inline" distribution="trailing" gap="base">
                    <s-button variant="secondary" id="edit-title-cancel">Cancel</s-button>
                    <s-button variant="primary" id="edit-title-save">Save Changes</s-button>
                </s-stack>
                <script>
                    document.getElementById('edit-title-save').addEventListener('click', function() {
                        this.setAttribute('loading', '');
                        var f = document.getElementById('edit-title-form');
                        var b = new URLSearchParams();
                        f.querySelectorAll('input[type=hidden]').forEach(function(e) { if (e.name && e.value) b.append(e.name, e.value); });
                        f.querySelectorAll('s-text-field').forEach(function(e) { if (e.getAttribute('name')) b.set(e.getAttribute('name'), e.value || ''); });
                        fetch(f.getAttribute('action'), { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:b.toString(), redirect:'follow' })
                            .then(function(r) { r.ok ? location.href = r.url : location.reload(); }).catch(function() { location.reload(); });
                    });
                    document.getElementById('edit-title-cancel').addEventListener('click', function() { shopify.modal.hide('edit-title-modal'); });
                </script>
            </div>
            <ui-title-bar title="Edit Page Title"></ui-title-bar>
        </ui-modal>

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
    // Loading state on all form submits
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form:not(#delete-page-form)').forEach(function (f) {
            f.addEventListener('submit', function () {
                f.querySelectorAll('s-button[type="submit"]').forEach(function (b) {
                    b.setAttribute('loading', '');
                });
            });
        });
    });
</script>

@endsection

