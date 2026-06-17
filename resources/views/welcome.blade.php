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

    <s-page heading="Dashboard" style="display:flex;flex-direction:column;gap:24px;">

        <s-banner status="info">
            🏪 <strong>{{ $shopDomain ?? Auth::user()->name }}</strong> — Welcome to QuickB2B
        </s-banner>

        @if (session('success'))
            <s-banner status="success">{{ session('success') }}</s-banner>
        @endif
        @if (session('error'))
            <s-banner status="critical">{{ session('error') }}</s-banner>
        @endif

        {{-- ───── Page Status Card ───── --}}
        <s-section heading="📄 Quick Order Page">
            @if ($page)
                {{-- Status: LIVE --}}
                <s-box padding="large-200" style="display:flex;flex-direction:column;gap:16px;">
                    <s-stack gap="base" distribution="space-between" alignment="center">
                        <s-stack gap="small" alignment="center">
                            <s-badge tone="success">Live</s-badge>
                            <s-text fontWeight="bold">{{ $page->title }}</s-text>
                        </s-stack>
                        <s-text tone="subdued" variant="bodySm">
                            {{ $page->created_at->diffForHumans() }}
                        </s-text>
                    </s-stack>

                    <s-stack gap="base" style="flex-wrap:wrap;">
                        <s-text variant="bodySm">
                            🔗 <a href="https://{{ $page->page_url }}" target="_blank" rel="noopener">
                                {{ $page->page_url }}
                            </a>
                        </s-text>
                        <s-badge tone="{{ $page->is_published ? 'success' : 'critical' }}">
                            {{ $page->is_published ? 'Published' : 'Hidden' }}
                        </s-badge>
                        <s-badge tone="{{ $page->menu_linked ? 'success' : 'warning' }}">
                            {{ $page->menuStatusLabel() }}
                        </s-badge>
                    </s-stack>

                    <s-stack gap="base" direction="inline" style="flex-wrap:wrap;">
                        {{-- Refresh --}}
                        <form method="POST" action="{{ route('page.sync') }}" style="display:inline;">
                            @csrf
                            @sessionToken
                            <input type="hidden" name="host" value="{{ $host }}">
                            <s-button type="submit" variant="secondary">🔄 Refresh</s-button>
                        </form>

                        {{-- Edit Title --}}
                        <s-button variant="secondary" onclick="shopify.modal.show('edit-title-modal')">
                            ✏️ Edit Title
                        </s-button>

                        {{-- Link to Menu (only if not linked) --}}
                        @if (!$page->menu_linked)
                            <form method="POST" action="{{ route('page.link-menu') }}" style="display:inline;">
                                @csrf
                                @sessionToken
                                <input type="hidden" name="host" value="{{ $host }}">
                                <s-button type="submit" variant="secondary">
                                    🔗 Link to Menu
                                </s-button>
                            </form>
                        @endif

                        {{-- Delete --}}
                        <s-button variant="critical" onclick="shopify.modal.show('delete-page-modal')">
                            🗑️ Delete Page
                        </s-button>
                    </s-stack>
                </s-box>
            @else
                {{-- Status: No page yet — show create button --}}
                <s-banner tone="info" style="margin-bottom:16px;">
                    No Quick Order page yet. Create one or refresh to find an existing one.
                </s-banner>
                <s-stack direction="inline" gap="base">
                    <form method="POST" action="{{ route('setup.create-page') }}">
                        @csrf
                        @sessionToken
                        <input type="hidden" name="host" value="{{ $host }}">
                        <s-button type="submit" variant="primary">📄 Create Quick Order Page + Add to Menu</s-button>
                    </form>
                    <form method="POST" action="{{ route('page.sync') }}">
                        @csrf
                        @sessionToken
                        <input type="hidden" name="host" value="{{ $host }}">
                        <s-button type="submit" variant="secondary">🔄 Find Existing Page</s-button>
                    </form>
                </s-stack>
            @endif
        </s-section>

        <s-section heading="Getting Started">
            <s-paragraph tone="subdued">
                Your Shopify app is ready. Start building your features here.
            </s-paragraph>
        </s-section>

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

