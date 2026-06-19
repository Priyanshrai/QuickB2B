{{-- QuickB2B Help & FAQ Page --}}
@extends('shopify-app::layouts.default')

@php $host = request('host'); @endphp

@section('content')
    <ui-title-bar title="QuickB2B > Help"></ui-title-bar>
    @include('components.nav-menu')

    <s-page heading="Help &amp; FAQ">
        <s-stack gap="large-200">

            {{-- ── How to Use ── --}}
            <s-section heading="📖 Quick Start">
                <s-stack gap="none">
                    <s-box padding="large-100" borderColor="base" borderWidth="none none small none">
                        <s-stack direction="inline" gap="large-100" alignItems="start">
                            <s-badge tone="success" size="large">1</s-badge>
                            <s-paragraph><strong>Create Your Page</strong> — Go to Dashboard and click "Create Page + Add to Menu." This adds a Quick Order page to your store and links it in the navigation menu.</s-paragraph>
                        </s-stack>
                    </s-box>
                    <s-box padding="large-100" borderColor="base" borderWidth="none none small none">
                        <s-stack direction="inline" gap="large-100" alignItems="start">
                            <s-badge tone="success" size="large">2</s-badge>
                            <s-paragraph><strong>Configure Settings</strong> — Visit the Settings page to hide out-of-stock items, set minimum quantities, show product images, and customize table columns.</s-paragraph>
                        </s-stack>
                    </s-box>
                    <s-box padding="large-100">
                        <s-stack direction="inline" gap="large-100" alignItems="start">
                            <s-badge tone="success" size="large">3</s-badge>
                            <s-paragraph><strong>Customers Start Ordering</strong> — Your wholesale customers visit the Quick Order page, type quantities or upload a CSV, and place bulk orders in one click.</s-paragraph>
                        </s-stack>
                    </s-box>
                </s-stack>
            </s-section>

            {{-- ── FAQ ── --}}
            <s-section heading="❓ Frequently Asked Questions">
                <s-box padding="large-200" background="base" border="base" borderRadius="large-100">
                    <s-stack gap="large-100">

                        <s-stack gap="base">
                            <s-text variant="headingSm" fontWeight="bold">📤 How does CSV upload work?</s-text>
                            <s-paragraph tone="subdued">
                                Download the sample CSV from the Quick Order page. Fill in the SKU, product name, or ID in column 1, optionally add quantity in column 3, and tags in column 4. Upload the file — products are matched automatically.
                            </s-paragraph>
                        </s-stack>

                        <s-stack gap="base">
                            <s-text variant="headingSm" fontWeight="bold">📧 What is a Draft Order and how is it different?</s-text>
                            <s-paragraph tone="subdued">
                                Draft Orders are created in your Shopify admin. They work on <strong>password-protected stores</strong> and let you send an invoice email to the customer. Unlike "Add to Cart," draft orders bypass the cart entirely — perfect for B2B wholesale.
                            </s-paragraph>
                        </s-stack>

                        <s-stack gap="base">
                            <s-text variant="headingSm" fontWeight="bold">🖼️ Why are product images not showing?</s-text>
                            <s-paragraph tone="subdued">
                                Go to <strong>Settings → Show product images → Save</strong>. Then click <strong>"Refresh Product Catalog"</strong> on the Settings page. Images will appear after the catalog refresh completes.
                            </s-paragraph>
                        </s-stack>

                        <s-stack gap="base">
                            <s-text variant="headingSm" fontWeight="bold">📊 How do I hide out-of-stock products?</s-text>
                            <s-paragraph tone="subdued">
                                By default, out-of-stock products are hidden. You can change this in <strong>Settings → Hide out-of-stock products</strong> — uncheck to show all products including sold-out items.
                            </s-paragraph>
                        </s-stack>

                        <s-stack gap="base">
                            <s-text variant="headingSm" fontWeight="bold">🔢 How do min/max quantities work?</s-text>
                            <s-paragraph tone="subdued">
                                Set a global minimum and maximum quantity per product in Settings. Customers cannot order less than the minimum or more than the maximum — this is enforced both on the page and on the server.
                            </s-paragraph>
                        </s-stack>

                        <s-stack gap="base">
                            <s-text variant="headingSm" fontWeight="bold">💱 Are prices shown in my store currency?</s-text>
                            <s-paragraph tone="subdued">
                                Yes! QuickB2B automatically detects your store's currency (USD, EUR, INR, GBP, etc.) and displays prices accordingly.
                            </s-paragraph>
                        </s-stack>

                        <s-stack gap="base">
                            <s-text variant="headingSm" fontWeight="bold">🗑️ How do I remove the Quick Order page from my store?</s-text>
                            <s-paragraph tone="subdued">
                                Go to <strong>Dashboard → Delete Page</strong>, or visit the <strong>Plan</strong> page where you'll find a cleanup option. This removes the page and its menu link from your store.
                            </s-paragraph>
                        </s-stack>

                        <s-stack gap="base">
                            <s-text variant="headingSm" fontWeight="bold">🏷️ Can I hide specific products by tag?</s-text>
                            <s-paragraph tone="subdued">
                                Yes! In <strong>Settings → Product Tags</strong>, enter comma-separated tags. Any product with those tags will be hidden from the Quick Order page.
                            </s-paragraph>
                        </s-stack>

                    </s-stack>
                </s-box>
            </s-section>

            {{-- ── Contact ── --}}
            <s-section heading="📧 Get In Touch">
                <s-box padding="large-200" background="base" border="base" borderRadius="large-100">
                    <s-stack gap="large-100">
                        <s-paragraph>
                            We'd love to hear from you! Whether you need help, have a feature idea, or want to report something — drop us an email.
                        </s-paragraph>
                        <s-stack gap="base">
                            <s-text variant="bodyMd">💡 <strong>Feature Request?</strong> Tell us what would make QuickB2B even better for your store.</s-text>
                            <s-text variant="bodyMd">🐛 <strong>Found a bug?</strong> Let us know what went wrong and we'll fix it fast.</s-text>
                            <s-text variant="bodyMd">❓ <strong>Need help?</strong> Stuck somewhere? We'll guide you through it.</s-text>
                            <s-text variant="bodyMd">📣 <strong>Any complaint?</strong> We take feedback seriously — good or bad, we want it all.</s-text>
                        </s-stack>
                        <s-stack direction="inline" gap="base" alignItems="center">
                            <s-text variant="headingSm" fontWeight="bold">✉️ support@pixiestore.in</s-text>
                            <s-button variant="secondary" size="small" onclick="copySupportEmail()" id="btn-copy-email">📋 Copy</s-button>
                        </s-stack>
                        <s-paragraph tone="subdued" variant="bodySm">
                            ⚡ We typically respond within a few hours. No bots, real humans!
                        </s-paragraph>
                        <script>
                            function copySupportEmail() {
                                navigator.clipboard.writeText('support@pixiestore.in').then(function() {
                                    var btn = document.getElementById('btn-copy-email');
                                    btn.textContent = '✅ Copied!';
                                    setTimeout(function() { btn.textContent = '📋 Copy'; }, 2000);
                                });
                            }
                        </script>
                    </s-stack>
                </s-box>
            </s-section>

        </s-stack>
    </s-page>
@endsection
