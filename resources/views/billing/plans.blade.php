@extends('shopify-app::layouts.default')

@section('content')

    @php
        $host = request('host');
        $shopDomain = Auth::user()->getDomain()->toNative();
        $storeHandle = explode('.', $shopDomain)[0];
        $homeUrl = URL::tokenRoute('home', compact('host'));
        $appHandle = config('shopify-app.app_handle', 'quick-order-page-1');
        $planPageUrl = "https://admin.shopify.com/store/{$storeHandle}/charges/{$appHandle}/pricing_plans";
        $billingUrl = "https://admin.shopify.com/store/{$storeHandle}/settings/billing";
        $hasActivePlan = (bool) Auth::user()->plan_id;
        $quickPage = \App\Models\QuickOrderPage::where('user_id', Auth::id())->first();
    @endphp

    <ui-title-bar title="QuickB2B > Plan">
        <button onclick="location.href='{{ $homeUrl }}'">← Dashboard</button>
    </ui-title-bar>

    @include('components.nav-menu')

    <s-page heading="Plan &amp; Billing">

        <s-stack gap="large-200">

            {{-- Flash Messages --}}
            @if(session('success'))
                <s-banner tone="success" dismissible>{{ session('success') }}</s-banner>
            @endif
            @if(session('error'))
                <s-banner tone="critical" dismissible>{{ session('error') }}</s-banner>
            @endif

            {{-- ── Plan Card ── --}}
            <s-section heading="{{ $hasActivePlan ? 'Your Plan' : 'Get Started' }}">
                <s-box padding="large-300" background="base" border="base" borderRadius="large-100">
                    <s-stack direction="inline" gap="large-200" wrap>

                        {{-- Left: Price --}}
                        <s-stack gap="base" style="min-width:180px;">
                            <s-text variant="headingSm" fontWeight="bold">🚀 Pro Plan</s-text>
                            <s-paragraph tone="subdued">Everything you need for B2B wholesale.</s-paragraph>
                            <s-text as="p" variant="heading2xl" fontWeight="bold">$9.99
                                <s-text as="span" variant="bodySm" tone="subdued" fontWeight="regular">/month</s-text>
                            </s-text>
                            <s-badge tone="info" size="large">7-day free trial</s-badge>
                            <s-paragraph tone="subdued" variant="bodySm">Cancel anytime. No lock-in.</s-paragraph>

                            @if ($hasActivePlan)
                                <s-badge tone="success" size="large">✅ Active</s-badge>
                                <s-button variant="secondary" onclick="window.top.location.href='{{ $billingUrl }}'">
                                    Manage Subscription
                                </s-button>
                                </s-button>
                            @else
                                <s-button variant="primary" size="large" onclick="window.top.location.href='{{ $planPageUrl }}'">
                                    Start 7-Day Free Trial →
                                </s-button>
                            @endif
                        </s-stack>

                        {{-- Right: Features --}}
                        <s-stack gap="small-200" style="min-width:250px;">
                            <s-text variant="bodyMd" fontWeight="bold">All Features Included:</s-text>
                            <s-text variant="bodySm">📊 Smart Bulk Order Table</s-text>
                            <s-text variant="bodySm">📤 CSV Upload from Excel</s-text>
                            <s-text variant="bodySm">📧 Draft Orders + Email Invoices</s-text>
                            <s-text variant="bodySm">🖼️ Product Images (100×100)</s-text>
                            <s-text variant="bodySm">🔢 Min/Max Quantity Control</s-text>
                            <s-text variant="bodySm">📊 Smart Stock Management</s-text>
                            <s-text variant="bodySm">💱 Auto Currency (35+ supported)</s-text>
                            <s-text variant="bodySm">⚙️ Full Settings Control</s-text>
                            <s-text variant="bodySm">❓ Priority Support</s-text>
                        </s-stack>

                    </s-stack>
                </s-box>
                <s-banner tone="info" style="margin-top:12px;">
                    💡 You'll be redirected to Shopify to approve the subscription. 7-day free trial starts immediately.
                </s-banner>
            </s-section>

            {{-- ───── Cleanup Before Uninstalling ───── --}}
            @if ($quickPage)
                <s-section heading="⚠️ Before You Go">
                    <s-banner tone="warning" icon="circle-alert" style="margin-bottom:16px;">
                        <strong>Uninstalling the app will NOT remove the Quick Order page</strong> from your store.
                        Delete it here first to clean up the page and navigation menu link.
                    </s-banner>
                    <s-box padding="large-200" background="base" border="base" borderRadius="large-100">
                        <s-stack gap="base">
                            <s-text variant="bodyMd">
                                <strong>{{ $quickPage->title }}</strong> —
                                <a href="{{ $quickPage->page_url }}" target="_blank" rel="noopener" style="color:var(--p-color-text-interactive);">
                                    {{ $quickPage->page_url }}
                                </a>
                            </s-text>
                            @if ($quickPage->menu_linked)
                                <s-badge tone="caution">Linked in navigation</s-badge>
                            @endif
                            <s-button variant="primary" tone="critical" id="btn-delete-page-plans" onclick="deletePageFromPlans()">
                                🗑️ Delete Page & Remove from Menu
                            </s-button>
                            <s-banner id="plans-delete-status" style="display:none;"></s-banner>
                        </s-stack>
                    </s-box>
                </s-section>

                <form id="delete-page-form-plans" method="POST" action="{{ route('page.delete') }}" style="display:none;">
                    @csrf
                    @sessionToken
                    <input type="hidden" name="host" value="{{ $host }}">
                </form>

                <script>
                    async function deletePageFromPlans() {
                        var btn = document.getElementById('btn-delete-page-plans');
                        var banner = document.getElementById('plans-delete-status');
                        if (!confirm('Permanently delete the "{{ $quickPage->title }}" page from your store and navigation menu?')) return;

                        btn.setAttribute('loading', '');
                        btn.disabled = true;
                        banner.style.display = 'block';
                        banner.setAttribute('tone', 'info');
                        banner.textContent = '⏳ Deleting page...';

                        try {
                            var f = document.getElementById('delete-page-form-plans');
                            var data = new URLSearchParams(new FormData(f));
                            var resp = await fetch(f.getAttribute('action'), {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: data.toString(),
                                redirect: 'follow'
                            });
                            if (resp.ok || resp.redirected) {
                                banner.setAttribute('tone', 'success');
                                banner.textContent = '✅ Page deleted! You can now safely uninstall.';
                                btn.style.display = 'none';
                            } else {
                                throw new Error('Delete failed');
                            }
                        } catch (e) {
                            banner.setAttribute('tone', 'critical');
                            banner.textContent = '❌ Could not delete page. Please try again or delete manually from Shopify Admin.';
                            btn.removeAttribute('loading');
                            btn.disabled = false;
                        }
                    }
                </script>
            @endif

        </s-stack>

    </s-page>
@endsection
