{{-- QuickB2B Settings Page --}}
@extends('shopify-app::layouts.default')

@section('content')
    @php $host = request('host'); @endphp

    <ui-title-bar title="QuickB2B > Settings"></ui-title-bar>
    @include('components.nav-menu')

    <s-page heading="Quick Order Page Settings">
        <s-stack gap="large-200">

            {{-- Flash Messages --}}
            @if (session('success'))
                <s-banner tone="success" heading="Saved" dismissible>{{ session('success') }}</s-banner>
            @endif
            @if (session('error'))
                <s-banner tone="critical" heading="Error" dismissible>{{ session('error') }}</s-banner>
            @endif

            <s-box padding="large-200" background="base" border="base" borderRadius="large-100">
                <form method="POST" action="{{ route('settings.save') }}">
                    @csrf
                    @sessionToken
                    <input type="hidden" name="host" value="{{ $host }}">

                    <s-stack gap="large-200">

                        {{-- ── Quantity Limits ── --}}
                        <s-section heading="📊 Quantity Limits">
                            <s-paragraph tone="subdued" style="margin-bottom:12px;">
                                Set global minimum & maximum quantity for all products. Leave empty for no limit.
                            </s-paragraph>
                            <s-stack direction="inline" gap="large-100">
                                <s-text-field name="min_qty" label="Minimum Qty" type="number" min="1"
                                    value="{{ $settings['min_qty'] ?? '' }}" placeholder="e.g. 5"
                                    help-text="Customer must order at least this many per variant.">
                                </s-text-field>
                                <s-text-field name="max_qty" label="Maximum Qty" type="number" min="1"
                                    value="{{ $settings['max_qty'] ?? '' }}" placeholder="e.g. 100"
                                    help-text="Customer cannot order more than this per variant.">
                                </s-text-field>
                            </s-stack>
                        </s-section>

                        {{-- ── Visibility Toggles ── --}}
                        <s-section heading="👁️ Visibility">
                            <s-paragraph tone="subdued" style="margin-bottom:12px;">
                                Choose which columns &amp; controls to show on the Quick Order page.
                            </s-paragraph>
                            <s-stack gap="base">
                                <s-checkbox name="hide_oos" value="1" label="Hide out-of-stock products"
                                    {{ ($settings['hide_oos'] ?? true) ? 'checked' : '' }}
                                    help-text="Products with zero inventory will be hidden from the table.">
                                </s-checkbox>
                                <s-checkbox name="hide_sku" value="1" label="Hide SKU column"
                                    {{ ($settings['hide_sku'] ?? false) ? 'checked' : '' }}
                                    help-text="Hides the SKU column from the product table.">
                                </s-checkbox>
                                <s-checkbox name="hide_stock" value="1" label="Hide Stock column"
                                    {{ ($settings['hide_stock'] ?? false) ? 'checked' : '' }}
                                    help-text="Hides the inventory count column from the product table.">
                                </s-checkbox>
                            </s-stack>
                        </s-section>

                        {{-- ── Product Images ── --}}
                        <s-section heading="🖼️ Product Images">
                            <s-paragraph tone="subdued" style="margin-bottom:12px;">
                                Show product thumbnails (100×100) in the table.
                                <strong>⚠️ Enabling this may slow down the page</strong> for stores with many products.
                            </s-paragraph>
                            <s-checkbox name="show_images" value="1" label="Show product images"
                                {{ ($settings['show_images'] ?? false) ? 'checked' : '' }}
                                help-text="Display 100×100 product thumbnails in the Quick Order table.">
                            </s-checkbox>
                        </s-section>

                        {{-- ── Tag Filtering ── --}}
                        <s-section heading="🏷️ Product Tags">
                            <s-paragraph tone="subdued" style="margin-bottom:12px;">
                                Products with these tags will be <strong>hidden</strong> from the Quick Order page.
                                Separate multiple tags with commas.
                            </s-paragraph>
                            <s-text-field name="hide_tags" label="Hide Products Tagged"
                                value="{{ implode(', ', $settings['hide_tags'] ?? []) }}"
                                placeholder="e.g. clearance, discontinued, wholesale-only"
                                help-text="Comma-separated list of tags to exclude.">
                            </s-text-field>
                        </s-section>

                        {{-- ── Submit ── --}}
                        <s-stack direction="inline" distribution="trailing">
                            <s-button type="submit" variant="primary">💾 Save Settings</s-button>
                        </s-stack>

                    </s-stack>
                </form>
            </s-box>

            {{-- ── Catalog Management ── --}}
            <s-section heading="📦 Product Catalog">
                <s-paragraph tone="subdued" style="margin-bottom:12px;">
                    Refresh the product catalog to sync latest prices, inventory, and images from your store.
                    <br><strong>⚠️ Required after enabling images</strong> or making major product changes.
                </s-paragraph>
                <s-button id="btn-refresh-catalog" variant="secondary" onclick="refreshCatalog()">
                    🔄 Refresh Product Catalog
                </s-button>
                <s-banner id="catalog-status" tone="info" style="margin-top:12px;display:none;"></s-banner>
            </s-section>

            <script>
                async function refreshCatalog() {
                    var btn = document.getElementById('btn-refresh-catalog');
                    var banner = document.getElementById('catalog-status');
                    btn.setAttribute('loading', '');
                    btn.disabled = true;
                    banner.style.display = 'block';
                    banner.textContent = '⏳ Starting catalog refresh...';

                    try {
                        var resp = await fetch('/apps/quick-order/api/products/refresh', { method: 'POST' });
                        var data = await resp.json();
                        if (data.status === 'already_running') {
                            banner.setAttribute('tone', 'warning');
                            banner.textContent = '⚠️ ' + data.message;
                        } else if (data.status === 'started') {
                            banner.setAttribute('tone', 'success');
                            banner.textContent = '✅ Catalog refresh started! It may take a few minutes.';
                        }
                    } catch (e) {
                        banner.setAttribute('tone', 'critical');
                        banner.textContent = '❌ Failed to start refresh. Please try again.';
                    }
                    btn.removeAttribute('loading');
                    btn.disabled = false;
                }
            </script>

        </s-stack>
    </s-page>
@endsection
