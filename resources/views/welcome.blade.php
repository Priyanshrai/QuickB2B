@extends('shopify-app::layouts.default')

@php
    $host = request('host');
    $page = \App\Models\QuickOrderPage::where('user_id', Auth::id())->first();
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
                        {{-- Edit Title --}}
                        <s-button variant="secondary" onclick="shopify.modal.show('edit-title-modal')">
                            ✏️ Edit Title
                        </s-button>

                        {{-- Link to Menu (only if not linked) --}}
                        @if (!$page->menu_linked)
                            <form method="POST" action="{{ URL::tokenRoute('page.link-menu', compact('host')) }}" style="display:inline;">
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
                    No Quick Order page yet. Create one with one click!
                </s-banner>
                <form method="POST" action="{{ URL::tokenRoute('setup.create-page', compact('host')) }}">
                    @csrf
                    @sessionToken
                    <input type="hidden" name="host" value="{{ $host }}">
                    <s-button type="submit" variant="primary">
                        📄 Create Quick Order Page + Add to Menu
                    </s-button>
                </form>
            @endif
        </s-section>

        <s-section heading="Getting Started">
            <s-paragraph tone="subdued">
                Your Shopify app is ready. Start building your features here.
            </s-paragraph>
        </s-section>

    </s-page>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- Edit Title Modal --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <ui-modal id="edit-title-modal">
        <div style="padding:20px 24px;display:flex;flex-direction:column;gap:16px;">
            <s-text tone="subdued">Change the display name of your Quick Order page.</s-text>
            <form id="edit-title-form" method="POST" action="{{ URL::tokenRoute('page.update-title', compact('host')) }}">
                @csrf
                @sessionToken
                <input type="hidden" name="host" value="{{ $host }}">
                <s-text-field label="Page Title" name="title" value="{{ $page->title ?? 'Quick Order' }}" required>
                </s-text-field>
            </form>
        </div>
        <ui-title-bar title="Edit Page Title">
            <button onclick="shopify.modal.hide('edit-title-modal')">Cancel</button>
            <button variant="primary" onclick="submitModalForm('edit-title-form')">Save Changes</button>
        </ui-title-bar>
    </ui-modal>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- Delete Confirmation Modal --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <ui-modal id="delete-page-modal">
        <div style="padding:20px 24px;display:flex;flex-direction:column;gap:14px;">
            <s-text tone="subdued">
                This will permanently delete the <strong>{{ $page->title ?? 'Quick Order' }}</strong> page
                from your store and remove it from the navigation menu.
            </s-text>
            <s-banner tone="warning">This action cannot be undone. The page and its menu link will be removed.</s-banner>
            <s-text tone="subdued">Are you sure you want to continue?</s-text>
        </div>
        <ui-title-bar title="Delete Quick Order Page?">
            <button onclick="shopify.modal.hide('delete-page-modal')">Cancel</button>
            <button variant="critical" onclick="submitModalForm('delete-page-form')">🗑️ Delete</button>
        </ui-title-bar>
    </ui-modal>
    <form id="delete-page-form" method="POST" action="{{ URL::tokenRoute('page.delete', compact('host')) }}" style="display:none;">
        @csrf
        @sessionToken
        <input type="hidden" name="host" value="{{ $host }}">
    </form>

<script>
    // Submit a form from inside a modal
    function submitModalForm(formId) {
        var form = document.getElementById(formId);
        if (!form) return;

        // Collect Polaris web component values
        form.querySelectorAll('s-text-field, s-select, s-number-field, s-checkbox').forEach(function(el) {
            var name = el.getAttribute('name');
            if (!name) return;
            var val = el.tagName.toLowerCase() === 's-checkbox'
                ? (el.checked ? (el.getAttribute('value') || '1') : '0')
                : (el.value || '');
            var hidden = form.querySelector('input[type=hidden][name="' + name + '"]');
            if (hidden) {
                hidden.value = val;
            } else {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = name;
                hidden.value = val;
                form.appendChild(hidden);
            }
        });

        shopify.modal.hide(document.querySelector('ui-modal[open]')?.id || '').then(() => {
            form.submit();
        });
    }
</script>

@endsection

